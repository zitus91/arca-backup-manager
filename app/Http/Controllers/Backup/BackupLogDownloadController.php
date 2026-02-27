<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Services\Backup\FtpStorageService;
use App\Services\Backup\S3StorageService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupLogDownloadController extends Controller
{
    public function __invoke(
        BackupLog $log,
        S3StorageService $s3Service,
        FtpStorageService $ftpService,
    ): StreamedResponse|BinaryFileResponse {
        // Only allow download of successful backups with a storage path
        if ($log->status !== 'success' || ! $log->storage_path) {
            abort(404, 'Backup file not available for download.');
        }

        $log->loadMissing('job.destination');

        $destination = $log->job->destination;

        if (! $destination) {
            abort(404, 'Destination not found.');
        }

        $config = $destination->config;
        $remotePath = $log->storage_path;
        $fileName = $log->file_name ?? basename($remotePath);

        return match ($destination->type) {
            's3' => $s3Service->download($config, $remotePath, $fileName),
            'ftp' => $ftpService->download($config, $remotePath, $fileName),
            'local' => $this->downloadLocal($config, $remotePath, $fileName),
            default => abort(404, "Unsupported destination type: {$destination->type}"),
        };
    }

    /**
     * Download a file from local storage.
     */
    protected function downloadLocal(array $config, string $remotePath, string $fileName): BinaryFileResponse
    {
        $basePath = rtrim($config['path'] ?? '', '/');
        $fullPath = $basePath . '/' . $remotePath;

        if (! file_exists($fullPath)) {
            abort(404, "File not found: {$fullPath}");
        }

        return response()->download($fullPath, $fileName, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
