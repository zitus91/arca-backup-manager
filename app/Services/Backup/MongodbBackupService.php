<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;

class MongodbBackupService
{
    /**
     * Execute a MongoDB dump and return the path to the generated file.
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
                (int) ($config['port'] ?? 27017),
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
     * Execute an incremental MongoDB dump: only documents created/modified since checkpoint.
     * Uses ObjectId timestamp comparison for collections with _id as ObjectId.
     */
    public function incrementalDump(array $config, string $outputPath, string $compression = 'gzip', ?array $checkpoint = null): array
    {
        $ssh = $config['ssh'] ?? null;

        if ($ssh && ! empty($ssh['enabled']) && ! empty($ssh['host'])) {
            $tunnelService = app(SshTunnelService::class);

            return $tunnelService->withTunnel(
                $ssh,
                '127.0.0.1',
                (int) ($config['port'] ?? 27017),
                function (int $localPort) use ($config, $outputPath, $compression, $checkpoint) {
                    $tunnelConfig = array_merge($config, [
                        'host' => '127.0.0.1',
                        'port' => $localPort,
                    ]);

                    return $this->executeIncrementalDump($tunnelConfig, $outputPath, $compression, $checkpoint);
                }
            );
        }

        return $this->executeIncrementalDump($config, $outputPath, $compression, $checkpoint);
    }

    /**
     * Internal incremental dump: uses --query with ObjectId timestamp filter.
     */
    protected function executeIncrementalDump(array $config, string $outputPath, string $compression, ?array $checkpoint): array
    {
        $sinceTimestamp = $checkpoint['timestamp'] ?? null;

        // Build an ObjectId from the checkpoint timestamp for _id comparison
        $query = null;
        if ($sinceTimestamp) {
            $unixTime = strtotime($sinceTimestamp);
            if ($unixTime) {
                $hexTimestamp = dechex($unixTime);
                $objectIdPrefix = str_pad($hexTimestamp, 8, '0', STR_PAD_LEFT) . '0000000000000000';
                $query = '{"_id":{"\$gt":{"\$oid":"' . $objectIdPrefix . '"}}}';
            }
        }

        $dumpDir = rtrim($outputPath, '/') . '/mongodump_incr_' . now()->format('Ymd_His');
        $command = $this->buildCommand($config, $dumpDir);

        if ($query) {
            $command .= ' --queryFile=' . escapeshellarg($this->writeQueryFile($query, $outputPath));
        }

        $result = Process::timeout(3600)->run($command);

        if (! $result->successful()) {
            throw new \RuntimeException('mongodump incremental failed: ' . $result->errorOutput());
        }

        $fileName = $this->generateFileName($config['database'], $compression, true);
        $fullPath = rtrim($outputPath, '/') . '/' . $fileName;

        $this->compressDirectory($dumpDir, $fullPath, $compression);

        Process::run('rm -rf ' . escapeshellarg($dumpDir));

        // Cleanup query file
        $queryFile = rtrim($outputPath, '/') . '/.mongodump_query.json';
        @unlink($queryFile);

        $meta = [
            'database' => $config['database'],
            'collections' => $config['collections'] ?? 'all',
            'host' => $config['host'],
            'incremental' => true,
            'since' => $sinceTimestamp,
        ];

        return [
            'file_name' => $fileName,
            'file_path' => $fullPath,
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'meta' => $meta,
            'incremental_checkpoint' => [
                'type' => 'mongodb',
                'database' => $config['database'],
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Write a query JSON file for mongodump --queryFile.
     */
    protected function writeQueryFile(string $query, string $dir): string
    {
        $path = rtrim($dir, '/') . '/.mongodump_query.json';
        file_put_contents($path, $query);

        return $path;
    }

    /**
     * Internal dump execution (no SSH).
     */
    protected function executeDump(array $config, string $outputPath, string $compression): array
    {
        $dumpDir = rtrim($outputPath, '/') . '/mongodump_' . now()->format('Ymd_His');
        $command = $this->buildCommand($config, $dumpDir);

        $result = Process::timeout(3600)->run($command);

        if (! $result->successful()) {
            throw new \RuntimeException('mongodump failed: ' . $result->errorOutput());
        }

        $fileName = $this->generateFileName($config['database'], $compression);
        $fullPath = rtrim($outputPath, '/') . '/' . $fileName;

        // Compress the dump directory
        $this->compressDirectory($dumpDir, $fullPath, $compression);

        // Clean up raw dump directory
        Process::run('rm -rf ' . escapeshellarg($dumpDir));

        $meta = [
            'database' => $config['database'],
            'collections' => $config['collections'] ?? 'all',
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
     * Build the mongodump command with properly escaped arguments.
     */
    protected function buildCommand(array $config, string $outputDir): string
    {
        $parts = [
            'mongodump',
            '--host=' . escapeshellarg($config['host']),
            '--port=' . escapeshellarg((string) ($config['port'] ?? 27017)),
            '--db=' . escapeshellarg($config['database']),
            '--out=' . escapeshellarg($outputDir),
        ];

        if (! empty($config['username'])) {
            $parts[] = '--username=' . escapeshellarg($config['username']);
            $parts[] = '--password=' . escapeshellarg($config['password']);
            $authDb = $config['auth_database'] ?? 'admin';
            $parts[] = '--authenticationDatabase=' . escapeshellarg($authDb);
        }

        if (! empty($config['collections']) && is_array($config['collections'])) {
            // mongodump can only do one collection at a time, so we dump all and filter
            // For multiple specific collections, we handle it differently
            foreach ($config['collections'] as $collection) {
                // Note: mongodump --collection only works for one at a time
                // We'll dump the whole DB and let the archive handle it
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Compress a directory into a single archive file.
     */
    protected function compressDirectory(string $sourceDir, string $outputPath, string $compression): void
    {
        $cmd = match ($compression) {
            'gzip' => 'tar -czf ' . escapeshellarg($outputPath) . ' -C ' . escapeshellarg(dirname($sourceDir)) . ' ' . escapeshellarg(basename($sourceDir)),
            'zip' => 'cd ' . escapeshellarg(dirname($sourceDir)) . ' && zip -r ' . escapeshellarg($outputPath) . ' ' . escapeshellarg(basename($sourceDir)),
            default => 'tar -cf ' . escapeshellarg($outputPath) . ' -C ' . escapeshellarg(dirname($sourceDir)) . ' ' . escapeshellarg(basename($sourceDir)),
        };

        $result = Process::run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException('Compression failed: ' . $result->errorOutput());
        }
    }

    /**
     * Generate a timestamped file name.
     */
    protected function generateFileName(string $database, string $compression, bool $isIncremental = false): string
    {
        $timestamp = now()->format('Ymd_His');
        $prefix = $isIncremental ? 'mongodb_incr' : 'mongodb';
        $ext = match ($compression) {
            'gzip' => '.tar.gz',
            'zip' => '.zip',
            default => '.tar',
        };

        return "{$prefix}_{$database}_{$timestamp}{$ext}";
    }
}
