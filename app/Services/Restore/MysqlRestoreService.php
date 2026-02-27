<?php

namespace App\Services\Restore;

use Illuminate\Support\Facades\Process;

class MysqlRestoreService
{
    /**
     * Restore a MySQL dump file into a database with "_restored" suffix.
     *
     * @param  array   $config       MySQL connection config (host, port, username, password, database)
     * @param  string  $dumpFilePath Path to the .sql / .sql.gz / .sql.zip file
     * @return array   Result with restored_db_name and meta
     */
    public function restore(array $config, string $dumpFilePath): array
    {
        $originalDb = $config['database'];
        $restoredDb = $originalDb . '_restored_' . now()->format('Ymd_His');

        // 1. Create the restored database if it doesn't exist
        $this->createDatabase($config, $restoredDb);

        // 2. Detect compression and build restore command
        $importCmd = $this->buildRestoreCommand($config, $restoredDb, $dumpFilePath);

        $result = Process::timeout(3600)->run($importCmd);

        if (! $result->successful()) {
            throw new \RuntimeException('MySQL restore failed: ' . $result->errorOutput());
        }

        return [
            'restored_db_name' => $restoredDb,
            'original_db' => $originalDb,
            'dump_file' => basename($dumpFilePath),
        ];
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
