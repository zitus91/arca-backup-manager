<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Storage;

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
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
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
