<?php

namespace App\Jobs\Backup;

use App\Events\Backup\BackupJobCompleted;
use App\Events\Backup\BackupJobStarted;
use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Services\Backup\BackupSchedulerService;
use App\Services\Backup\FilesystemBackupService;
use App\Services\Backup\FtpStorageService;
use App\Services\Backup\MongodbBackupService;
use App\Services\Backup\MysqlBackupService;
use App\Services\Backup\S3StorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        public int $backupJobId,
        public int $backupLogId,
    ) {}

    public function handle(
        MysqlBackupService $mysqlService,
        MongodbBackupService $mongodbService,
        FilesystemBackupService $filesystemService,
        S3StorageService $s3Service,
        FtpStorageService $ftpService,
        BackupSchedulerService $schedulerService,
    ): void {
        $backupJob = BackupJob::with(['source', 'destination'])->findOrFail($this->backupJobId);
        $log = BackupLog::findOrFail($this->backupLogId);

        // 1. Update status to running
        $log->update(['status' => 'running']);

        event(new BackupJobStarted(
            jobId: $backupJob->id,
            logId: $log->id,
            jobName: $backupJob->name,
        ));

        $tmpDir = storage_path('app/backups/tmp/' . $log->id);
        @mkdir($tmpDir, 0755, true);

        try {
            // 2. Execute backup for each enabled source type
            $sourceConfig = $backupJob->source->config;
            $sharedSsh = $sourceConfig['ssh'] ?? ['enabled' => false];
            $results = [];
            $totalSize = 0;
            $fileNames = [];

            if (isset($sourceConfig['mysql'])) {
                $mysqlDir = $tmpDir . '/mysql';
                @mkdir($mysqlDir, 0755, true);
                $mysqlConf = array_merge($sourceConfig['mysql'], ['ssh' => $sharedSsh]);
                $databases = $mysqlConf['databases'] ?? (isset($mysqlConf['database']) ? [$mysqlConf['database']] : []);
                foreach ($databases as $db) {
                    $singleConf = array_merge($mysqlConf, ['database' => $db]);
                    $r = $mysqlService->dump($singleConf, $mysqlDir, $backupJob->compression);
                    $results[] = $r;
                    $totalSize += $r['file_size'] ?? 0;
                    $fileNames[] = 'mysql/' . ($r['file_name'] ?? 'dump');
                }
            }

            if (isset($sourceConfig['mongodb'])) {
                $mongoDir = $tmpDir . '/mongodb';
                @mkdir($mongoDir, 0755, true);
                $mongoConf = array_merge($sourceConfig['mongodb'], ['ssh' => $sharedSsh]);
                $databases = $mongoConf['databases'] ?? (isset($mongoConf['database']) ? [$mongoConf['database']] : []);
                foreach ($databases as $db) {
                    $singleConf = array_merge($mongoConf, ['database' => $db]);
                    $r = $mongodbService->dump($singleConf, $mongoDir, $backupJob->compression);
                    $results[] = $r;
                    $totalSize += $r['file_size'] ?? 0;
                    $fileNames[] = 'mongodb/' . ($r['file_name'] ?? 'dump');
                }
            }

            if (isset($sourceConfig['filesystem'])) {
                $fsDir = $tmpDir . '/filesystem';
                @mkdir($fsDir, 0755, true);
                $fsConf = $sourceConfig['filesystem'];
                $paths = $fsConf['paths'] ?? (isset($fsConf['path']) ? [$fsConf['path']] : []);
                $excludePatterns = $fsConf['exclude_patterns'] ?? [];
                foreach ($paths as $path) {
                    $singleConf = ['path' => $path, 'exclude_patterns' => $excludePatterns, 'ssh' => $sharedSsh];
                    $r = $filesystemService->backup($singleConf, $fsDir, $backupJob->compression);
                    $results[] = $r;
                    $totalSize += $r['file_size'] ?? 0;
                    $fileNames[] = 'filesystem/' . ($r['file_name'] ?? 'archive');
                }
            }

            if (empty($results)) {
                throw new \RuntimeException('No source types configured for this backup source.');
            }

            // Use first result for single-type sources, or create package archive for multi-type
            if (count($results) === 1) {
                $result = $results[0];
            } else {
                // Create a tar archive of the entire tmpDir (package with subdirectories)
                $packageName = \Illuminate\Support\Str::slug($backupJob->source->name) . '-' . now()->format('Ymd-His') . '.tar.gz';
                $packagePath = storage_path('app/backups/tmp/' . $packageName);
                $tarCmd = sprintf(
                    'tar -czf %s -C %s .',
                    escapeshellarg($packagePath),
                    escapeshellarg($tmpDir)
                );
                exec($tarCmd, $output, $exitCode);

                if ($exitCode !== 0) {
                    throw new \RuntimeException('Failed to create backup package archive.');
                }

                $result = [
                    'file_path' => $packagePath,
                    'file_name' => $packageName,
                    'file_size' => filesize($packagePath),
                    'meta' => [
                        'types' => array_keys(array_intersect_key($sourceConfig, array_flip(['mysql', 'mongodb', 'filesystem']))),
                        'files' => $fileNames,
                    ],
                ];
            }

            // 3. Upload to destination
            $remotePath = $this->buildRemotePath($backupJob, $result['file_name']);

            match ($backupJob->destination->type) {
                's3' => $s3Service->upload(
                    $backupJob->destination->config,
                    $result['file_path'],
                    $remotePath,
                ),
                'ftp' => $ftpService->upload(
                    $backupJob->destination->config,
                    $result['file_path'],
                    $remotePath,
                ),
                'local' => $this->uploadLocal(
                    $backupJob->destination->config,
                    $result['file_path'],
                    $remotePath,
                ),
                default => throw new \RuntimeException("Unknown destination type: {$backupJob->destination->type}"),
            };

            // 4. Update log with success
            $log->update([
                'status' => 'success',
                'finished_at' => now(),
                'duration_seconds' => now()->diffInSeconds($log->started_at),
                'file_name' => $result['file_name'],
                'file_size_bytes' => $result['file_size'],
                'storage_path' => $remotePath,
                'meta' => $result['meta'] ?? null,
            ]);

            // 5. Apply retention policy
            $this->applyRetention($backupJob, $s3Service, $ftpService);

            // 6. Update next run
            $schedulerService->updateNextRun($backupJob);

            // 7. Dispatch completion event
            event(new BackupJobCompleted(
                jobId: $backupJob->id,
                logId: $log->id,
                status: 'success',
                jobName: $backupJob->name,
            ));

            // 8. Send notification if configured
            if ($backupJob->notify_on_success && $backupJob->notification_email) {
                $this->sendNotification($backupJob, $log, 'success');
            }

        } catch (\Throwable $e) {
            Log::error('Backup job failed', [
                'job_id' => $backupJob->id,
                'log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => now()->diffInSeconds($log->started_at),
                'error_message' => $e->getMessage(),
            ]);

            // Update next run even on failure
            $schedulerService->updateNextRun($backupJob);

            event(new BackupJobCompleted(
                jobId: $backupJob->id,
                logId: $log->id,
                status: 'failed',
                jobName: $backupJob->name,
                errorMessage: $e->getMessage(),
            ));

            if ($backupJob->notify_on_failure && $backupJob->notification_email) {
                $this->sendNotification($backupJob, $log, 'failed');
            }
        } finally {
            // Cleanup temp directory
            $this->cleanupTempDir($tmpDir);
        }
    }

    /**
     * Build the remote storage path.
     */
    protected function buildRemotePath(BackupJob $job, string $fileName): string
    {
        $date = now()->format('Y/m/d');
        $sourceName = \Illuminate\Support\Str::slug($job->source->name);

        return "backups/{$sourceName}/{$date}/{$fileName}";
    }

    /**
     * Copy backup file to local storage destination.
     */
    protected function uploadLocal(array $config, string $filePath, string $remotePath): bool
    {
        $basePath = rtrim($config['path'] ?? '', '/');
        $destPath = $basePath . '/' . $remotePath;
        $destDir = dirname($destPath);

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        return copy($filePath, $destPath);
    }

    /**
     * Apply retention policy - delete old backups beyond retention_count.
     */
    protected function applyRetention(BackupJob $job, S3StorageService $s3Service, FtpStorageService $ftpService): void
    {
        $logsToDelete = BackupLog::where('backup_job_id', $job->id)
            ->where('status', 'success')
            ->whereNotNull('storage_path')
            ->orderByDesc('started_at')
            ->skip($job->retention_count)
            ->take(100)
            ->get();

        foreach ($logsToDelete as $oldLog) {
            try {
                match ($job->destination->type) {
                    's3' => $s3Service->delete($job->destination->config, $oldLog->storage_path),
                    'ftp' => $ftpService->delete($job->destination->config, $oldLog->storage_path),
                    'local' => @unlink($oldLog->storage_path),
                    default => null,
                };
            } catch (\Throwable $e) {
                Log::warning('Failed to delete old backup', [
                    'log_id' => $oldLog->id,
                    'path' => $oldLog->storage_path,
                    'error' => $e->getMessage(),
                ]);
            }

            $oldLog->update(['storage_path' => null]);
        }
    }

    /**
     * Send email notification about backup result.
     */
    protected function sendNotification(BackupJob $job, BackupLog $log, string $status): void
    {
        try {
            Mail::raw(
                $this->buildNotificationMessage($job, $log, $status),
                function ($message) use ($job, $status) {
                    $message->to($job->notification_email)
                        ->subject("Backup {$status}: {$job->name}");
                }
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send backup notification', [
                'job_id' => $job->id,
                'email' => $job->notification_email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build notification message body.
     */
    protected function buildNotificationMessage(BackupJob $job, BackupLog $log, string $status): string
    {
        $lines = [
            "Backup Job: {$job->name}",
            "Status: " . strtoupper($status),
            "Source: {$job->source->name}",
            "Destination: {$job->destination->name} ({$job->destination->type})",
            "Started: {$log->started_at}",
            "Finished: {$log->finished_at}",
            "Duration: {$log->formatted_duration}",
        ];

        if ($status === 'success') {
            $lines[] = "File: {$log->file_name}";
            $lines[] = "Size: {$log->formatted_size}";
        }

        if ($status === 'failed' && $log->error_message) {
            $lines[] = "Error: {$log->error_message}";
        }

        return implode("\n", $lines);
    }

    /**
     * Clean up temporary files.
     */
    protected function cleanupTempDir(string $dir): void
    {
        if (is_dir($dir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                if ($file->isDir()) {
                    @rmdir($file->getRealPath());
                } else {
                    @unlink($file->getRealPath());
                }
            }

            @rmdir($dir);
        }
    }
}
