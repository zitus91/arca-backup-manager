<?php

namespace App\Services\Backup;

use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Process;

class MysqlBackupService
{
    /**
     * Execute a MySQL dump and return the path to the generated file.
     */
    public function dump(array $config, string $outputPath, string $compression = 'gzip'): array
    {
        $command = $this->buildCommand($config);
        $fileName = $this->generateFileName($config['database'], $compression);
        $fullPath = rtrim($outputPath, '/') . '/' . $fileName;

        $cmd = $command;

        if ($compression === 'gzip') {
            $cmd .= ' | gzip > ' . escapeshellarg($fullPath);
        } elseif ($compression === 'zip') {
            $rawPath = rtrim($outputPath, '/') . '/' . $this->generateFileName($config['database'], 'none');
            $cmd .= ' > ' . escapeshellarg($rawPath);
        } else {
            $cmd .= ' > ' . escapeshellarg($fullPath);
        }

        $result = Process::timeout(3600)->run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException('mysqldump failed: ' . $result->errorOutput());
        }

        // Handle zip compression as second step
        if ($compression === 'zip') {
            $rawPath = rtrim($outputPath, '/') . '/' . $this->generateFileName($config['database'], 'none');
            $zipResult = Process::run('zip -j ' . escapeshellarg($fullPath) . ' ' . escapeshellarg($rawPath));
            if (! $zipResult->successful()) {
                throw new \RuntimeException('zip compression failed: ' . $zipResult->errorOutput());
            }
            @unlink($rawPath);
        }

        $meta = [
            'database' => $config['database'],
            'tables' => $config['tables'] ?? 'all',
            'host' => $config['host'],
        ];

        return [
            'file_name' => $fileName,
            'file_path' => $fullPath,
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'meta' => $meta,
        ];
    }

    /**
     * Build the mysqldump command with properly escaped arguments.
     */
    protected function buildCommand(array $config): string
    {
        $parts = [
            'mysqldump',
            '--host=' . escapeshellarg($config['host']),
            '--port=' . escapeshellarg((string) ($config['port'] ?? 3306)),
            '--user=' . escapeshellarg($config['username']),
            '--password=' . escapeshellarg($config['password']),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--quick',
        ];

        // Specific tables or whole database
        $parts[] = escapeshellarg($config['database']);

        if (! empty($config['tables']) && is_array($config['tables'])) {
            foreach ($config['tables'] as $table) {
                $parts[] = escapeshellarg($table);
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Generate a timestamped file name.
     */
    protected function generateFileName(string $database, string $compression): string
    {
        $timestamp = now()->format('Ymd_His');
        $ext = match ($compression) {
            'gzip' => '.sql.gz',
            'zip' => '.sql.zip',
            default => '.sql',
        };

        return "mysql_{$database}_{$timestamp}{$ext}";
    }
}
