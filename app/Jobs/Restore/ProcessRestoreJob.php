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

                $chainTmpDir = $tmpDir.'/chain_'.$chainLog->id;
                @mkdir($chainTmpDir, 0755, true);

                // Resolves a single dump on demand and downloads only that one, so a
                // db_only restore never pulls the filesystem archives.
                $fetch = $this->artifactFetcher($chainLog, $destination, $chainTmpDir, $sourceConfig, $s3Service, $ftpService);

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

                        $dumpFile = $fetch('mysql', $db);

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

                        $dumpFile = $fetch('postgres', $db);

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

                        $archiveFile = $fetch('mongodb', $db);

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

                        $archiveFile = $fetch('filesystem', $path);

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
     * Build a resolver that returns the local path of one dump, downloading it on demand.
     *
     * Backups taken after the artifact split store every dump as its own object and list
     * them in meta.artifacts, so only the requested one is fetched. Older backups are a
     * single object (possibly a multi-type package): those are downloaded and extracted
     * once, lazily, on the first lookup.
     *
     * @return \Closure(string, string): ?string (type, database name or path) => local path
     */
    protected function artifactFetcher(
        BackupLog $backupLog,
        $destination,
        string $tmpDir,
        array $sourceConfig,
        S3StorageService $s3Service,
        FtpStorageService $ftpService,
    ): \Closure {
        $artifacts = $backupLog->meta['artifacts'] ?? null;

        if (! empty($artifacts)) {
            return function (string $type, string $key) use ($artifacts, $destination, $tmpDir, $s3Service, $ftpService): ?string {
                $artifact = $this->matchArtifact($artifacts, $type, $key);

                if (! $artifact) {
                    return null;
                }

                $localPath = $tmpDir.'/'.$type.'-'.basename($artifact['file_name']);
                $this->downloadObject($destination, $artifact['storage_path'], $localPath, $s3Service, $ftpService);

                return $localPath;
            };
        }

        $legacyDir = null;
        $isPackage = null;

        return function (string $type, string $key) use (&$legacyDir, &$isPackage, $backupLog, $destination, $tmpDir, $sourceConfig, $s3Service, $ftpService): ?string {
            if ($legacyDir === null) {
                [$legacyDir, $isPackage] = $this->prepareLegacyBackup($backupLog, $destination, $tmpDir, $sourceConfig, $s3Service, $ftpService);
            }

            return $this->findLegacyFile($legacyDir, $isPackage, $type, $key);
        };
    }

    /**
     * Pick the artifact for a database name / filesystem path. Falls back to the only
     * artifact of that type when the key does not match (source renamed after the backup),
     * but never guesses between several candidates.
     */
    protected function matchArtifact(array $artifacts, string $type, string $key): ?array
    {
        $ofType = array_values(array_filter($artifacts, fn ($a) => ($a['type'] ?? null) === $type && ! empty($a['storage_path'])));

        foreach ($ofType as $artifact) {
            if (($artifact['key'] ?? null) === $key) {
                return $artifact;
            }
        }

        return count($ofType) === 1 ? $ofType[0] : null;
    }

    /**
     * Download the single object of a pre-split backup and extract it when it is a
     * multi-type package. Returns [directory to search, is package].
     */
    protected function prepareLegacyBackup(
        BackupLog $backupLog,
        $destination,
        string $tmpDir,
        array $sourceConfig,
        S3StorageService $s3Service,
        FtpStorageService $ftpService,
    ): array {
        $remotePath = $backupLog->storage_path;
        $localPath = $tmpDir.'/'.($backupLog->file_name ?? basename($remotePath));

        $this->downloadObject($destination, $remotePath, $localPath, $s3Service, $ftpService);

        if (! $this->isPackageArchive($backupLog, $sourceConfig)) {
            return [$tmpDir, false];
        }

        $extractedDir = $tmpDir.'/extracted';
        @mkdir($extractedDir, 0755, true);
        $this->extractPackage($localPath, $extractedDir);

        return [$extractedDir, true];
    }

    /**
     * Download one remote object to a local path.
     */
    protected function downloadObject(
        $destination,
        string $remotePath,
        string $localPath,
        S3StorageService $s3Service,
        FtpStorageService $ftpService,
    ): void {
        @mkdir(dirname($localPath), 0755, true);

        match ($destination->type) {
            's3' => $this->downloadFromS3($s3Service, $destination->config, $remotePath, $localPath),
            'ftp' => $this->downloadFromFtp($ftpService, $destination->config, $remotePath, $localPath),
            'local' => $this->downloadFromLocal($destination->config, $remotePath, $localPath),
            default => throw new \RuntimeException("Unsupported destination type: {$destination->type}"),
        };

        if (! file_exists($localPath)) {
            throw new \RuntimeException("Failed to download backup file to: {$localPath}");
        }
    }

    /**
     * Download from S3 to local file.
     */
    protected function downloadFromS3(S3StorageService $s3Service, array $config, string $remotePath, string $localPath): void
    {
        // Not readStream(): Guzzle's curl handler ignores the SDK's `stream` option and always
        // writes the body into a sink, defaulting to php://temp — which spills to sys_temp_dir
        // (a tmpfs in our containers) and fills it on multi-GB archives. SaveAs points curl's
        // sink straight at the destination file, so nothing is buffered.
        $s3Service->disk($config)->getClient()->getObject([
            'Bucket' => $config['bucket'],
            'Key' => $remotePath,
            'SaveAs' => $localPath,
        ]);
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
     * Whether a pre-split backup is a multi-type package archive.
     *
     * The backup job wrote meta.files only for packages, so that key is an exact marker.
     * Without it (very old logs) fall back to counting the source's configured types —
     * note this reads today's config, which is why the marker is preferred.
     */
    protected function isPackageArchive(BackupLog $backupLog, array $sourceConfig): bool
    {
        if (isset($backupLog->meta['files'])) {
            return true;
        }

        if (isset($backupLog->meta['database']) || isset($backupLog->meta['source_path'])) {
            return false;
        }

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
     * Locate the dump for one database / path inside a pre-split backup.
     *
     * Incremental dumps carry an _incr prefix, so both spellings are matched: picking the
     * wrong file here used to restore one database's dump into another. When no pattern
     * matches, the single-candidate fallback only fires if there is exactly one file to
     * choose from — guessing between several is what corrupted restores.
     */
    protected function findLegacyFile(string $dir, bool $isPackage, string $type, string $key): ?string
    {
        $searchDir = $isPackage ? $dir.'/'.$type : $dir;

        if (! is_dir($searchDir)) {
            return null;
        }

        // Filesystem archives are named after the path's basename, plus a transport
        // suffix (_ssh, _incr, _ssh_incr) that the trailing wildcard absorbs.
        $name = $type === 'filesystem' ? basename(rtrim($key, '/')) : $key;
        $prefix = $type === 'filesystem' ? 'fs' : $type;

        foreach (["{$prefix}_{$name}_*", "{$prefix}_incr_{$name}_*"] as $pattern) {
            $files = glob("{$searchDir}/{$pattern}");
            if (! empty($files)) {
                sort($files);

                return $files[0];
            }
        }

        $candidates = array_values(array_filter(
            glob("{$searchDir}/*") ?: [],
            fn ($f) => is_file($f) && ! str_starts_with(basename($f), '.'),
        ));

        return count($candidates) === 1 ? $candidates[0] : null;
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
