<?php

use App\Services\Backup\MysqlClient;
use Illuminate\Support\Facades\Process;

it('lists user databases with the same client the backup uses', function () {
    Process::fake([
        '*' => Process::result(output: "information_schema\nshop\nblog\nmysql\n", exitCode: 0),
    ]);

    $databases = MysqlClient::listDatabases([
        'host' => 'db.internal',
        'port' => 3306,
        'username' => 'root',
        'password' => 'secret',
    ]);

    expect($databases)->toBe(['shop', 'blog']);

    Process::assertRan(function ($process) {
        $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;

        // Same binary and same connection flags as the dump, or a green test would
        // keep promising something the backup cannot do.
        return str_starts_with($cmd, 'mysql ') && str_contains($cmd, '--skip-ssl');
    });
});

it('surfaces the client error instead of reporting a working connection', function () {
    Process::fake([
        '*' => Process::result(
            output: '',
            errorOutput: 'ERROR 2026 (HY000): TLS/SSL error: SSL is required, but the server does not support it',
            exitCode: 1,
        ),
    ]);

    expect(fn () => MysqlClient::listDatabases(['host' => 'db.internal', 'username' => 'root', 'password' => 'x']))
        ->toThrow(RuntimeException::class, 'SSL is required');
});
