<?php

namespace App\Jobs\Backup;

use App\Events\Backup\BackupJobCompleted;
use App\Events\Backup\BackupJobStarted;
use App\Mail\BackupNotificationMail;
use App\Models\AuditLog;
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
use Illuminate\Support\Facades\Process;

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
        $backupJob->loadMissing(['source.mysqlHost', 'source.mongodbHost', 'source.filesystemHost']);
        $log = BackupLog::findOrFail($this->backupLogId);

        // 1. Update status to running
        $log->update(['status' => 'running', 'started_at' => $log->started_at ?? now()]);

        // Early-exit: cancelled before worker even picked up the job
        $log->refresh();
        if ($log->status === 'cancelled') {
            return;
        }

        // Determine if this run should be incremental or full
        $isIncremental = false;
        $parentLog = null;
        $checkpoint = null;

        if ($backupJob->backup_type === 'incremental') {
            $resolved = $this->resolveIncrementalState($backupJob);
            $isIncremental = $resolved['is_incremental'];
            $parentLog = $resolved['parent_log'];
            $checkpoint = $resolved['checkpoint'];
        }

        // Update the log with full/incremental flag and parent reference
        $log->update([
            'is_full' => ! $isIncremental,
            'parent_backup_log_id' => $isIncremental && $parentLog ? $parentLog->id : null,
        ]);

        try {
            event(new BackupJobStarted(
                jobId: $backupJob->id,
                logId: $log->id,
                jobName: $backupJob->name,
            ));
        } catch (\Throwable) {
            // Non-critical: Reverb may be unavailable; do not abort the backup
        }

        $tmpDir = storage_path('app/backups/tmp/'.$log->id);
        @mkdir($tmpDir, 0755, true);

        try {
            // 2. Execute backup for each enabled source type
            $source = $backupJob->source;
            $sourceConfig = $source->config;
            $results = [];
            $totalSize = 0;
            $fileNames = [];
            $incrementalCheckpoints = [];
            $processedTypes = [];

            if ($source->mysql_host_id) {
                $host = $source->mysqlHost;
                $mysqlDir = $tmpDir.'/mysql';
                @mkdir($mysqlDir, 0755, true);
                $mysqlConf = array_merge($host->config['mysql'] ?? [], ['ssh' => $host->sshConfig()], $sourceConfig['mysql'] ?? []);
                $processedTypes[] = 'mysql';
                $databases = $mysqlConf['databases'] ?? (isset($mysqlConf['database']) ? [$mysqlConf['database']] : []);
                foreach ($databases as $db) {
                    $singleConf = array_merge($mysqlConf, ['database' => $db]);
                    $mysqlCheckpoint = $checkpoint['mysql'][$db] ?? ($checkpoint['mysql'] ?? null);

                    if ($isIncremental) {
                        $r = $mysqlService->incrementalDump($singleConf, $mysqlDir, $backupJob->compression, $mysqlCheckpoint);
                    } else {
                        $r = $mysqlService->dump($singleConf, $mysqlDir, $backupJob->compression);
                    }

                    $results[] = $r;
                    $totalSize += $r['file_size'] ?? 0;
                    $fileNames[] = 'mysql/'.($r['file_name'] ?? 'dump');
                    if (isset($r['incremental_checkpoint'])) {
                        $incrementalCheckpoints['mysql'][$db] = $r['incremental_checkpoint'];
                    }
                }
            }

            if ($source->mongodb_host_id) {
                $host = $source->mongodbHost;
                $mongoDir = $tmpDir.'/mongodb';
                @mkdir($mongoDir, 0755, true);
                $mongoConf = array_merge($host->config['mongodb'] ?? [], ['ssh' => $host->sshConfig()], $sourceConfig['mongodb'] ?? []);
                $processedTypes[] = 'mongodb';
                $databases = $mongoConf['databases'] ?? (isset($mongoConf['database']) ? [$mongoConf['database']] : []);
                foreach ($databases as $db) {
                    $singleConf = array_merge($mongoConf, ['database' => $db]);
                    $mongoCheckpoint = $checkpoint['mongodb'][$db] ?? ($checkpoint['mongodb'] ?? null);

                    if ($isIncremental) {
                        $r = $mongodbService->incrementalDump($singleConf, $mongoDir, $backupJob->compression, $mongoCheckpoint);
                    } else {
                        $r = $mongodbService->dump($singleConf, $mongoDir, $backupJob->compression);
                    }

                    $results[] = $r;
                    $totalSize += $r['file_size'] ?? 0;
                    $fileNames[] = 'mongodb/'.($r['file_name'] ?? 'dump');
                    if (isset($r['incremental_checkpoint'])) {
                        $incrementalCheckpoints['mongodb'][$db] = $r['incremental_checkpoint'];
                    }
                }
            }

            if ($source->filesystem_host_id) {
                $host = $source->filesystemHost;
                $fsDir = $tmpDir.'/filesystem';
                @mkdir($fsDir, 0755, true);
                $fsConf = array_merge($host->config['filesystem'] ?? [], ['ssh' => $host->sshConfig()], $sourceConfig['filesystem'] ?? []);
                $processedTypes[] = 'filesystem';
                $paths = $fsConf['paths'] ?? (isset($fsConf['path']) ? [$fsConf['path']] : []);
                $excludePatterns = $fsConf['exclude_patterns'] ?? [];
                foreach ($paths as $path) {
                    $singleConf = [
                        'path' => $path,
                        'exclude_patterns' => $excludePatterns,
                        'ssh' => $fsConf['ssh'],
                        'transport' => $host->filesystemTransport(),
                        'ftp' => $host->filesystemFtpConfig() ?? [],
                    ];
                    $fsCheckpoint = $checkpoint['filesystem'][$path] ?? ($checkpoint['filesystem'] ?? null);

                    if ($isIncremental) {
                        $r = $filesystemService->incrementalBackup($singleConf, $fsDir, $backupJob->compression, $fsCheckpoint);
                    } else {
                        $r = $filesystemService->backup($singleConf, $fsDir, $backupJob->compression);
                    }

                    $results[] = $r;
                    $totalSize += $r['file_size'] ?? 0;
                    $fileNames[] = 'filesystem/'.($r['file_name'] ?? 'archive');
                    if (isset($r['incremental_checkpoint'])) {
                        $incrementalCheckpoints['filesystem'][$path] = $r['incremental_checkpoint'];
                    }
                }
            }

            if (empty($results)) {
                throw new \RuntimeException('No source types configured for this backup source.');
            }

            // Cancellation checkpoint: after source dumps, before packaging/upload
            if ($log->fresh()->status === 'cancelled') {
                return;
            }

            // Use first result for single-type sources, or create package archive for multi-type
            if (count($results) === 1) {
                $result = $results[0];
            } else {
                // Create a tar archive of the entire tmpDir (package with subdirectories)
                $packageName = \Illuminate\Support\Str::slug($backupJob->source->name).'-'.now()->format('Ymd-His').'.tar.gz';
                $packagePath = storage_path('app/backups/tmp/'.$packageName);

                $result = Process::timeout(config('backup.process_timeout'))->run([
                    'tar', '-czf', $packagePath, '-C', $tmpDir, '.',
                ]);

                if (! $result->successful()) {
                    throw new \RuntimeException('Failed to create backup package archive: '.$result->errorOutput());
                }

                $result = [
                    'file_path' => $packagePath,
                    'file_name' => $packageName,
                    'file_size' => filesize($packagePath),
                    'meta' => [
                        'types' => $processedTypes,
                        'files' => $fileNames,
                    ],
                ];
            }

            // Cancellation checkpoint: before potentially long upload
            if ($log->fresh()->status === 'cancelled') {
                return;
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
                'incremental_checkpoint' => ! empty($incrementalCheckpoints) ? $incrementalCheckpoints : null,
            ]);

            // 5. Apply retention policy
            $this->applyRetention($backupJob, $s3Service, $ftpService);

            // 6. Update next run
            $schedulerService->updateNextRun($backupJob);

            // 7. Dispatch completion event
            try {
                event(new BackupJobCompleted(
                    jobId: $backupJob->id,
                    logId: $log->id,
                    status: 'success',
                    jobName: $backupJob->name,
                ));
            } catch (\Throwable) {
                // Non-critical: Reverb may be unavailable
            }

            // 8. Record audit log
            AuditLog::record(
                'backup_completed',
                "Backup job completed successfully: {$backupJob->name}".($isIncremental ? ' (incremental)' : ' (full)'),
                $backupJob,
                null,
                [
                    'log_id' => $log->id,
                    'file_name' => $result['file_name'],
                    'file_size_bytes' => $result['file_size'],
                    'duration_seconds' => $log->duration_seconds,
                    'storage_path' => $remotePath,
                    'is_incremental' => $isIncremental,
                ],
            );

            // 9. Send notification if configured
            if ($backupJob->notify_on_success && ! empty($backupJob->notification_emails)) {
                $this->sendNotification($backupJob, $log, 'success');
            }

        } catch (\Throwable $e) {
            // Refresh to avoid overwriting a status already set by cancelJob()
            $log->refresh();

            if ($log->status !== 'cancelled') {
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

                try {
                    event(new BackupJobCompleted(
                        jobId: $backupJob->id,
                        logId: $log->id,
                        status: 'failed',
                        jobName: $backupJob->name,
                        errorMessage: $e->getMessage(),
                    ));
                } catch (\Throwable) {
                    // Non-critical: Reverb may be unavailable
                }

                AuditLog::record(
                    'backup_failed',
                    "Backup job failed: {$backupJob->name}",
                    $backupJob,
                    null,
                    [
                        'log_id' => $log->id,
                        'error_message' => $e->getMessage(),
                    ],
                );

                if ($backupJob->notify_on_failure && ! empty($backupJob->notification_emails)) {
                    $this->sendNotification($backupJob, $log, 'failed');
                }
            }

            // Always update next run regardless of cancellation or failure
            $schedulerService->updateNextRun($backupJob);
        } finally {
            // Cleanup temp directory
            $this->cleanupTempDir($tmpDir);
        }
    }

    /**
     * Called by Laravel's queue system when the job fails after all retries
     * or when it times out. Ensures the backup_log is never left in 'running'.
     */
    public function failed(\Throwable $exception): void
    {
        $log = BackupLog::find($this->backupLogId);

        if ($log && in_array($log->status, ['pending', 'running'])) {
            // Strip Reverb/Pusher transport errors from the user-visible message
            $rawMessage = $exception->getMessage();
            $isBroadcastError = str_contains($rawMessage, 'Pusher error') || str_contains($rawMessage, 'cURL error');
            $userMessage = $isBroadcastError
                ? 'Job terminated by queue worker (broadcast transport error — check Reverb).'
                : 'Job terminated by queue worker: '.$rawMessage;
            $log->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => $log->started_at ? now()->diffInSeconds($log->started_at) : null,
                'error_message' => $userMessage,
            ]);

            try {
                $jobName = BackupJob::find($this->backupJobId)?->name ?? 'Unknown';
                event(new BackupJobCompleted(
                    jobId: $this->backupJobId,
                    logId: $log->id,
                    status: 'failed',
                    jobName: $jobName,
                    errorMessage: 'Job terminated by queue worker.',
                ));
            } catch (\Throwable) {
                // Broadcast must never block the failed() handler
            }

            AuditLog::record(
                'backup_failed',
                "Backup job terminated by queue worker: {$jobName}",
                BackupJob::find($this->backupJobId),
                null,
                [
                    'log_id' => $log->id,
                    'error_message' => $exception->getMessage(),
                ],
            );
        }
    }

    /**
     * Determine whether this run should be incremental or full.
     *
     * Returns full backup when:
     *  - No prior successful full backup exists
     *  - full_backup_every threshold is reached (count of incremental runs since last full)
     *
     * Otherwise returns incremental with the last checkpoint.
     */
    protected function resolveIncrementalState(BackupJob $job): array
    {
        // Find the last successful full backup for this job
        $lastFullLog = BackupLog::where('backup_job_id', $job->id)
            ->where('status', 'success')
            ->where('is_full', true)
            ->orderByDesc('started_at')
            ->first();

        // No prior full backup: force full
        if (! $lastFullLog) {
            return ['is_incremental' => false, 'parent_log' => null, 'checkpoint' => null];
        }

        // Check if we've reached the full_backup_every threshold
        if ($job->full_backup_every) {
            $incrementalCount = BackupLog::where('backup_job_id', $job->id)
                ->where('status', 'success')
                ->where('is_full', false)
                ->where('started_at', '>', $lastFullLog->started_at)
                ->count();

            if ($incrementalCount >= $job->full_backup_every) {
                return ['is_incremental' => false, 'parent_log' => null, 'checkpoint' => null];
            }
        }

        // Find the most recent successful backup (full or incremental) for checkpoint
        $lastSuccessLog = BackupLog::where('backup_job_id', $job->id)
            ->where('status', 'success')
            ->orderByDesc('started_at')
            ->first();

        $checkpoint = $lastSuccessLog?->incremental_checkpoint;

        // If no checkpoint data exists on the last log, build one from the timestamp
        if (! $checkpoint && $lastSuccessLog) {
            $checkpoint = [
                'timestamp' => $lastSuccessLog->started_at->toIso8601String(),
            ];
        }

        return [
            'is_incremental' => true,
            'parent_log' => $lastFullLog,
            'checkpoint' => $checkpoint,
        ];
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
        $destPath = $basePath.'/'.$remotePath;
        $destDir = dirname($destPath);

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        return copy($filePath, $destPath);
    }

    /**
     * Apply retention policy - delete old backups beyond retention_count.
     * For incremental jobs: retention counts full backup chains (full + its incrementals).
     */
    protected function applyRetention(BackupJob $job, S3StorageService $s3Service, FtpStorageService $ftpService): void
    {
        if ($job->backup_type === 'incremental') {
            $this->applyIncrementalRetention($job, $s3Service, $ftpService);

            return;
        }

        $logsToDelete = BackupLog::where('backup_job_id', $job->id)
            ->where('status', 'success')
            ->where('is_locked', false)
            ->whereNotNull('storage_path')
            ->orderByDesc('started_at')
            ->skip($job->retention_count)
            ->take(100)
            ->get();

        foreach ($logsToDelete as $oldLog) {
            $this->deleteBackupFile($job, $oldLog, $s3Service, $ftpService);
        }
    }

    /**
     * Apply retention for incremental jobs: count full backup chains.
     * Each chain = 1 full + N incrementals. Keep retention_count chains.
     */
    protected function applyIncrementalRetention(BackupJob $job, S3StorageService $s3Service, FtpStorageService $ftpService): void
    {
        // Get all full backups ordered by date, skip the ones we want to keep
        $fullLogsToDelete = BackupLog::where('backup_job_id', $job->id)
            ->where('status', 'success')
            ->where('is_full', true)
            ->where('is_locked', false)
            ->whereNotNull('storage_path')
            ->orderByDesc('started_at')
            ->skip($job->retention_count)
            ->take(100)
            ->get();

        foreach ($fullLogsToDelete as $fullLog) {
            // Delete all incrementals that belong to this full backup chain
            $incrementals = BackupLog::where('backup_job_id', $job->id)
                ->where('status', 'success')
                ->where('is_full', false)
                ->where('is_locked', false)
                ->where('parent_backup_log_id', $fullLog->id)
                ->whereNotNull('storage_path')
                ->get();

            foreach ($incrementals as $incrLog) {
                $this->deleteBackupFile($job, $incrLog, $s3Service, $ftpService);
            }

            // Delete the full backup itself
            $this->deleteBackupFile($job, $fullLog, $s3Service, $ftpService);
        }
    }

    /**
     * Delete a single backup file from the storage destination.
     */
    protected function deleteBackupFile(BackupJob $job, BackupLog $log, S3StorageService $s3Service, FtpStorageService $ftpService): void
    {
        try {
            match ($job->destination->type) {
                's3' => $s3Service->delete($job->destination->config, $log->storage_path),
                'ftp' => $ftpService->delete($job->destination->config, $log->storage_path),
                'local' => @unlink($log->storage_path),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::warning('Failed to delete old backup', [
                'log_id' => $log->id,
                'path' => $log->storage_path,
                'error' => $e->getMessage(),
            ]);
        }

        $log->update(['storage_path' => null]);
    }

    /**
     * Send email notification about backup result to all configured recipients.
     */
    protected function sendNotification(BackupJob $job, BackupLog $log, string $status): void
    {
        $emails = $job->notification_emails ?? [];
        if (empty($emails)) {
            return;
        }

        // Re-read .env to override stale in-memory config (worker may have started before mail config was set)
        $this->refreshSmtpConfig();

        foreach ($emails as $email) {
            try {
                Mail::mailer('smtp')->to($email)->send(new BackupNotificationMail($job, $log, $status));
            } catch (\Throwable $e) {
                Log::error('Failed to send backup notification', [
                    'job_id' => $job->id,
                    'email' => $email,
                    'status' => $status,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Re-parse .env and update the in-memory SMTP mail config.
     * This ensures the queue worker always uses current SMTP settings
     * even if it was started before the mail config was finalised.
     */
    private function refreshSmtpConfig(): void
    {
        $envFile = base_path('.env');
        if (! is_readable($envFile)) {
            return;
        }

        $vars = [];
        foreach (file($envFile) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $val] = explode('=', $line, 2);
            $val = trim($val, '"\'');
            // Resolve ${VAR} references against vars parsed earlier (dotenv interpolation)
            $val = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/', fn ($m) => $vars[$m[1]] ?? $m[0], $val);
            $vars[trim($key)] = $val;
        }

        $overrides = array_filter([
            'mail.mailers.smtp.host' => $vars['MAIL_HOST'] ?? null,
            'mail.mailers.smtp.port' => isset($vars['MAIL_PORT']) ? (int) $vars['MAIL_PORT'] : null,
            'mail.mailers.smtp.encryption' => $vars['MAIL_ENCRYPTION'] ?? null,
            'mail.mailers.smtp.username' => $vars['MAIL_USERNAME'] ?? null,
            'mail.mailers.smtp.password' => $vars['MAIL_PASSWORD'] ?? null,
            'mail.from.address' => $vars['MAIL_FROM_ADDRESS'] ?? null,
            'mail.from.name' => $vars['MAIL_FROM_NAME'] ?? null,
        ], fn ($v) => $v !== null);

        if (! empty($overrides)) {
            config($overrides);
            // Purge cached smtp mailer so it is rebuilt with updated config
            Mail::purge('smtp');
        }
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
