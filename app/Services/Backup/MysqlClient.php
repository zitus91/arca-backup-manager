<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;

class MysqlClient
{
    /**
     * Connection flags shared by every mysql/mysqldump invocation.
     *
     * The MariaDB client bundled since Debian's 11.8 packages enables --ssl by default,
     * so a server without TLS is refused with 'error 2026: SSL is required, but the
     * server does not support it'. TLS is therefore opt-in per host: hosts saved before
     * this option existed keep connecting in plaintext, which is what they already did.
     *
     * Certificate verification stays off when TLS is on, because self-hosted servers
     * almost always present a self-signed certificate and the client verifies by default.
     *
     * ponytail: no --ssl-ca/--ssl-cert options, add them when a verified chain is needed.
     */
    public static function sslFlags(array $config): string
    {
        return ! empty($config['ssl'])
            ? '--ssl --skip-ssl-verify-server-cert'
            : '--skip-ssl';
    }

    /**
     * List the user databases on a host.
     *
     * This runs the same binary with the same flags the backup will use, so a green
     * connection test cannot promise something the backup then fails to do. Testing
     * over PDO used to report success for a configuration mysqldump could not connect
     * with at all, since PDO does not enforce TLS.
     *
     * @throws \RuntimeException with the client's own error message
     */
    public static function listDatabases(array $config): array
    {
        $cmd = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s --connect-timeout=5 -N -e %s',
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username'] ?? 'root'),
            escapeshellarg($config['password'] ?? ''),
            self::sslFlags($config),
            escapeshellarg('SHOW DATABASES'),
        );

        $result = Process::timeout(30)->run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException(trim($result->errorOutput()) ?: 'mysql connection failed');
        }

        $databases = array_filter(array_map('trim', explode("\n", trim($result->output()))));

        return array_values(array_diff($databases, ['information_schema', 'mysql', 'performance_schema', 'sys']));
    }
}
