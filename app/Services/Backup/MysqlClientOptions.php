<?php

namespace App\Services\Backup;

class MysqlClientOptions
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
}
