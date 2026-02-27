<?php

use App\Services\Backup\MysqlBackupService;
use Illuminate\Support\Facades\Process;

it('builds correct mysqldump command', function () {
    Process::fake([
        '*' => Process::result(output: '', errorOutput: '', exitCode: 0),
    ]);

    $service = new MysqlBackupService();

    $config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'testdb',
        'username' => 'root',
        'password' => 'secret',
        'tables' => null,
    ];

    $tmpDir = sys_get_temp_dir() . '/backup_test_' . uniqid();
    @mkdir($tmpDir, 0755, true);

    try {
        $service->dump($config, $tmpDir, 'none');

        Process::assertRan(function ($process) {
            $cmd = $process->command;
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

    $service = new MysqlBackupService();

    $config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'testdb',
        'username' => 'root',
        'password' => 'secret',
        'tables' => ['users', 'orders'],
    ];

    $tmpDir = sys_get_temp_dir() . '/backup_test_' . uniqid();
    @mkdir($tmpDir, 0755, true);

    try {
        $service->dump($config, $tmpDir, 'none');

        Process::assertRan(function ($process) {
            $cmd = $process->command;
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

    $service = new MysqlBackupService();

    $config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'testdb',
        'username' => 'root',
        'password' => 'wrong',
        'tables' => null,
    ];

    $tmpDir = sys_get_temp_dir() . '/backup_test_' . uniqid();
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
