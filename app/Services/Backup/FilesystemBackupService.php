<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;

class FilesystemBackupService
{
    /**
     * Create a backup of a filesystem path.
     */
    public function backup(array $config, string $outputPath, string $compression = 'gzip'): array
    {
        $sourcePath = $config['path'];
        $excludePatterns = $config['exclude_patterns'] ?? [];

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
