<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;

class MysqlBackupService
{
    /**
     * Execute a MySQL dump and return the path to the generated file.
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
                (int) ($config['port'] ?? 3306),
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
     * Execute an incremental MySQL dump: only tables modified since the checkpoint.
     * Returns the same structure as dump() plus an 'incremental_checkpoint' key.
     */
    public function incrementalDump(array $config, string $outputPath, string $compression = 'gzip', ?array $checkpoint = null): array
    {
        $ssh = $config['ssh'] ?? null;

        if ($ssh && ! empty($ssh['enabled']) && ! empty($ssh['host'])) {
            $tunnelService = app(SshTunnelService::class);

            return $tunnelService->withTunnel(
                $ssh,
                '127.0.0.1',
                (int) ($config['port'] ?? 3306),
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
     * Internal incremental dump: queries information_schema for modified tables.
     */
    protected function executeIncrementalDump(array $config, string $outputPath, string $compression, ?array $checkpoint): array
    {
        $since = $checkpoint['timestamp'] ?? null;
        $changedTables = $this->getChangedTables($config, $since);

        // If no tables changed, create an empty marker file. It holds plain text, so it
        // must not claim a .gz/.zip extension: the restore picks its decompressor from
        // the file name and would fail trying to gunzip it.
        if (empty($changedTables)) {
            $fileName = $this->generateFileName($config['database'], 'none', true);
            $fullPath = rtrim($outputPath, '/').'/'.$fileName;
            file_put_contents($fullPath, '-- incremental: no changes since '.($since ?? 'unknown'));

            return [
                'file_name' => $fileName,
                'file_path' => $fullPath,
                'file_size' => filesize($fullPath),
                'meta' => [
                    'database' => $config['database'],
                    'tables' => [],
                    'host' => $config['host'],
                    'incremental' => true,
                    'no_changes' => true,
                ],
                'incremental_checkpoint' => [
                    'type' => 'mysql',
                    'database' => $config['database'],
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        }

        // Dump only changed tables
        $dumpConfig = array_merge($config, ['tables' => $changedTables]);
        $result = $this->executeDump($dumpConfig, $outputPath, $compression, true);

        $result['meta']['incremental'] = true;
        $result['meta']['changed_tables'] = $changedTables;
        $result['incremental_checkpoint'] = [
            'type' => 'mysql',
            'database' => $config['database'],
            'timestamp' => now()->toIso8601String(),
        ];

        return $result;
    }

    /**
     * Query information_schema to find tables modified since $since.
     */
    protected function getChangedTables(array $config, ?string $since): array
    {
        if (! $since) {
            return []; // No checkpoint = should do full backup
        }

        $cmd = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s -N -e %s',
            escapeshellarg($config['host']),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            MysqlClient::sslFlags($config),
            escapeshellarg(
                'SELECT TABLE_NAME FROM information_schema.TABLES '
                ."WHERE TABLE_SCHEMA = '{$config['database']}' "
                ."AND TABLE_TYPE = 'BASE TABLE' "
                ."AND (UPDATE_TIME IS NULL OR UPDATE_TIME >= '{$since}')"
            )
        );

        $result = Process::timeout(60)->run($cmd);

        if (! $result->successful()) {
            // Fallback: cannot determine changed tables, return empty to force full
            return [];
        }

        return array_filter(array_map('trim', explode("\n", trim($result->output()))));
    }

    /**
     * Internal dump execution (no SSH).
     */
    protected function executeDump(array $config, string $outputPath, string $compression, bool $isIncremental = false): array
    {
        $command = $this->buildCommand($config);
        $fileName = $this->generateFileName($config['database'], $compression, $isIncremental);
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

        // Through a plain shell the pipeline reports gzip's exit code, so a failing
        // mysqldump looked successful and left a 20-byte empty gzip behind.
        $result = Process::timeout(3600)->run(['bash', '-o', 'pipefail', '-c', $cmd]);

        if (! $result->successful()) {
            throw new \RuntimeException('mysqldump failed: '.$result->errorOutput());
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
            '--host='.escapeshellarg($config['host']),
            '--port='.escapeshellarg((string) ($config['port'] ?? 3306)),
            '--user='.escapeshellarg($config['username']),
            '--password='.escapeshellarg($config['password']),
            MysqlClient::sslFlags($config),
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
    protected function generateFileName(string $database, string $compression, bool $isIncremental = false): string
    {
        $timestamp = now()->format('Ymd_His');
        $prefix = $isIncremental ? 'mysql_incr' : 'mysql';
        $ext = match ($compression) {
            'gzip' => '.sql.gz',
            'zip' => '.sql.zip',
            default => '.sql',
        };

        return "{$prefix}_{$database}_{$timestamp}{$ext}";
    }
}
