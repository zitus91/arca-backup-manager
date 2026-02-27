<?php

namespace App\Services\Restore;

use Illuminate\Support\Facades\Process;

class FilesystemRestoreService
{
    /**
     * Restore a filesystem backup archive to the original path with "_restored" suffix.
     *
     * @param  array   $config       Filesystem config (path = original source path)
     * @param  string  $archivePath  Path to the .tar.gz / .zip / .tar archive
     * @return array   Result with restored_path and meta
     */
    public function restore(array $config, string $archivePath): array
    {
        $originalPath = rtrim($config['path'], '/');
        $restoredPath = $originalPath . '_restored_' . now()->format('Ymd_His');

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
