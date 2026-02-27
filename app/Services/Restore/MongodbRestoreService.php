<?php

namespace App\Services\Restore;

use Illuminate\Support\Facades\Process;

class MongodbRestoreService
{
    /**
     * Restore a MongoDB dump archive into a database with "_restored" suffix.
     *
     * @param  array   $config       MongoDB connection config (host, port, database, username, password)
     * @param  string  $archivePath  Path to the .tar.gz / .zip / .tar archive
     * @return array   Result with restored_db_name and meta
     */
    public function restore(array $config, string $archivePath): array
    {
        $originalDb = $config['database'];
        $restoredDb = $originalDb . '_restored_' . now()->format('Ymd_His');

        // 1. Extract the archive to a temp directory
        $extractDir = dirname($archivePath) . '/mongorestore_' . uniqid();
        @mkdir($extractDir, 0755, true);

        $this->extractArchive($archivePath, $extractDir);

        // 2. Find the dump directory (mongodump creates a subfolder with the db name)
        $dumpPath = $this->findDumpPath($extractDir, $originalDb);

        // 3. Run mongorestore with nsFrom/nsTo to rename db
        $cmd = $this->buildRestoreCommand($config, $originalDb, $restoredDb, $dumpPath);

        $result = Process::timeout(3600)->run($cmd);

        if (! $result->successful()) {
            // Cleanup before throwing
            Process::run('rm -rf ' . escapeshellarg($extractDir));
            throw new \RuntimeException('MongoDB restore failed: ' . $result->errorOutput());
        }

        // Cleanup extracted files
        Process::run('rm -rf ' . escapeshellarg($extractDir));

        return [
            'restored_db_name' => $restoredDb,
            'original_db' => $originalDb,
            'dump_file' => basename($archivePath),
        ];
    }

    /**
     * Extract the archive based on extension.
     */
    protected function extractArchive(string $archivePath, string $extractDir): void
    {
        if (str_ends_with($archivePath, '.tar.gz') || str_ends_with($archivePath, '.tgz')) {
            $cmd = 'tar -xzf ' . escapeshellarg($archivePath) . ' -C ' . escapeshellarg($extractDir);
        } elseif (str_ends_with($archivePath, '.zip')) {
            $cmd = 'unzip -o ' . escapeshellarg($archivePath) . ' -d ' . escapeshellarg($extractDir);
        } elseif (str_ends_with($archivePath, '.tar')) {
            $cmd = 'tar -xf ' . escapeshellarg($archivePath) . ' -C ' . escapeshellarg($extractDir);
        } else {
            throw new \RuntimeException("Unsupported archive format: {$archivePath}");
        }

        $result = Process::timeout(300)->run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to extract MongoDB archive: ' . $result->errorOutput());
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
            '--host=' . escapeshellarg($config['host']),
            '--port=' . escapeshellarg((string) ($config['port'] ?? 27017)),
            '--nsFrom=' . escapeshellarg("{$fromDb}.*"),
            '--nsTo=' . escapeshellarg("{$toDb}.*"),
            '--drop',
        ];

        if (! empty($config['username'])) {
            $parts[] = '--username=' . escapeshellarg($config['username']);
            $parts[] = '--password=' . escapeshellarg($config['password']);
            $parts[] = '--authenticationDatabase=' . escapeshellarg($config['auth_database'] ?? 'admin');
        }

        $parts[] = escapeshellarg($dumpPath);

        return implode(' ', $parts);
    }
}
