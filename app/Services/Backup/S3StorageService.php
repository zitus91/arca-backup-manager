<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Storage;

class S3StorageService
{
    /**
     * Upload a file to an S3 destination using streaming to avoid memory issues.
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
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $remotePath;
    }

    /**
     * Delete a remote file from S3.
     */
    public function delete(array $config, string $remotePath): bool
    {
        $disk = $this->createDisk($config);

        return $disk->delete($remotePath);
    }

    /**
     * Check if the connection to S3 works.
     * Throws on failure so the caller can display the actual error.
     */
    public function testConnection(array $config): bool
    {
        $disk = $this->createDisk($config);
        $disk->directories('/');

        return true;
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
     * Download a file from S3 and stream it as a response.
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
     * Public seam over createDisk so callers (restore, tests) share one disk builder
     * instead of rebuilding the config and drifting from it.
     */
    public function disk(array $config): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return $this->createDisk($config);
    }

    /**
     * Create a temporary filesystem disk for the S3 config.
     * Uses Storage::build() to avoid Laravel's disk caching.
     */
    protected function createDisk(array $config): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $diskConfig = [
            'driver' => 's3',
            'key' => $config['access_key'],
            'secret' => $config['secret_key'],
            'region' => $config['region'] ?: 'us-east-1',
            'bucket' => $config['bucket'],
            'throw' => true,
        ];

        if (! empty($config['endpoint'])) {
            $diskConfig['endpoint'] = rtrim(trim($config['endpoint']), '/');
            $diskConfig['use_path_style_endpoint'] = true;
        }

        return Storage::build($diskConfig);
    }
}
