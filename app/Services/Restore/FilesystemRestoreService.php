<?php

namespace App\Services\Restore;

use App\Services\Backup\SshTunnelService;
use Illuminate\Support\Facades\Process;

class FilesystemRestoreService
{
    /**
     * Restore a filesystem backup archive.
     *
     * @param  array       $config           Filesystem config (path = original source path, optional ssh for remote)
     * @param  string      $archivePath      Path to the .tar.gz / .zip / .tar archive
     * @param  string|null $targetPath       Custom target path (default: originalPath_restored_TIMESTAMP)
     * @param  bool        $overrideExisting If true, remove existing directory before restoring
     * @return array   Result with restored_path and meta
     */
    public function restore(array $config, string $archivePath, ?string $targetPath = null, bool $overrideExisting = false): array
    {
        $originalPath = rtrim($config['path'], '/');
        $restoredPath = $targetPath ?? ($originalPath . '_restored_' . now()->format('Ymd_His'));
        $sshConfig = $config['ssh'] ?? null;

        if ($sshConfig) {
            return $this->restoreRemote($originalPath, $restoredPath, $archivePath, $overrideExisting, $sshConfig);
        }

        // If override, remove existing directory first
        if ($overrideExisting && is_dir($restoredPath)) {
            Process::timeout(300)->run('rm -rf ' . escapeshellarg($restoredPath));
        }

        // 1. Create the restored directory
        if (! is_dir($restoredPath)) {
            if (! mkdir($restoredPath, 0755, true)) {
                throw new \RuntimeException("Failed to create restore directory: {$restoredPath}");
            }
        }

        // 2. Extract the archive to the restored path
        $cmd = $this->buildExtractCommand($archivePath, $restoredPath);

        $result = Process::timeout(3600)->run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException('Filesystem restore failed: ' . $result->errorOutput());
        }

        // 3. Count restored files
        $fileCount = $this->countFiles($restoredPath);

        return [
            'restored_path' => $restoredPath,
            'original_path' => $originalPath,
            'files_count' => $fileCount,
            'archive_file' => basename($archivePath),
            'override_existing' => $overrideExisting,
        ];
    }

    /**
     * Restore files to a remote host via SSH/rsync.
     */
    protected function restoreRemote(string $originalPath, string $targetPath, string $archivePath, bool $overrideExisting, array $sshConfig): array
    {
        // Extract to temp local dir first
        $tmpDir = dirname($archivePath) . '/fs_remote_' . uniqid();
        @mkdir($tmpDir, 0755, true);

        $extractCmd = $this->buildExtractCommand($archivePath, $tmpDir);
        $result = Process::timeout(3600)->run($extractCmd);

        if (! $result->successful()) {
            Process::run('rm -rf ' . escapeshellarg($tmpDir));
            throw new \RuntimeException('Filesystem extract failed: ' . $result->errorOutput());
        }

        // Build SSH options using SshTunnelService for consistent auth handling (key + sshpass)
        $sshOptions = app(SshTunnelService::class)->buildSshOptions($sshConfig);
        $sshUser    = $sshConfig['user'] ?? $sshConfig['ssh_user'] ?? '';
        $sshHost    = $sshConfig['host'] ?? $sshConfig['ssh_host'] ?? '';
        $remoteUser = escapeshellarg($sshUser . '@' . $sshHost);

        // For plain ssh commands (mkdir, rm) we need ssh binary + options without sshpass prefix
        $port     = (int) ($sshConfig['port'] ?? $sshConfig['ssh_port'] ?? 22);
        $baseSsh  = "-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -p {$port}";
        if (($sshConfig['auth_method'] ?? 'key') === 'password' && ! empty($sshConfig['password'])) {
            $pass    = escapeshellarg($sshConfig['password']);
            $sshCmd  = "sshpass -p {$pass} ssh {$baseSsh}";
        } else {
            $keyPath = escapeshellarg($sshConfig['key_path'] ?? $sshConfig['ssh_key_path'] ?? '');
            $sshCmd  = "ssh {$baseSsh} -i {$keyPath}";
        }

        // If override, remove remote directory first
        if ($overrideExisting) {
            Process::timeout(60)->run("{$sshCmd} {$remoteUser} " . escapeshellarg("rm -rf {$targetPath}"));
        }

        // Create remote directory
        Process::timeout(30)->run("{$sshCmd} {$remoteUser} " . escapeshellarg("mkdir -p {$targetPath}"));

        // rsync to remote using the full ssh options string (handles sshpass)
        $rsyncCmd = sprintf(
            'rsync -avz -e %s %s/ %s',
            escapeshellarg($sshOptions),
            escapeshellarg($tmpDir),
            escapeshellarg($sshUser . '@' . $sshHost . ':' . $targetPath)
        );

        $rsyncResult = Process::timeout(3600)->run($rsyncCmd);

        // Cleanup local temp
        Process::run('rm -rf ' . escapeshellarg($tmpDir));

        if (! $rsyncResult->successful()) {
            throw new \RuntimeException('Remote filesystem restore failed: ' . $rsyncResult->errorOutput());
        }

        return [
            'restored_path' => $sshUser . '@' . $sshHost . ':' . $targetPath,
            'original_path' => $originalPath,
            'archive_file' => basename($archivePath),
            'override_existing' => $overrideExisting,
            'remote' => true,
        ];
    }

    /**
     * Build the extraction command based on archive type.
     */
    protected function buildExtractCommand(string $archivePath, string $destPath): string
    {
        if (str_ends_with($archivePath, '.tar.gz') || str_ends_with($archivePath, '.tgz')) {
            return 'tar -xzf ' . escapeshellarg($archivePath) . ' -C ' . escapeshellarg($destPath);
        }

        if (str_ends_with($archivePath, '.zip')) {
            return 'unzip -o ' . escapeshellarg($archivePath) . ' -d ' . escapeshellarg($destPath);
        }

        if (str_ends_with($archivePath, '.tar')) {
            return 'tar -xf ' . escapeshellarg($archivePath) . ' -C ' . escapeshellarg($destPath);
        }

        throw new \RuntimeException("Unsupported archive format: {$archivePath}");
    }

    /**
     * Count files in the restored directory.
     */
    protected function countFiles(string $path): int
    {
        try {
            $result = Process::run('find ' . escapeshellarg($path) . ' -type f | wc -l');

            return (int) trim($result->output());
        } catch (\Throwable) {
            return 0;
        }
    }
}
