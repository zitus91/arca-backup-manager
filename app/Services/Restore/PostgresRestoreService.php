<?php

namespace App\Services\Restore;

use App\Services\Backup\SshTunnelService;
use Illuminate\Support\Facades\Process;

class PostgresRestoreService
{
    /**
     * Restore a PostgreSQL dump file into a database.
     *
     * @param  array  $config  PostgreSQL connection config (host, port, username, password, database)
     * @param  string  $dumpFilePath  Path to the .sql / .sql.gz / .sql.zip file
     * @param  string|null  $targetDbName  Custom target database name (default: originalDb_restored_TIMESTAMP)
     * @param  bool  $overrideExisting  If true, DROP existing database before restoring
     * @return array Result with restored_db_name and meta
     */
    public function restore(array $config, string $dumpFilePath, ?string $targetDbName = null, bool $overrideExisting = false): array
    {
        $ssh = $config['ssh'] ?? null;

        if ($ssh && ! empty($ssh['enabled']) && ! empty($ssh['host'])) {
            return app(SshTunnelService::class)->withTunnel(
                $ssh,
                $config['host'],
                (int) ($config['port'] ?? 5432),
                function (int $localPort) use ($config, $dumpFilePath, $targetDbName, $overrideExisting): array {
                    $tunnelConfig = array_merge($config, ['host' => '127.0.0.1', 'port' => $localPort]);

                    return $this->executeRestore($tunnelConfig, $dumpFilePath, $targetDbName, $overrideExisting);
                }
            );
        }

        return $this->executeRestore($config, $dumpFilePath, $targetDbName, $overrideExisting);
    }

    protected function executeRestore(array $config, string $dumpFilePath, ?string $targetDbName, bool $overrideExisting): array
    {
        $originalDb = $config['database'];
        $restoredDb = $targetDbName ?? ($originalDb.'_restored_'.now()->format('Ymd_His'));

        // 1. If override, drop existing database first
        if ($overrideExisting) {
            $this->dropDatabaseIfExists($config, $restoredDb);
        }

        // 2. Create the restored database if it doesn't exist
        $this->createDatabase($config, $restoredDb);

        // 3. Detect compression and build restore command
        $importCmd = $this->buildRestoreCommand($config, $restoredDb, $dumpFilePath);

        $result = Process::timeout(3600)->run($importCmd);

        if (! $result->successful()) {
            throw new \RuntimeException('PostgreSQL restore failed: '.$result->errorOutput());
        }

        return [
            'restored_db_name' => $restoredDb,
            'original_db' => $originalDb,
            'dump_file' => basename($dumpFilePath),
            'override_existing' => $overrideExisting,
        ];
    }

    /**
     * Drop the target database if it exists (for override mode).
     */
    protected function dropDatabaseIfExists(array $config, string $dbName): void
    {
        $result = Process::timeout(30)->run($this->psql($config, 'postgres', 'DROP DATABASE IF EXISTS "'.$dbName.'";'));

        if (! $result->successful()) {
            throw new \RuntimeException("Failed to drop database '{$dbName}': ".$result->errorOutput());
        }
    }

    /**
     * Create the target database if it does not exist.
     * PostgreSQL has no "IF NOT EXISTS" for CREATE DATABASE, so guard with a lookup.
     */
    protected function createDatabase(array $config, string $dbName): void
    {
        $sql = "SELECT 1 FROM pg_database WHERE datname = '{$dbName}'";
        $exists = Process::timeout(30)->run($this->psql($config, 'postgres', $sql, tuplesOnly: true));

        if (trim($exists->output()) === '1') {
            return;
        }

        $result = Process::timeout(30)->run($this->psql($config, 'postgres', 'CREATE DATABASE "'.$dbName.'";'));

        if (! $result->successful()) {
            throw new \RuntimeException("Failed to create database '{$dbName}': ".$result->errorOutput());
        }
    }

    /**
     * Build a psql command running a single SQL statement against $database.
     */
    protected function psql(array $config, string $database, string $sql, bool $tuplesOnly = false): string
    {
        $env = 'PGPASSWORD='.escapeshellarg((string) ($config['password'] ?? ''));

        return sprintf(
            '%s psql --host=%s --port=%s --username=%s --no-password %s --dbname=%s -c %s',
            $env,
            escapeshellarg($config['host']),
            escapeshellarg((string) ($config['port'] ?? 5432)),
            escapeshellarg($config['username']),
            $tuplesOnly ? '-tA' : '',
            escapeshellarg($database),
            escapeshellarg($sql)
        );
    }

    /**
     * Build the appropriate restore command based on file extension.
     */
    protected function buildRestoreCommand(array $config, string $dbName, string $filePath): string
    {
        $env = 'PGPASSWORD='.escapeshellarg((string) ($config['password'] ?? ''));
        $psqlCmd = sprintf(
            '%s psql --host=%s --port=%s --username=%s --no-password --dbname=%s',
            $env,
            escapeshellarg($config['host']),
            escapeshellarg((string) ($config['port'] ?? 5432)),
            escapeshellarg($config['username']),
            escapeshellarg($dbName)
        );

        if (str_ends_with($filePath, '.sql.gz') || str_ends_with($filePath, '.gz')) {
            return 'gunzip -c '.escapeshellarg($filePath).' | '.$psqlCmd;
        }

        if (str_ends_with($filePath, '.sql.zip') || str_ends_with($filePath, '.zip')) {
            return 'unzip -p '.escapeshellarg($filePath).' | '.$psqlCmd;
        }

        return $psqlCmd.' < '.escapeshellarg($filePath);
    }
}
