<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;

class PostgresBackupService
{
    /**
     * Execute a PostgreSQL dump and return the path to the generated file.
     * Supports an optional SSH tunnel via $config['ssh'].
     */
    public function dump(array $config, string $outputPath, string $compression = 'gzip'): array
    {
        $ssh = $config['ssh'] ?? null;

        if ($ssh && ! empty($ssh['enabled']) && ! empty($ssh['host'])) {
            $tunnelService = app(SshTunnelService::class);

            return $tunnelService->withTunnel(
                $ssh,
                '127.0.0.1',
                (int) ($config['port'] ?? 5432),
                function (int $localPort) use ($config, $outputPath, $compression) {
                    $tunnelConfig = array_merge($config, [
                        'host' => '127.0.0.1',
                        'port' => $localPort,
                    ]);

                    return $this->executeDump($tunnelConfig, $outputPath, $compression);
                }
            );
        }

        return $this->executeDump($config, $outputPath, $compression);
    }

    /**
     * PostgreSQL has no cheap per-table modification time, so an incremental
     * dump falls back to a full dump tagged with a checkpoint.
     *
     * ponytail: full dump per run; add WAL/logical replication if incremental size matters.
     */
    public function incrementalDump(array $config, string $outputPath, string $compression = 'gzip', ?array $checkpoint = null): array
    {
        $result = $this->dump($config, $outputPath, $compression);

        $result['meta']['incremental'] = true;
        $result['incremental_checkpoint'] = [
            'type' => 'postgres',
            'database' => $config['database'],
            'timestamp' => now()->toIso8601String(),
        ];

        return $result;
    }

    /**
     * Internal dump execution (no SSH).
     */
    protected function executeDump(array $config, string $outputPath, string $compression): array
    {
        $command = $this->buildCommand($config);
        $fileName = $this->generateFileName($config['database'], $compression);
        $fullPath = rtrim($outputPath, '/').'/'.$fileName;

        $cmd = $command;

        if ($compression === 'gzip') {
            $cmd .= ' | gzip > '.escapeshellarg($fullPath);
        } elseif ($compression === 'zip') {
            $rawPath = rtrim($outputPath, '/').'/'.$this->generateFileName($config['database'], 'none');
            $cmd .= ' > '.escapeshellarg($rawPath);
        } else {
            $cmd .= ' > '.escapeshellarg($fullPath);
        }

        // See MysqlBackupService: without pipefail the pipeline reports gzip's exit
        // code and a failed pg_dump is recorded as a successful empty backup.
        $result = Process::timeout(3600)->run(['bash', '-o', 'pipefail', '-c', $cmd]);

        if (! $result->successful()) {
            throw new \RuntimeException('pg_dump failed: '.$result->errorOutput());
        }

        // Handle zip compression as second step
        if ($compression === 'zip') {
            $rawPath = rtrim($outputPath, '/').'/'.$this->generateFileName($config['database'], 'none');
            $zipResult = Process::run('zip -j '.escapeshellarg($fullPath).' '.escapeshellarg($rawPath));
            if (! $zipResult->successful()) {
                throw new \RuntimeException('zip compression failed: '.$zipResult->errorOutput());
            }
            @unlink($rawPath);
        }

        $meta = [
            'database' => $config['database'],
            'tables' => 'all',
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
     * Build the pg_dump command with properly escaped arguments.
     * The password is passed via the PGPASSWORD environment variable.
     */
    protected function buildCommand(array $config): string
    {
        $env = 'PGPASSWORD='.escapeshellarg((string) ($config['password'] ?? ''));

        $parts = [
            'pg_dump',
            '--host='.escapeshellarg($config['host']),
            '--port='.escapeshellarg((string) ($config['port'] ?? 5432)),
            '--username='.escapeshellarg($config['username']),
            '--no-password',
            '--format=plain',
            '--clean',
            '--if-exists',
            escapeshellarg($config['database']),
        ];

        return $env.' '.implode(' ', $parts);
    }

    /**
     * Generate a timestamped file name.
     */
    protected function generateFileName(string $database, string $compression, bool $isIncremental = false): string
    {
        $timestamp = now()->format('Ymd_His');
        $prefix = $isIncremental ? 'postgres_incr' : 'postgres';
        $ext = match ($compression) {
            'gzip' => '.sql.gz',
            'zip' => '.sql.zip',
            default => '.sql',
        };

        return "{$prefix}_{$database}_{$timestamp}{$ext}";
    }
}
