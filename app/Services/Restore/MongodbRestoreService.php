<?php

namespace App\Services\Restore;

use App\Services\Backup\SshTunnelService;
use Illuminate\Support\Facades\Process;

class MongodbRestoreService
{
    /**
     * Restore a MongoDB dump archive into a database.
     *
     * @param  array  $config  MongoDB connection config (host, port, database, username, password)
     * @param  string  $archivePath  Path to the .tar.gz / .zip / .tar archive
     * @param  string|null  $targetDbName  Custom target database name (default: originalDb_restored_TIMESTAMP)
     * @param  bool  $overrideExisting  If true, DROP existing database before restoring
     * @return array Result with restored_db_name and meta
     */
    public function restore(array $config, string $archivePath, ?string $targetDbName = null, bool $overrideExisting = false): array
    {
        $ssh = $config['ssh'] ?? null;

        if ($ssh && ! empty($ssh['enabled']) && ! empty($ssh['host'])) {
            return app(SshTunnelService::class)->withTunnel(
                $ssh,
                $config['host'],
                (int) ($config['port'] ?? 27017),
                function (int $localPort) use ($config, $archivePath, $targetDbName, $overrideExisting): array {
                    $tunnelConfig = array_merge($config, ['host' => '127.0.0.1', 'port' => $localPort]);

                    return $this->executeRestore($tunnelConfig, $archivePath, $targetDbName, $overrideExisting);
                }
            );
        }

        return $this->executeRestore($config, $archivePath, $targetDbName, $overrideExisting);
    }

    protected function executeRestore(array $config, string $archivePath, ?string $targetDbName, bool $overrideExisting): array
    {
        $originalDb = $config['database'];
        $restoredDb = $targetDbName ?? ($originalDb.'_restored_'.now()->format('Ymd_His'));

        // 1. If override, drop existing database first; otherwise refuse to restore into
        // an existing database (mongorestore would merge collections into live data).
        if ($overrideExisting) {
            $this->dropDatabaseIfExists($config, $restoredDb);
        } elseif ($this->databaseExists($config, $restoredDb)) {
            throw new \RuntimeException("Target database '{$restoredDb}' already exists. Choose a different name or enable override.");
        }

        // 2. Extract the archive to a temp directory
        $extractDir = dirname($archivePath).'/mongorestore_'.uniqid();
        @mkdir($extractDir, 0755, true);

        $this->extractArchive($archivePath, $extractDir);

        // Find the dump directory (mongodump creates a subfolder with the db name)
        $dumpPath = $this->findDumpPath($extractDir, $originalDb);

        // 3. Run mongorestore with nsFrom/nsTo to rename db
        $cmd = $this->buildRestoreCommand($config, $originalDb, $restoredDb, $dumpPath);

        $result = Process::timeout(3600)->run($cmd);

        if (! $result->successful()) {
            Process::run('rm -rf '.escapeshellarg($extractDir));
            throw new \RuntimeException('MongoDB restore failed: '.$result->errorOutput());
        }

        Process::run('rm -rf '.escapeshellarg($extractDir));

        return [
            'restored_db_name' => $restoredDb,
            'original_db' => $originalDb,
            'dump_file' => basename($archivePath),
            'override_existing' => $overrideExisting,
        ];
    }

    /**
     * Drop the target database if it exists (for override mode).
     */
    protected function dropDatabaseIfExists(array $config, string $dbName): void
    {
        $result = Process::timeout(30)->run(
            $this->mongoshCommand($config, 'db.getSiblingDB('.json_encode($dbName).').dropDatabase()')
        );

        if (! $result->successful()) {
            throw new \RuntimeException("Failed to drop MongoDB database '{$dbName}': ".$result->errorOutput());
        }
    }

    /**
     * Whether the target database already exists.
     */
    protected function databaseExists(array $config, string $dbName): bool
    {
        $result = Process::timeout(30)->run(
            $this->mongoshCommand($config, 'print(db.getMongo().getDBNames().includes('.json_encode($dbName).'))', quiet: true)
        );

        if (! $result->successful()) {
            throw new \RuntimeException("Failed to check MongoDB database '{$dbName}': ".$result->errorOutput());
        }

        return str_contains($result->output(), 'true');
    }

    /**
     * Build a mongosh command running a single --eval script.
     */
    protected function mongoshCommand(array $config, string $eval, bool $quiet = false): string
    {
        $parts = [
            'mongosh',
            '--host', escapeshellarg($config['host']),
            '--port', escapeshellarg((string) ($config['port'] ?? 27017)),
        ];

        if ($quiet) {
            $parts[] = '--quiet';
        }

        if (! empty($config['username'])) {
            $parts[] = '--username '.escapeshellarg($config['username']);
            $parts[] = '--password '.escapeshellarg($config['password']);
            $parts[] = '--authenticationDatabase '.escapeshellarg($config['auth_database'] ?? 'admin');
        }

        $parts[] = '--eval '.escapeshellarg($eval);

        return implode(' ', $parts);
    }

    /**
     * Extract the archive based on extension.
     */
    protected function extractArchive(string $archivePath, string $extractDir): void
    {
        if (str_ends_with($archivePath, '.tar.gz') || str_ends_with($archivePath, '.tgz')) {
            $cmd = 'tar -xzf '.escapeshellarg($archivePath).' -C '.escapeshellarg($extractDir);
        } elseif (str_ends_with($archivePath, '.zip')) {
            $cmd = 'unzip -o '.escapeshellarg($archivePath).' -d '.escapeshellarg($extractDir);
        } elseif (str_ends_with($archivePath, '.tar')) {
            $cmd = 'tar -xf '.escapeshellarg($archivePath).' -C '.escapeshellarg($extractDir);
        } else {
            throw new \RuntimeException("Unsupported archive format: {$archivePath}");
        }

        $result = Process::timeout(300)->run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to extract MongoDB archive: '.$result->errorOutput());
        }
    }

    /**
     * Find the actual dump path inside the extracted directory.
     */
    protected function findDumpPath(string $extractDir, string $dbName): string
    {
        // mongodump creates: extractDir/dbName/ or extractDir/mongodump_*/dbName/
        // Try direct path first
        if (is_dir("{$extractDir}/{$dbName}")) {
            return $extractDir;
        }

        // Search for the database directory recursively (one level deep)
        $dirs = glob("{$extractDir}/*/{$dbName}", GLOB_ONLYDIR);
        if (! empty($dirs)) {
            return dirname($dirs[0]);
        }

        // If not found, use the extractDir itself (mongorestore will figure it out)
        return $extractDir;
    }

    /**
     * Build the mongorestore command.
     */
    protected function buildRestoreCommand(array $config, string $fromDb, string $toDb, string $dumpPath): string
    {
        $parts = [
            'mongorestore',
            '--host='.escapeshellarg($config['host']),
            '--port='.escapeshellarg((string) ($config['port'] ?? 27017)),
            '--nsFrom='.escapeshellarg("{$fromDb}.*"),
            '--nsTo='.escapeshellarg("{$toDb}.*"),
            '--drop',
        ];

        if (! empty($config['username'])) {
            $parts[] = '--username='.escapeshellarg($config['username']);
            $parts[] = '--password='.escapeshellarg($config['password']);
            $parts[] = '--authenticationDatabase='.escapeshellarg($config['auth_database'] ?? 'admin');
        }

        $parts[] = escapeshellarg($dumpPath);

        return implode(' ', $parts);
    }
}
