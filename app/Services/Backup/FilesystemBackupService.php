<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;

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

        if ($ssh && ! empty($ssh['enabled']) && ! empty($ssh['host'])) {
            return $this->backupViaSsh($ssh, $sourcePath, $excludePatterns, $outputPath, $compression);
        }

        if (! is_dir($sourcePath) && ! is_file($sourcePath)) {
            throw new \RuntimeException("Source path does not exist: {$sourcePath}");
        }

        $fileName = $this->generateFileName(basename($sourcePath), $compression);
        $fullPath = rtrim($outputPath, '/') . '/' . $fileName;

        $command = $this->buildCommand($sourcePath, $fullPath, $compression, $excludePatterns);

        $result = Process::timeout(3600)->run($command);

        if (! $result->successful()) {
            throw new \RuntimeException('Filesystem backup failed: ' . $result->errorOutput());
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
     * Backup a remote filesystem path via rsync over SSH, then compress locally.
     */
    protected function backupViaSsh(array $ssh, string $remotePath, array $excludePatterns, string $outputPath, string $compression): array
    {
        $tunnelService = app(SshTunnelService::class);
        $sshOptions = $tunnelService->buildSshOptions($ssh);

        $localRsyncDir = rtrim($outputPath, '/') . '/rsync_' . now()->format('Ymd_His');
        @mkdir($localRsyncDir, 0755, true);

        // Build exclude flags for rsync
        $excludes = '';
        foreach ($excludePatterns as $pattern) {
            $excludes .= ' --exclude=' . escapeshellarg($pattern);
        }

        $remoteSource = sprintf(
            '%s@%s:%s',
            escapeshellarg($ssh['user']),
            escapeshellarg($ssh['host']),
            escapeshellarg(rtrim($remotePath, '/') . '/')
        );

        $rsyncCmd = sprintf(
            'rsync -az -e %s %s %s %s/',
            escapeshellarg($sshOptions),
            $excludes,
            $remoteSource,
            escapeshellarg($localRsyncDir)
        );

        $result = Process::timeout(7200)->run($rsyncCmd);

        if (! $result->successful()) {
            Process::run('rm -rf ' . escapeshellarg($localRsyncDir));
            throw new \RuntimeException('SSH filesystem backup (rsync) failed: ' . $result->errorOutput());
        }

        $baseName = basename(rtrim($remotePath, '/'));
        $fileName = $this->generateFileName($baseName . '_ssh', $compression);
        $fullPath = rtrim($outputPath, '/') . '/' . $fileName;

        $archiveCmd = match ($compression) {
            'gzip' => 'tar -czf ' . escapeshellarg($fullPath) . ' -C ' . escapeshellarg(dirname($localRsyncDir)) . ' ' . escapeshellarg(basename($localRsyncDir)),
            'zip'  => 'cd ' . escapeshellarg(dirname($localRsyncDir)) . ' && zip -r ' . escapeshellarg($fullPath) . ' ' . escapeshellarg(basename($localRsyncDir)),
            default => 'tar -cf ' . escapeshellarg($fullPath) . ' -C ' . escapeshellarg(dirname($localRsyncDir)) . ' ' . escapeshellarg(basename($localRsyncDir)),
        };

        $archiveResult = Process::timeout(3600)->run($archiveCmd);
        Process::run('rm -rf ' . escapeshellarg($localRsyncDir));

        if (! $archiveResult->successful()) {
            throw new \RuntimeException('SSH filesystem backup archiving failed: ' . $archiveResult->errorOutput());
        }

        $meta = [
            'source_path' => $remotePath,
            'ssh_host' => $ssh['host'],
            'exclude_patterns' => $excludePatterns,
            'via_ssh' => true,
        ];

        return [
            'file_name' => $fileName,
            'file_path' => $fullPath,
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'meta' => $meta,
        ];
    }

    /**
     * Build the tar/zip command with exclusions.
     */
    protected function buildCommand(string $sourcePath, string $outputPath, string $compression, array $excludePatterns): string
    {
        $excludes = '';
        foreach ($excludePatterns as $pattern) {
            $excludes .= ' --exclude=' . escapeshellarg($pattern);
        }

        return match ($compression) {
            'gzip' => 'tar -czf ' . escapeshellarg($outputPath) . $excludes . ' -C ' . escapeshellarg(dirname($sourcePath)) . ' ' . escapeshellarg(basename($sourcePath)),
            'zip' => 'cd ' . escapeshellarg(dirname($sourcePath)) . ' && zip -r ' . escapeshellarg($outputPath) . ' ' . escapeshellarg(basename($sourcePath)) . $this->zipExcludes($excludePatterns),
            default => 'tar -cf ' . escapeshellarg($outputPath) . $excludes . ' -C ' . escapeshellarg(dirname($sourcePath)) . ' ' . escapeshellarg(basename($sourcePath)),
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
            $excludes .= ' ' . escapeshellarg($pattern);
        }

        return $excludes;
    }

    /**
     * Count files in source (approximate).
     */
    protected function countFiles(string $path, array $excludePatterns): int
    {
        try {
            $result = Process::run('find ' . escapeshellarg($path) . ' -type f | wc -l');

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
