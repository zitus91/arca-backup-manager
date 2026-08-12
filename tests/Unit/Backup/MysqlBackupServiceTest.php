<?php

use App\Services\Backup\MysqlBackupService;
use Illuminate\Support\Facades\Process;

it('builds correct mysqldump command', function () {
    Process::fake([
        '*' => Process::result(output: '', errorOutput: '', exitCode: 0),
    ]);

    $service = new MysqlBackupService;

    $config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'testdb',
        'username' => 'root',
        'password' => 'secret',
        'tables' => null,
    ];

    $tmpDir = sys_get_temp_dir().'/backup_test_'.uniqid();
    @mkdir($tmpDir, 0755, true);

    try {
        $service->dump($config, $tmpDir, 'none');

        Process::assertRan(function ($process) {
            // The dump runs through `bash -o pipefail -c`, so the command is an array.
            $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;

            return str_contains($cmd, 'mysqldump')
                && str_contains($cmd, '--host=')
                && str_contains($cmd, '--user=')
                && str_contains($cmd, '--single-transaction');
        });
    } finally {
        @array_map('unlink', glob("$tmpDir/*"));
        @rmdir($tmpDir);
    }
});

it('includes specific tables when configured', function () {
    Process::fake([
        '*' => Process::result(output: '', errorOutput: '', exitCode: 0),
    ]);

    $service = new MysqlBackupService;

    $config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'testdb',
        'username' => 'root',
        'password' => 'secret',
        'tables' => ['users', 'orders'],
    ];

    $tmpDir = sys_get_temp_dir().'/backup_test_'.uniqid();
    @mkdir($tmpDir, 0755, true);

    try {
        $service->dump($config, $tmpDir, 'none');

        Process::assertRan(function ($process) {
            // The dump runs through `bash -o pipefail -c`, so the command is an array.
            $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;

            return str_contains($cmd, "'users'")
                && str_contains($cmd, "'orders'");
        });
    } finally {
        @array_map('unlink', glob("$tmpDir/*"));
        @rmdir($tmpDir);
    }
});

it('throws exception on mysqldump failure', function () {
    Process::fake([
        '*' => Process::result(
            output: '',
            errorOutput: 'Access denied',
            exitCode: 1,
        ),
    ]);

    $service = new MysqlBackupService;

    $config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'testdb',
        'username' => 'root',
        'password' => 'wrong',
        'tables' => null,
    ];

    $tmpDir = sys_get_temp_dir().'/backup_test_'.uniqid();
    @mkdir($tmpDir, 0755, true);

    try {
        $service->dump($config, $tmpDir, 'none');
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toContain('mysqldump failed');

        return;
    } finally {
        @array_map('unlink', glob("$tmpDir/*"));
        @rmdir($tmpDir);
    }

    $this->fail('Expected RuntimeException was not thrown');
});

it('fails instead of writing an empty archive when the dump command fails', function () {
    // No Process::fake here: the point is that the real shell pipeline reports the
    // dump's exit code and not gzip's. mysqldump cannot succeed against these
    // credentials (or is absent entirely), so the archive must never be published.
    $tmpDir = sys_get_temp_dir().'/mysql_pipefail_'.uniqid();
    @mkdir($tmpDir, 0755, true);

    $service = new MysqlBackupService;

    try {
        expect(fn () => $service->dump([
            'host' => '127.0.0.1',
            'port' => 1,
            'username' => 'nobody',
            'password' => 'nothing',
            'database' => 'missing_db',
        ], $tmpDir, 'gzip'))->toThrow(RuntimeException::class);
    } finally {
        @array_map('unlink', glob("$tmpDir/*"));
        @rmdir($tmpDir);
    }
});

it('disables TLS by default and enables it only when the host asks for it', function (bool $ssl, string $expected) {
    Process::fake(['*' => Process::result(output: '', errorOutput: '', exitCode: 0)]);

    $tmpDir = sys_get_temp_dir().'/backup_ssl_'.uniqid();
    @mkdir($tmpDir, 0755, true);

    $config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'testdb',
        'username' => 'root',
        'password' => 'secret',
    ];

    if ($ssl) {
        $config['ssl'] = true;
    }

    try {
        (new MysqlBackupService)->dump($config, $tmpDir, 'none');

        Process::assertRan(function ($process) use ($expected) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;

            return str_contains($cmd, $expected);
        });
    } finally {
        @array_map('unlink', glob("$tmpDir/*"));
        @rmdir($tmpDir);
    }
})->with([
    // The bundled MariaDB client requires TLS unless told otherwise, which is what
    // broke every backup against a server without it.
    'plaintext by default' => [false, '--skip-ssl'],
    'TLS when enabled' => [true, '--ssl --skip-ssl-verify-server-cert'],
]);
