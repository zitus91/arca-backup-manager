<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;
use App\Services\Backup\FtpStorageService;

class FilesystemBackupService
{
    /**
     * Create a backup of a filesystem path.
     * If $config['ssh'] is set and enabled, uses rsync over SSH.
     */
    public function backup(array $config, string $outputPath, string $compression = 'gzip'): array
    {
        $sourcePath = $config['path'];
        $excludePatterns = $config['exclude_patterns'] ?? [];
        $ssh = $config['ssh'] ?? null;

        if (($config['transport'] ?? 'ssh') === 'ftp') {
            return $this->backupViaFtp($config, $sourcePath, $excludePatterns, $outputPath, $compression);
        }

        if ($ssh && ! empty($ssh['enabled']) && ! empty($ssh['host'])) {
            return $this->backupViaSsh($ssh, $sourcePath, $excludePatterns, $outputPath, $compression);
        }

        if (! is_dir($sourcePath) && ! is_file($sourcePath)) {
            throw new \RuntimeException("Source path does not exist: {$sourcePath}");
        }

        $fileName = $this->generateFileName(basename($sourcePath), $compression);
        $fullPath = rtrim($outputPath, '/').'/'.$fileName;

        $command = $this->buildCommand($sourcePath, $fullPath, $compression, $excludePatterns);

        $result = Process::timeout(3600)->run($command);

        if (! $result->successful()) {
            throw new \RuntimeException('Filesystem backup failed: '.$result->errorOutput());
        }

        $fileCount = $this->countFiles($sourcePath, $excludePatterns);

        $meta = [
            'source_path' => $sourcePath,
            'exclude_patterns' => $excludePatterns,
            'files_count' => $fileCount,
        ];

        return [
            'file_name' => $fileName,
            'file_path' => $fullPath,
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'meta' => $meta,
        ];
    }

