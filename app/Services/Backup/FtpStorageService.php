<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FtpStorageService
{
    /**
     * Upload a file to an FTP destination.
     */
    public function upload(array $config, string $localPath, string $remotePath): string
    {
        $disk = $this->createDisk($config);

        $stream = fopen($localPath, 'rb');

        if ($stream === false) {
            throw new \RuntimeException("Cannot read local file: {$localPath}");
        }

        try {
            $disk->writeStream($remotePath, $stream);
        } finally {
            fclose($stream);
        }

        return $remotePath;
    }

    /**
     * Delete a remote file from FTP.
     */
    public function delete(array $config, string $remotePath): bool
    {
        $disk = $this->createDisk($config);

        return $disk->delete($remotePath);
    }

    /**
     * Check if the connection to FTP works.
     */
    public function testConnection(array $config): bool
    {
        try {
            $disk = $this->createDisk($config);
            $disk->directories('/');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * List files in a remote directory.
     */
    public function listFiles(array $config, string $directory = '/'): array
    {
        $disk = $this->createDisk($config);

        return $disk->files($directory);
    }

    /**
     * Download a file from FTP and stream it as a response.
     */
    public function download(array $config, string $remotePath, string $fileName): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $disk = $this->createDisk($config);

        if (! $disk->exists($remotePath)) {
            abort(404, "File not found: {$remotePath}");
        }

        $stream = $disk->readStream($remotePath);

        if (! $stream) {
            abort(500, "Cannot read file: {$remotePath}");
        }

        $size = $disk->size($remotePath);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => $size,
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Public accessor for the FTP filesystem disk (used by mirror methods so
     * callers/tests can override disk creation without touching createDisk).
     */
    public function disk(array $config): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return $this->createDisk($config);
    }

    /**
     * Recursively copy the remote tree at $remotePath into $localDir, skipping
     * files whose basename matches any of the exclude glob patterns.
     * Returns the number of files copied.
     */
    public function mirrorDown(array $config, string $remotePath, string $localDir, array $excludePatterns = []): int
    {
        $disk = $this->disk($config);

        File::ensureDirectoryExists($localDir);

        $base = trim($remotePath, '/');
        $count = 0;

        foreach ($disk->allFiles($remotePath) as $file) {
            if ($this->matchesAnyGlob(basename($file), $excludePatterns)) {
                continue;
            }

            $relative = ltrim(Str::after($file, $base), '/');
            $target = rtrim($localDir, '/').'/'.$relative;

            File::ensureDirectoryExists(dirname($target));

            $stream = $disk->readStream($file);

            if ($stream === null) {
                continue;
            }

            $out = fopen($target, 'wb');

            if ($out === false) {
                fclose($stream);
                throw new \RuntimeException("Cannot open local file for writing: {$target}");
            }

            try {
                stream_copy_to_stream($stream, $out);
            } finally {
                fclose($out);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $count++;
        }

        return $count;
    }

    /**
     * Recursively upload $localDir contents to $remotePath on the FTP disk.
     * Returns the number of files uploaded.
     */
    public function mirrorUp(array $config, string $localDir, string $remotePath): int
    {
        $disk = $this->disk($config);

        $count = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($localDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                continue;
            }

            $relative = ltrim(Str::after($fileInfo->getPathname(), rtrim($localDir, '/')), '/');
            $target = rtrim($remotePath, '/').'/'.$relative;

            $stream = fopen($fileInfo->getPathname(), 'rb');

            if ($stream === false) {
                continue;
            }

            try {
                $disk->writeStream($target, $stream);
            } finally {
                fclose($stream);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Check whether $name matches any of the given glob patterns.
     */
    protected function matchesAnyGlob(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a temporary filesystem disk for the FTP config.
     */
    protected function createDisk(array $config): \Illuminate\Contracts\Filesystem\Filesystem
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

        // Use Storage::build to create an isolated, unique disk instance per call.
        // This avoids global config mutation and name collisions with concurrent operations
        // using different FTP/SFTP destinations.
        return Storage::build($diskConfig);
    }
}
