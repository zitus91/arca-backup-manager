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
use App\Services\Restore\PostgresRestoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

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
        PostgresRestoreService $postgresRestore,
        MongodbRestoreService $mongodbRestore,
        FilesystemRestoreService $filesystemRestore,
        S3StorageService $s3Service,
        FtpStorageService $ftpService,
    ): void {
        $restoreLog = RestoreLog::with([
            'backupLog.job.source.mysqlHost',
            'backupLog.job.source.postgresHost',
            'backupLog.job.source.mongodbHost',
            'backupLog.job.source.filesystemHost',
            'backupLog.job.destination',
        ])->findOrFail($this->restoreLogId);

        $backupLog = $restoreLog->backupLog;
        $backupJob = $backupLog->job;
        $source = $backupJob->source;
        $destination = $backupJob->destination;

        // Read new configuration options
        $restoreTarget = $restoreLog->restore_target ?? 'same_host';
        $remoteHostConfig = $restoreLog->remote_host_config ?? [];
        $customNames = $restoreLog->custom_names ?? [];
        $overrideExisting = $restoreLog->override_existing ?? false;

        // Build the chain of backup logs to restore (for incremental backups)
        $backupChain = $this->buildRestoreChain($backupLog);

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

        $tmpDir = storage_path('app/restores/tmp/'.$restoreLog->id);
        @mkdir($tmpDir, 0755, true);

        try {
            // 2. Download and restore each backup in the chain (full first, then incrementals)
            $sourceConfig = $source->config;
            $restoreType = $restoreLog->restore_type;
            $selectedItems = $restoreLog->selected_items;
            $results = [];
            $restoredDbNames = [];
            $restoredPaths = [];

            foreach ($backupChain as $chainIndex => $chainLog) {
                $isFirstInChain = $chainIndex === 0;

                // Download this backup file
                $chainTmpDir = $tmpDir.'/chain_'.$chainLog->id;
                @mkdir($chainTmpDir, 0755, true);

                $localFilePath = $this->downloadBackupFile(
                    $destination,
                    $chainLog,
                    $chainTmpDir,
                    $s3Service,
                    $ftpService,
                );

                // Determine if it's a multi-type package archive
                $isPackage = $this->isPackageArchive($chainLog, $sourceConfig);

                if ($isPackage) {
                    $extractedDir = $chainTmpDir.'/extracted';
                    @mkdir($extractedDir, 0755, true);
                    $this->extractPackage($localFilePath, $extractedDir);
                }

                // For incremental restores after the first (full): always override
                // because we're applying deltas on top of the base
                $stepOverride = $isFirstInChain ? $overrideExisting : true;

                // 4. Restore MySQL if applicable
                if (in_array($restoreType, ['db_only', 'full']) && $source->mysql_host_id) {
                    $mysqlHost = $source->mysqlHost;
                    $mysqlConf = array_merge($mysqlHost->config['mysql'] ?? [], ['ssh' => $mysqlHost->usesSshFor('mysql') ? $mysqlHost->sshConfig() : ['enabled' => false]], $sourceConfig['mysql'] ?? []);
                    $databases = $mysqlConf['databases'] ?? (isset($mysqlConf['database']) ? [$mysqlConf['database']] : []);

                    // Filter by selected items if specified
                    if (! empty($selectedItems['mysql_databases'])) {
                        $databases = array_intersect($databases, $selectedItems['mysql_databases']);
                    }

                    foreach ($databases as $db) {
                        $singleConf = array_merge($mysqlConf, ['database' => $db]);

                        // Restore target decides the connection: same_host keeps the
                        // source host's ssh tunnel (already in $singleConf); remote_host
                        // uses the explicit override, or connects directly (no tunnel)
                        // when no override is given — never leak the source host's ssh.
                        if ($restoreTarget === 'remote_host') {
                            if (! empty($remoteHostConfig['mysql'])) {
                                $singleConf = array_merge($singleConf, $remoteHostConfig['mysql']);
                            } else {
                                $singleConf['ssh'] = ['enabled' => false];
                            }
                        }

                        // Get custom target name if specified
                        $targetDbName = $customNames['databases'][$db] ?? null;

                        $dumpFile = $this->findMysqlDump($isPackage ? $extractedDir : $chainTmpDir, $db, $isPackage);

                        if ($dumpFile) {
                            $r = $mysqlRestore->restore($singleConf, $dumpFile, $targetDbName, $stepOverride);
                            $results[] = $r;
                            $restoredDbNames[] = $r['restored_db_name'];
                        }
                    }
                }

                // 5. Restore PostgreSQL if applicable
                if (in_array($restoreType, ['db_only', 'full']) && $source->postgres_host_id) {
                    $postgresHost = $source->postgresHost;
                    $postgresConf = array_merge($postgresHost->config['postgres'] ?? [], ['ssh' => $postgresHost->usesSshFor('postgres') ? $postgresHost->sshConfig() : ['enabled' => false]], $sourceConfig['postgres'] ?? []);
                    $databases = $postgresConf['databases'] ?? (isset($postgresConf['database']) ? [$postgresConf['database']] : []);

                    // Filter by selected items if specified
                    if (! empty($selectedItems['postgres_databases'])) {
                        $databases = array_intersect($databases, $selectedItems['postgres_databases']);
                    }

                    foreach ($databases as $db) {
                        $singleConf = array_merge($postgresConf, ['database' => $db]);

                        // See MySQL block: never carry the source host's ssh into a
                        // remote_host restore without an explicit override.
                        if ($restoreTarget === 'remote_host') {
                            if (! empty($remoteHostConfig['postgres'])) {
                                $singleConf = array_merge($singleConf, $remoteHostConfig['postgres']);
                            } else {
                                $singleConf['ssh'] = ['enabled' => false];
                            }
                        }

                        // Get custom target name if specified
                        $targetDbName = $customNames['databases'][$db] ?? null;

                        $dumpFile = $this->findPostgresDump($isPackage ? $extractedDir : $chainTmpDir, $db, $isPackage);

                        if ($dumpFile) {
                            $r = $postgresRestore->restore($singleConf, $dumpFile, $targetDbName, $stepOverride);
                            $results[] = $r;
                            $restoredDbNames[] = $r['restored_db_name'];
                        }
                    }
                }

                // 6. Restore MongoDB if applicable
                if (in_array($restoreType, ['db_only', 'full']) && $source->mongodb_host_id) {
                    $mongodbHost = $source->mongodbHost;
                    $mongoConf = array_merge($mongodbHost->config['mongodb'] ?? [], ['ssh' => $mongodbHost->usesSshFor('mongodb') ? $mongodbHost->sshConfig() : ['enabled' => false]], $sourceConfig['mongodb'] ?? []);
                    $databases = $mongoConf['databases'] ?? (isset($mongoConf['database']) ? [$mongoConf['database']] : []);

                    // Filter by selected items if specified
                    if (! empty($selectedItems['mongodb_databases'])) {
                        $databases = array_intersect($databases, $selectedItems['mongodb_databases']);
                    }

                    foreach ($databases as $db) {
                        $singleConf = array_merge($mongoConf, ['database' => $db]);

                        // See MySQL block: never carry the source host's ssh into a
                        // remote_host restore without an explicit override.
                        if ($restoreTarget === 'remote_host') {
                            if (! empty($remoteHostConfig['mongodb'])) {
                                $singleConf = array_merge($singleConf, $remoteHostConfig['mongodb']);
                            } else {
                                $singleConf['ssh'] = ['enabled' => false];
                            }
                        }

                        // Get custom target name if specified
                        $targetDbName = $customNames['databases'][$db] ?? null;

                        $archiveFile = $this->findMongoArchive($isPackage ? $extractedDir : $chainTmpDir, $db, $isPackage);

                        if ($archiveFile) {
                            $r = $mongodbRestore->restore($singleConf, $archiveFile, $targetDbName, $stepOverride);
                            $results[] = $r;
                            $restoredDbNames[] = $r['restored_db_name'];
                        }
                    }
                }

                // 7. Restore Filesystem if applicable
                if (in_array($restoreType, ['files_only', 'full']) && $source->filesystem_host_id) {
                    $filesystemHost = $source->filesystemHost;
                    $fsConf = array_merge($filesystemHost->config['filesystem'] ?? [], ['ssh' => $filesystemHost->sshConfig()], $sourceConfig['filesystem'] ?? []);
                    $paths = $fsConf['paths'] ?? (isset($fsConf['path']) ? [$fsConf['path']] : []);

                    // Filter by selected items if specified
                    if (! empty($selectedItems['filesystem_paths'])) {
                        $paths = array_intersect($paths, $selectedItems['filesystem_paths']);
                    }

                    foreach ($paths as $path) {
                        $singleConf = [
                            'path' => $path,
                            'exclude_patterns' => $fsConf['exclude_patterns'] ?? [],
                            'ssh' => $fsConf['ssh'],
                            'transport' => $filesystemHost->filesystemTransport(),
                            'ftp' => $filesystemHost->filesystemFtpConfig() ?? [],
                        ];

                        // same_host keeps the source host's ssh/ftp transport (set above); remote_host
                        // uses the override, or no tunnel when none is given, and never pushes to the
                        // source's own FTP host.
                        if ($restoreTarget === 'remote_host') {
                            $singleConf['ssh'] = ! empty($remoteHostConfig['filesystem'])
                                ? $remoteHostConfig['filesystem']
                                : ['enabled' => false];
                            $singleConf['transport'] = 'ssh';
                        }

                        // Get custom target path if specified
                        $targetPath = $customNames['paths'][$path] ?? null;

                        $archiveFile = $this->findFilesystemArchive($isPackage ? $extractedDir : $chainTmpDir, basename($path), $isPackage);

                        if ($archiveFile) {
                            $r = $filesystemRestore->restore($singleConf, $archiveFile, $targetPath, $stepOverride);
                            $results[] = $r;
                            $restoredPaths[] = $r['restored_path'];
                        }
                    }
                }

            } // end foreach backupChain

            if (empty($results)) {
                throw new \RuntimeException('No matching backup data found for the requested restore type.');
            }

            // 7. Update restore log with success
            $restoreLog->update([
                'status' => 'success',
                'finished_at' => now(),
                'duration_seconds' => now()->diffInSeconds($restoreLog->started_at),
                'restored_db_name' => ! empty($restoredDbNames) ? implode(', ', array_unique($restoredDbNames)) : null,
                'restored_path' => ! empty($restoredPaths) ? implode(', ', array_unique($restoredPaths)) : null,
                'meta' => [
                    'restore_type' => $restoreType,
                    'restore_target' => $restoreTarget,
                    'override_existing' => $overrideExisting,
                    'results' => $results,
                    'backup_chain_count' => count($backupChain),
                ],
            ]);

            event(new RestoreJobCompleted(
                restoreLogId: $restoreLog->id,
                backupJobName: $backupJob->name,
                status: 'success',
            ));

        } catch (\Throwable $e) {
            $error = $this->describeError($e);

            Log::error('Restore failed', [
                'restore_log_id' => $restoreLog->id,
                'backup_log_id' => $backupLog->id,
                'error' => $error,
            ]);

            $restoreLog->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => $restoreLog->started_at
                    ? now()->diffInSeconds($restoreLog->started_at)
                    : 0,
                'error_message' => $error,
            ]);

            event(new RestoreJobCompleted(
                restoreLogId: $restoreLog->id,
                backupJobName: $backupJob->name,
                status: 'failed',
                errorMessage: $error,
            ));
        } finally {
            $this->cleanupTempDir($tmpDir);
        }
    }

    /**
     * Flatten the exception chain: Flysystem/AWS wrap the useful message (403, bad region,
     * missing key) in a previous exception, and only the generic wrapper was being stored.
     */
    protected function describeError(\Throwable $e): string
    {
        $messages = [];

        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            $messages[] = trim($current->getMessage());
        }

        return Str::limit(implode(' — caused by: ', array_unique(array_filter($messages))), 2000);
    }

    /**
     * Build the ordered chain of backup logs needed to restore from an incremental backup.
     * Returns [full_backup, incremental_1, incremental_2, ..., target_backup].
     * For full backups, returns just the single log.
     */
    protected function buildRestoreChain(BackupLog $targetLog): array
    {
        // If the target is a full backup, just return it
        if ($targetLog->is_full) {
            return [$targetLog];
        }

        // Find the parent full backup
        $fullLog = $targetLog->parent_backup_log_id
            ? BackupLog::find($targetLog->parent_backup_log_id)
            : null;

        if (! $fullLog) {
            // No parent found: treat this log as standalone
            return [$targetLog];
        }

        // Get all incremental backups between the full and the target, ordered chronologically
        $incrementals = BackupLog::where('backup_job_id', $targetLog->backup_job_id)
            ->where('status', 'success')
            ->where('is_full', false)
            ->where('parent_backup_log_id', $fullLog->id)
            ->where('started_at', '<=', $targetLog->started_at)
            ->orderBy('started_at')
            ->get()
            ->all();

        return array_merge([$fullLog], $incrementals);
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
        $localPath = $tmpDir.'/'.$fileName;

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
        $stream = $s3Service->disk($config)->readStream($remotePath);

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
        $stream = $ftpService->disk($config)->readStream($remotePath);

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
        $fullPath = $basePath.'/'.$remotePath;

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
        $types = array_intersect(array_keys($sourceConfig), ['mysql', 'postgres', 'mongodb', 'filesystem']);

        return count($types) > 1;
    }

    /**
     * Extract a package (multi-type) archive.
     */
    protected function extractPackage(string $archivePath, string $extractDir): void
    {
        if (str_ends_with($archivePath, '.tar.gz') || str_ends_with($archivePath, '.tgz')) {
            $cmd = 'tar -xzf '.escapeshellarg($archivePath).' -C '.escapeshellarg($extractDir);
        } elseif (str_ends_with($archivePath, '.zip')) {
            $cmd = 'unzip -o '.escapeshellarg($archivePath).' -d '.escapeshellarg($extractDir);
        } else {
            $cmd = 'tar -xf '.escapeshellarg($archivePath).' -C '.escapeshellarg($extractDir);
        }

        $result = Process::timeout(config('backup.process_timeout'))->run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to extract backup package: '.$result->errorOutput());
        }
    }

    /**
     * Find the MySQL dump file in the extracted directory.
     */
    protected function findMysqlDump(string $dir, string $dbName, bool $isPackage): ?string
    {
        // In package: look under mysql/ subdirectory
        $searchDir = $isPackage ? $dir.'/mysql' : $dir;

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
    protected function findPostgresDump(string $dir, string $dbName, bool $isPackage): ?string
    {
        // In package: look under postgres/ subdirectory
        $searchDir = $isPackage ? $dir.'/postgres' : $dir;

        if (! is_dir($searchDir)) {
            return null;
        }

        // Find any postgres dump file matching this database
        $patterns = [
            "postgres_{$dbName}_*.sql.gz",
            "postgres_{$dbName}_*.sql.zip",
            "postgres_{$dbName}_*.sql",
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

    protected function findMongoArchive(string $dir, string $dbName, bool $isPackage): ?string
    {
        $searchDir = $isPackage ? $dir.'/mongodb' : $dir;

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
        $searchDir = $isPackage ? $dir.'/filesystem' : $dir;

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
