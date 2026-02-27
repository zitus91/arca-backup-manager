<?php

namespace App\Jobs\Restore;

use App\Events\Restore\RestoreJobCompleted;
use App\Events\Restore\RestoreJobStarted;
use App\Models\BackupLog;
use App\Models\RestoreLog;
use App\Services\Backup\FtpStorageService;
use App\Services\Backup\S3StorageService;
use App\Services\Restore\FilesystemRestoreService;
use App\Services\Restore\MongodbRestoreService;
use App\Services\Restore\MysqlRestoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ProcessRestoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        public int $restoreLogId,
    ) {}

    public function handle(
        MysqlRestoreService $mysqlRestore,
        MongodbRestoreService $mongodbRestore,
        FilesystemRestoreService $filesystemRestore,
        S3StorageService $s3Service,
        FtpStorageService $ftpService,
    ): void {
        $restoreLog = RestoreLog::with(['backupLog.job.source', 'backupLog.job.destination'])
            ->findOrFail($this->restoreLogId);

        $backupLog = $restoreLog->backupLog;
        $backupJob = $backupLog->job;
        $source = $backupJob->source;
        $destination = $backupJob->destination;

        // 1. Mark as running
        $restoreLog->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        event(new RestoreJobStarted(
            restoreLogId: $restoreLog->id,
            backupLogId: $backupLog->id,
            backupJobName: $backupJob->name,
        ));

        $tmpDir = storage_path('app/restores/tmp/' . $restoreLog->id);
        @mkdir($tmpDir, 0755, true);

        try {
            // 2. Download backup file from destination to temp dir
            $localFilePath = $this->downloadBackupFile(
                $destination,
                $backupLog,
                $tmpDir,
                $s3Service,
                $ftpService,
            );

            // 3. Determine what types were backed up
            $sourceConfig = $source->config;
            $restoreType = $restoreLog->restore_type;
            $results = [];
            $restoredDbNames = [];
            $restoredPaths = [];

            // If it's a multi-type package archive, extract it first
            $isPackage = $this->isPackageArchive($backupLog, $sourceConfig);

            if ($isPackage) {
                $extractedDir = $tmpDir . '/extracted';
                @mkdir($extractedDir, 0755, true);
                $this->extractPackage($localFilePath, $extractedDir);
            }

            // 4. Restore MySQL if applicable
            if (in_array($restoreType, ['db_only', 'full']) && isset($sourceConfig['mysql'])) {
                $mysqlConf = $sourceConfig['mysql'];
                $databases = $mysqlConf['databases'] ?? (isset($mysqlConf['database']) ? [$mysqlConf['database']] : []);

                foreach ($databases as $db) {
                    $singleConf = array_merge($mysqlConf, ['database' => $db]);
                    $dumpFile = $this->findMysqlDump($isPackage ? $extractedDir : $tmpDir, $db, $isPackage);

                    if ($dumpFile) {
                        $r = $mysqlRestore->restore($singleConf, $dumpFile);
                        $results[] = $r;
                        $restoredDbNames[] = $r['restored_db_name'];
                    }
                }
            }

            // 5. Restore MongoDB if applicable
            if (in_array($restoreType, ['db_only', 'full']) && isset($sourceConfig['mongodb'])) {
                $mongoConf = $sourceConfig['mongodb'];
                $databases = $mongoConf['databases'] ?? (isset($mongoConf['database']) ? [$mongoConf['database']] : []);

                foreach ($databases as $db) {
                    $singleConf = array_merge($mongoConf, ['database' => $db]);
                    $archiveFile = $this->findMongoArchive($isPackage ? $extractedDir : $tmpDir, $db, $isPackage);

                    if ($archiveFile) {
                        $r = $mongodbRestore->restore($singleConf, $archiveFile);
                        $results[] = $r;
                        $restoredDbNames[] = $r['restored_db_name'];
                    }
                }
            }

            // 6. Restore Filesystem if applicable
            if (in_array($restoreType, ['files_only', 'full']) && isset($sourceConfig['filesystem'])) {
                $fsConf = $sourceConfig['filesystem'];
                $paths = $fsConf['paths'] ?? (isset($fsConf['path']) ? [$fsConf['path']] : []);

                foreach ($paths as $path) {
                    $singleConf = ['path' => $path, 'exclude_patterns' => $fsConf['exclude_patterns'] ?? []];
                    $archiveFile = $this->findFilesystemArchive($isPackage ? $extractedDir : $tmpDir, basename($path), $isPackage);

                    if ($archiveFile) {
                        $r = $filesystemRestore->restore($singleConf, $archiveFile);
                        $results[] = $r;
                        $restoredPaths[] = $r['restored_path'];
                    }
                }
            }

            if (empty($results)) {
                throw new \RuntimeException('No matching backup data found for the requested restore type.');
            }

            // 7. Update restore log with success
            $restoreLog->update([
                'status' => 'success',
                'finished_at' => now(),
                'duration_seconds' => now()->diffInSeconds($restoreLog->started_at),
                'restored_db_name' => ! empty($restoredDbNames) ? implode(', ', $restoredDbNames) : null,
                'restored_path' => ! empty($restoredPaths) ? implode(', ', $restoredPaths) : null,
                'meta' => [
                    'restore_type' => $restoreType,
                    'results' => $results,
                ],
            ]);

            event(new RestoreJobCompleted(
                restoreLogId: $restoreLog->id,
                backupJobName: $backupJob->name,
                status: 'success',
            ));

        } catch (\Throwable $e) {
            Log::error('Restore failed', [
                'restore_log_id' => $restoreLog->id,
                'backup_log_id' => $backupLog->id,
                'error' => $e->getMessage(),
            ]);

            $restoreLog->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => $restoreLog->started_at
                    ? now()->diffInSeconds($restoreLog->started_at)
                    : 0,
                'error_message' => $e->getMessage(),
            ]);

            event(new RestoreJobCompleted(
                restoreLogId: $restoreLog->id,
                backupJobName: $backupJob->name,
                status: 'failed',
                errorMessage: $e->getMessage(),
            ));
        } finally {
            $this->cleanupTempDir($tmpDir);
        }
    }

    /**
     * Download the backup file from the storage destination.
     */
    protected function downloadBackupFile(
        $destination,
        BackupLog $backupLog,
        string $tmpDir,
        S3StorageService $s3Service,
        FtpStorageService $ftpService,
    ): string {
        $remotePath = $backupLog->storage_path;
        $fileName = $backupLog->file_name ?? basename($remotePath);
        $localPath = $tmpDir . '/' . $fileName;

        match ($destination->type) {
            's3' => $this->downloadFromS3($s3Service, $destination->config, $remotePath, $localPath),
            'ftp' => $this->downloadFromFtp($ftpService, $destination->config, $remotePath, $localPath),
            'local' => $this->downloadFromLocal($destination->config, $remotePath, $localPath),
            default => throw new \RuntimeException("Unsupported destination type: {$destination->type}"),
        };

        if (! file_exists($localPath)) {
            throw new \RuntimeException("Failed to download backup file to: {$localPath}");
        }

        return $localPath;
    }

    /**
     * Download from S3 to local file.
     */
    protected function downloadFromS3(S3StorageService $s3Service, array $config, string $remotePath, string $localPath): void
    {
        $diskConfig = [
            'driver' => 's3',
            'key' => $config['access_key'],
            'secret' => $config['secret_key'],
            'region' => $config['region'],
            'bucket' => $config['bucket'],
        ];

        if (! empty($config['endpoint'])) {
            $diskConfig['endpoint'] = $config['endpoint'];
            $diskConfig['use_path_style_endpoint'] = true;
        }

        config(['filesystems.disks.restore_s3_temp' => $diskConfig]);
        $disk = \Illuminate\Support\Facades\Storage::disk('restore_s3_temp');

        $stream = $disk->readStream($remotePath);
        if (! $stream) {
            throw new \RuntimeException("Cannot read S3 file: {$remotePath}");
        }

        $localFile = fopen($localPath, 'w');
        stream_copy_to_stream($stream, $localFile);
        fclose($localFile);
        fclose($stream);
    }

    /**
     * Download from FTP to local file.
     */
    protected function downloadFromFtp(FtpStorageService $ftpService, array $config, string $remotePath, string $localPath): void
    {
        $driver = ! empty($config['ssl']) ? 'sftp' : 'ftp';

        $diskConfig = [
            'driver' => $driver,
            'host' => $config['host'],
            'port' => $config['port'] ?? 21,
            'username' => $config['username'],
            'password' => $config['password'],
            'root' => $config['root_path'] ?? '/',
        ];

        if ($driver === 'ftp') {
            $diskConfig['passive'] = $config['passive'] ?? true;
            $diskConfig['ssl'] = $config['ssl'] ?? false;
        }

        config(['filesystems.disks.restore_ftp_temp' => $diskConfig]);
        $disk = \Illuminate\Support\Facades\Storage::disk('restore_ftp_temp');

        $stream = $disk->readStream($remotePath);
        if (! $stream) {
            throw new \RuntimeException("Cannot read FTP file: {$remotePath}");
        }

        $localFile = fopen($localPath, 'w');
        stream_copy_to_stream($stream, $localFile);
        fclose($localFile);
        fclose($stream);
    }

    /**
     * Copy from local storage to temp path.
     */
    protected function downloadFromLocal(array $config, string $remotePath, string $localPath): void
    {
        $basePath = rtrim($config['path'] ?? '', '/');
        $fullPath = $basePath . '/' . $remotePath;

        if (! file_exists($fullPath)) {
            throw new \RuntimeException("Backup file not found: {$fullPath}");
        }

        copy($fullPath, $localPath);
    }

    /**
     * Check if the backup is a multi-type package archive.
     */
    protected function isPackageArchive(BackupLog $backupLog, array $sourceConfig): bool
    {
        $types = array_intersect(array_keys($sourceConfig), ['mysql', 'mongodb', 'filesystem']);

        return count($types) > 1;
    }

    /**
     * Extract a package (multi-type) archive.
     */
    protected function extractPackage(string $archivePath, string $extractDir): void
    {
        if (str_ends_with($archivePath, '.tar.gz') || str_ends_with($archivePath, '.tgz')) {
            $cmd = 'tar -xzf ' . escapeshellarg($archivePath) . ' -C ' . escapeshellarg($extractDir);
        } elseif (str_ends_with($archivePath, '.zip')) {
            $cmd = 'unzip -o ' . escapeshellarg($archivePath) . ' -d ' . escapeshellarg($extractDir);
        } else {
            $cmd = 'tar -xf ' . escapeshellarg($archivePath) . ' -C ' . escapeshellarg($extractDir);
        }

        $result = Process::timeout(600)->run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to extract backup package: ' . $result->errorOutput());
        }
    }

    /**
     * Find the MySQL dump file in the extracted directory.
     */
    protected function findMysqlDump(string $dir, string $dbName, bool $isPackage): ?string
    {
        // In package: look under mysql/ subdirectory
        $searchDir = $isPackage ? $dir . '/mysql' : $dir;

        if (! is_dir($searchDir)) {
            return null;
        }

        // Find any mysql dump file matching this database
        $patterns = [
            "mysql_{$dbName}_*.sql.gz",
            "mysql_{$dbName}_*.sql.zip",
            "mysql_{$dbName}_*.sql",
        ];

        foreach ($patterns as $pattern) {
            $files = glob("{$searchDir}/{$pattern}");
            if (! empty($files)) {
                return $files[0];
            }
        }

        // Fallback: any sql file in the directory
        $files = glob("{$searchDir}/*.sql*");

        return ! empty($files) ? $files[0] : null;
    }

    /**
     * Find the MongoDB archive file in the extracted directory.
     */
    protected function findMongoArchive(string $dir, string $dbName, bool $isPackage): ?string
    {
        $searchDir = $isPackage ? $dir . '/mongodb' : $dir;

        if (! is_dir($searchDir)) {
            return null;
        }

        $patterns = [
            "mongodb_{$dbName}_*.tar.gz",
            "mongodb_{$dbName}_*.zip",
            "mongodb_{$dbName}_*.tar",
        ];

        foreach ($patterns as $pattern) {
            $files = glob("{$searchDir}/{$pattern}");
            if (! empty($files)) {
                return $files[0];
            }
        }

        $files = glob("{$searchDir}/*");

        return ! empty($files) ? $files[0] : null;
    }

    /**
     * Find the filesystem archive file in the extracted directory.
     */
    protected function findFilesystemArchive(string $dir, string $sourceName, bool $isPackage): ?string
    {
        $searchDir = $isPackage ? $dir . '/filesystem' : $dir;

        if (! is_dir($searchDir)) {
            return null;
        }

        $patterns = [
            "fs_{$sourceName}_*.tar.gz",
            "fs_{$sourceName}_*.zip",
            "fs_{$sourceName}_*.tar",
        ];

        foreach ($patterns as $pattern) {
            $files = glob("{$searchDir}/{$pattern}");
            if (! empty($files)) {
                return $files[0];
            }
        }

        $files = glob("{$searchDir}/*");

        return ! empty($files) ? $files[0] : null;
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