    /**
     * Create an incremental backup: only files modified since the checkpoint timestamp.
     */
    public function incrementalBackup(array $config, string $outputPath, string $compression = 'gzip', ?array $checkpoint = null): array
    {
        $sourcePath = $config['path'];
        $excludePatterns = $config['exclude_patterns'] ?? [];
        $ssh = $config['ssh'] ?? null;

        if (($config['transport'] ?? 'ssh') === 'ftp') {
            $r = $this->backupViaFtp($config, $sourcePath, $excludePatterns, $outputPath, $compression);
            $r['meta']['incremental'] = false;
            $r['meta']['note'] = 'Incremental not available over FTP; full backup performed.';

            return $r;
        }

        if ($ssh && ! empty($ssh['enabled']) && ! empty($ssh['host'])) {
            return $this->incrementalBackupViaSsh($ssh, $sourcePath, $excludePatterns, $outputPath, $compression, $checkpoint);
        }

        if (! is_dir($sourcePath) && ! is_file($sourcePath)) {
            throw new \RuntimeException("Source path does not exist: {$sourcePath}");
        }

        $since = $checkpoint['timestamp'] ?? null;
        $baseName = basename($sourcePath);
        $fileName = $this->generateFileName($baseName.'_incr', $compression);
        $fullPath = rtrim($outputPath, '/').'/'.$fileName;

        // Create a reference timestamp file to use with find -newer
        $refFile = rtrim($outputPath, '/').'/.incr_reference';
        if ($since) {
            touch($refFile, strtotime($since));
        }

        // Build a file list of changed files since checkpoint
        $fileListPath = rtrim($outputPath, '/').'/.incr_filelist.txt';
        $findCmd = 'find '.escapeshellarg($sourcePath).' -type f';
        if ($since && file_exists($refFile)) {
            $findCmd .= ' -newer '.escapeshellarg($refFile);
        }
        foreach ($excludePatterns as $pattern) {
            $findCmd .= ' ! -path '.escapeshellarg($pattern);
        }
        $findCmd .= ' > '.escapeshellarg($fileListPath);

        Process::timeout(300)->run($findCmd);

        $changedFiles = file_exists($fileListPath) ? array_filter(file($fileListPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];

        @unlink($refFile);

        if (empty($changedFiles)) {
            // No changes: create a minimal marker archive
            file_put_contents($fileListPath, '');
            $cmd = match ($compression) {
                'gzip' => 'tar -czf '.escapeshellarg($fullPath).' -T /dev/null --files-from=/dev/null 2>/dev/null; touch '.escapeshellarg($fullPath),
                'zip' => 'echo "no changes" | zip '.escapeshellarg($fullPath).' -',
                default => 'tar -cf '.escapeshellarg($fullPath).' -T /dev/null --files-from=/dev/null 2>/dev/null; touch '.escapeshellarg($fullPath),
            };
            Process::run($cmd);
            @unlink($fileListPath);

            return [
                'file_name' => $fileName,
                'file_path' => $fullPath,
                'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                'meta' => [
                    'source_path' => $sourcePath,
                    'exclude_patterns' => $excludePatterns,
                    'files_count' => 0,
                    'incremental' => true,
                    'no_changes' => true,
                ],
                'incremental_checkpoint' => [
                    'type' => 'filesystem',
                    'path' => $sourcePath,
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        }

        // Archive only changed files
        $cmd = match ($compression) {
            'gzip' => 'tar -czf '.escapeshellarg($fullPath).' -T '.escapeshellarg($fileListPath),
            'zip' => 'cat '.escapeshellarg($fileListPath).' | zip '.escapeshellarg($fullPath).' -@',
            default => 'tar -cf '.escapeshellarg($fullPath).' -T '.escapeshellarg($fileListPath),
        };

        $result = Process::timeout(3600)->run($cmd);
        @unlink($fileListPath);

        if (! $result->successful()) {
            throw new \RuntimeException('Incremental filesystem backup failed: '.$result->errorOutput());
        }

        return [
            'file_name' => $fileName,
            'file_path' => $fullPath,
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'meta' => [
                'source_path' => $sourcePath,
                'exclude_patterns' => $excludePatterns,
                'files_count' => count($changedFiles),
                'incremental' => true,
            ],
            'incremental_checkpoint' => [
                'type' => 'filesystem',
                'path' => $sourcePath,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Incremental backup via SSH: rsync only changed files since checkpoint.
     */
    protected function incrementalBackupViaSsh(array $ssh, string $remotePath, array $excludePatterns, string $outputPath, string $compression, ?array $checkpoint): array
    {
        $tunnelService = app(SshTunnelService::class);
        $sshOptions = $tunnelService->buildSshOptions($ssh);

        $localRsyncDir = rtrim($outputPath, '/').'/rsync_incr_'.now()->format('Ymd_His');
        @mkdir($localRsyncDir, 0755, true);

        // Build exclude flags for rsync
        $excludes = '';
        foreach ($excludePatterns as $pattern) {
            $excludes .= ' --exclude='.escapeshellarg($pattern);
        }

        $remoteSource = sprintf(
            '%s@%s:%s',
            escapeshellarg($ssh['user']),
            escapeshellarg($ssh['host']),
            escapeshellarg(rtrim($remotePath, '/').'/')
        );

        // For incremental: only transfer files newer than checkpoint
        $since = $checkpoint['timestamp'] ?? null;
        $newerFilter = '';
        if ($since) {
            $refFile = rtrim($outputPath, '/').'/.incr_ssh_reference';
            touch($refFile, strtotime($since));
            // rsync doesn't have a native --newer flag, but we can use --update combined
            // with a filter. The simplest approach: rsync with --update to get only changed files.
            $newerFilter = ' --update';
            @unlink($refFile);
        }

        $rsyncCmd = sprintf(
            'rsync -az --ignore-errors -e %s %s %s %s %s/',
            escapeshellarg($sshOptions),
            $excludes,
            $newerFilter,
            $remoteSource,
            escapeshellarg($localRsyncDir)
        );

        $result = Process::timeout(7200)->run($rsyncCmd);

        $exitCode = $result->exitCode();
        $rsyncWarnings = null;

        if (! $result->successful() && ! in_array($exitCode, [23, 24])) {
            Process::run('rm -rf '.escapeshellarg($localRsyncDir));
            throw new \RuntimeException('SSH incremental filesystem backup (rsync) failed: '.$result->errorOutput());
        }

        if (in_array($exitCode, [23, 24])) {
            $rsyncWarnings = trim($result->errorOutput());
        }

        $baseName = basename(rtrim($remotePath, '/'));
        $fileName = $this->generateFileName($baseName.'_ssh_incr', $compression);
        $fullPath = rtrim($outputPath, '/').'/'.$fileName;

        $archiveCmd = match ($compression) {
            'gzip' => 'tar -czf '.escapeshellarg($fullPath).' -C '.escapeshellarg(dirname($localRsyncDir)).' '.escapeshellarg(basename($localRsyncDir)),
            'zip' => 'cd '.escapeshellarg(dirname($localRsyncDir)).' && zip -r '.escapeshellarg($fullPath).' '.escapeshellarg(basename($localRsyncDir)),
            default => 'tar -cf '.escapeshellarg($fullPath).' -C '.escapeshellarg(dirname($localRsyncDir)).' '.escapeshellarg(basename($localRsyncDir)),
        };

        $archiveResult = Process::timeout(3600)->run($archiveCmd);
        Process::run('rm -rf '.escapeshellarg($localRsyncDir));

        if (! $archiveResult->successful()) {
            throw new \RuntimeException('SSH incremental backup archiving failed: '.$archiveResult->errorOutput());
        }

        return [
            'file_name' => $fileName,
            'file_path' => $fullPath,
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'meta' => [
                'source_path' => $remotePath,
                'ssh_host' => $ssh['host'],
                'exclude_patterns' => $excludePatterns,
                'via_ssh' => true,
                'incremental' => true,
                'rsync_warnings' => $rsyncWarnings,
            ],
            'incremental_checkpoint' => [
                'type' => 'filesystem',
                'path' => $remotePath,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Backup a remote filesystem path via rsync over SSH, then compress locally.
     */
    protected function backupViaSsh(array $ssh, string $remotePath, array $excludePatterns, string $outputPath, string $compression): array
    {
        $tunnelService = app(SshTunnelService::class);
        $sshOptions = $tunnelService->buildSshOptions($ssh);

        $localRsyncDir = rtrim($outputPath, '/').'/rsync_'.now()->format('Ymd_His');
        @mkdir($localRsyncDir, 0755, true);

        // Build exclude flags for rsync
        $excludes = '';
        foreach ($excludePatterns as $pattern) {
            $excludes .= ' --exclude='.escapeshellarg($pattern);
        }

        $remoteSource = sprintf(
            '%s@%s:%s',
            escapeshellarg($ssh['user']),
            escapeshellarg($ssh['host']),
            escapeshellarg(rtrim($remotePath, '/').'/')
        );

        $rsyncCmd = sprintf(
            'rsync -az --ignore-errors -e %s %s %s %s/',
            escapeshellarg($sshOptions),
            $excludes,
            $remoteSource,
            escapeshellarg($localRsyncDir)
        );

        $result = Process::timeout(7200)->run($rsyncCmd);

        // Exit code 23 = partial transfer due to permission/read errors (non-fatal)
        // Exit code 24 = partial transfer due to vanished source files (non-fatal)
        $exitCode = $result->exitCode();
        $rsyncWarnings = null;

        if (! $result->successful() && ! in_array($exitCode, [23, 24])) {
            Process::run('rm -rf '.escapeshellarg($localRsyncDir));
            throw new \RuntimeException('SSH filesystem backup (rsync) failed: '.$result->errorOutput());
        }

        if (in_array($exitCode, [23, 24])) {
            $rsyncWarnings = trim($result->errorOutput());
        }

        $baseName = basename(rtrim($remotePath, '/'));
        $fileName = $this->generateFileName($baseName.'_ssh', $compression);
        $fullPath = rtrim($outputPath, '/').'/'.$fileName;

        $archiveCmd = match ($compression) {
            'gzip' => 'tar -czf '.escapeshellarg($fullPath).' -C '.escapeshellarg(dirname($localRsyncDir)).' '.escapeshellarg(basename($localRsyncDir)),
            'zip' => 'cd '.escapeshellarg(dirname($localRsyncDir)).' && zip -r '.escapeshellarg($fullPath).' '.escapeshellarg(basename($localRsyncDir)),
            default => 'tar -cf '.escapeshellarg($fullPath).' -C '.escapeshellarg(dirname($localRsyncDir)).' '.escapeshellarg(basename($localRsyncDir)),
        };

        $archiveResult = Process::timeout(3600)->run($archiveCmd);
        Process::run('rm -rf '.escapeshellarg($localRsyncDir));

        if (! $archiveResult->successful()) {
            throw new \RuntimeException('SSH filesystem backup archiving failed: '.$archiveResult->errorOutput());
        }

        $meta = [
            'source_path' => $remotePath,
            'ssh_host' => $ssh['host'],
            'exclude_patterns' => $excludePatterns,
            'via_ssh' => true,
            'rsync_warnings' => $rsyncWarnings,
        ];

        return [
            'file_name' => $fileName,
            'file_path' => $fullPath,
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'meta' => $meta,
        ];
    }

    /**
     * Backup a filesystem path over FTP: mirror the remote tree down to a
     * local staging dir, then archive with the same tar/zip logic as SSH.
     */
    protected function backupViaFtp(array $config, string $sourcePath, array $excludePatterns, string $outputPath, string $compression): array
    {
        $ftp = $config['ftp'] ?? [];
        $localDir = rtrim($outputPath, '/').'/ftp_'.now()->format('Ymd_His');
        @mkdir($localDir, 0755, true);

        try {
            $fileCount = app(FtpStorageService::class)->mirrorDown($ftp, $sourcePath, $localDir, $excludePatterns);
        } catch (\Throwable $e) {
            Process::run('rm -rf '.escapeshellarg($localDir));
            throw $e;
        }

        $fileName = $this->generateFileName(basename(rtrim($sourcePath, '/')) ?: 'ftp', $compression);
        $fullPath = rtrim($outputPath, '/').'/'.$fileName;

        $archiveCmd = match ($compression) {
            'gzip' => 'tar -czf '.escapeshellarg($fullPath).' -C '.escapeshellarg(dirname($localDir)).' '.escapeshellarg(basename($localDir)),
            'zip' => 'cd '.escapeshellarg(dirname($localDir)).' && zip -r '.escapeshellarg($fullPath).' '.escapeshellarg(basename($localDir)),
            default => 'tar -cf '.escapeshellarg($fullPath).' -C '.escapeshellarg(dirname($localDir)).' '.escapeshellarg(basename($localDir)),
        };
        $res = Process::timeout(3600)->run($archiveCmd);
        Process::run('rm -rf '.escapeshellarg($localDir));
        if (! $res->successful()) {
            throw new \RuntimeException('FTP filesystem backup archiving failed: '.$res->errorOutput());
        }

        return [
            'file_name' => $fileName,
            'file_path' => $fullPath,
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'meta' => ['source_path' => $sourcePath, 'exclude_patterns' => $excludePatterns, 'files_count' => $fileCount, 'via_ftp' => true],
        ];
    }

    /**
     * Build the tar/zip command with exclusions.
     */
    protected function buildCommand(string $sourcePath, string $outputPath, string $compression, array $excludePatterns): string
    {
        $excludes = '';
        foreach ($excludePatterns as $pattern) {
            $excludes .= ' --exclude='.escapeshellarg($pattern);
        }

        return match ($compression) {
            'gzip' => 'tar -czf '.escapeshellarg($outputPath).$excludes.' -C '.escapeshellarg(dirname($sourcePath)).' '.escapeshellarg(basename($sourcePath)),
            'zip' => 'cd '.escapeshellarg(dirname($sourcePath)).' && zip -r '.escapeshellarg($outputPath).' '.escapeshellarg(basename($sourcePath)).$this->zipExcludes($excludePatterns),
            default => 'tar -cf '.escapeshellarg($outputPath).$excludes.' -C '.escapeshellarg(dirname($sourcePath)).' '.escapeshellarg(basename($sourcePath)),
        };
    }

    /**
     * Convert exclude patterns to zip -x flags.
     */
    protected function zipExcludes(array $patterns): string
    {
        if (empty($patterns)) {
            return '';
        }

        $excludes = ' -x';
        foreach ($patterns as $pattern) {
            $excludes .= ' '.escapeshellarg($pattern);
        }

        return $excludes;
    }

    /**
     * Count files in source (approximate).
     */
    protected function countFiles(string $path, array $excludePatterns): int
    {
        try {
            $result = Process::run('find '.escapeshellarg($path).' -type f | wc -l');

            return (int) trim($result->output());
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Generate a timestamped file name.
     */
    protected function generateFileName(string $sourceName, string $compression): string
    {
        $timestamp = now()->format('Ymd_His');
        $ext = match ($compression) {
            'gzip' => '.tar.gz',
            'zip' => '.zip',
            default => '.tar',
        };

        return "fs_{$sourceName}_{$timestamp}{$ext}";
    }
}
