<?php

namespace App\Services\Restore;

use App\Services\Backup\SshTunnelService;
use Illuminate\Support\Facades\Process;

class MysqlRestoreService
{
    /**
     * Restore a MySQL dump file into a database.
     *
     * @param  array   $config           MySQL connection config (host, port, username, password, database)
     * @param  string  $dumpFilePath     Path to the .sql / .sql.gz / .sql.zip file
     * @param  string|null $targetDbName Custom target database name (default: originalDb_restored_TIMESTAMP)
     * @param  bool    $overrideExisting If true, DROP existing database before restoring
     * @return array   Result with restored_db_name and meta
     */
    public function restore(array $config, string $dumpFilePath, ?string $targetDbName = null, bool $overrideExisting = false): array
    {
        $ssh = $config['ssh'] ?? null;

        if ($ssh && ! empty($ssh['enabled']) && ! empty($ssh['host'])) {
            return app(SshTunnelService::class)->withTunnel(
                $ssh,
                $config['host'],
                (int) ($config['port'] ?? 3306),
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
        $restoredDb = $targetDbName ?? ($originalDb . '_restored_' . now()->format('Ymd_His'));

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
            throw new \RuntimeException('MySQL restore failed: ' . $result->errorOutput());
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
        $cmd = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s -e %s',
            escapeshellarg($config['host']),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg("DROP DATABASE IF EXISTS `{$dbName}`;")
        );

        $result = Process::timeout(30)->run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException("Failed to drop database '{$dbName}': " . $result->errorOutput());
        }
    }

    /**
     * Create the target database if it does not exist.
     */
    protected function createDatabase(array $config, string $dbName): void
    {
        $cmd = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s -e %s',
            escapeshellarg($config['host']),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
        );

        $result = Process::timeout(30)->run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException("Failed to create database '{$dbName}': " . $result->errorOutput());
        }
    }

    /**
     * Build the appropriate restore command based on file extension.
     */
    protected function buildRestoreCommand(array $config, string $dbName, string $filePath): string
    {
        $mysqlCmd = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s',
            escapeshellarg($config['host']),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($dbName)
        );

        if (str_ends_with($filePath, '.sql.gz') || str_ends_with($filePath, '.gz')) {
            return 'gunzip -c ' . escapeshellarg($filePath) . ' | ' . $mysqlCmd;
        }

        if (str_ends_with($filePath, '.sql.zip') || str_ends_with($filePath, '.zip')) {
            // Extract to stdout and pipe to mysql
            return 'unzip -p ' . escapeshellarg($filePath) . ' | ' . $mysqlCmd;
        }

        // Plain .sql file
        return $mysqlCmd . ' < ' . escapeshellarg($filePath);
    }
}
