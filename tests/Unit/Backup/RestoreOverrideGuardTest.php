<?php

use App\Services\Restore\FilesystemRestoreService;
use App\Services\Restore\MysqlRestoreService;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->target = sys_get_temp_dir().'/restore_guard_'.uniqid();
    mkdir($this->target, 0755, true);
});

afterEach(function () {
    Process::run('rm -rf '.escapeshellarg($this->target));
});

it('refuses to extract into a non-empty directory without override', function () {
    file_put_contents($this->target.'/keep.txt', 'live data');

    (new FilesystemRestoreService)->restore(
        ['path' => '/var/www/site'],
        __DIR__.'/does-not-matter.tar.gz',
        $this->target,
        overrideExisting: false,
    );
})->throws(RuntimeException::class, 'already exists and is not empty');

it('extracts into an existing but empty directory', function () {
    Process::fake(['*' => Process::result(output: '0')]);

    $result = (new FilesystemRestoreService)->restore(
        ['path' => '/var/www/site'],
        '/tmp/a.tar.gz',
        $this->target,
        overrideExisting: false,
    );

    expect($result['restored_path'])->toBe($this->target);
});

it('refuses to import into an existing mysql database without override', function () {
    Process::fake(['*' => Process::result(output: '1')]); // information_schema lookup finds it

    (new MysqlRestoreService)->restore(
        ['host' => 'db', 'port' => 3306, 'username' => 'root', 'password' => 'x', 'database' => 'wp_cavicchi'],
        '/tmp/dump.sql',
        'wp_cavicchi',
        overrideExisting: false,
    );
})->throws(RuntimeException::class, "Target database 'wp_cavicchi' already exists");

it('imports when the mysql target database does not exist', function () {
    Process::fake(['*' => Process::result(output: '')]); // lookup returns nothing

    $result = (new MysqlRestoreService)->restore(
        ['host' => 'db', 'port' => 3306, 'username' => 'root', 'password' => 'x', 'database' => 'wp_cavicchi'],
        '/tmp/dump.sql',
        'wp_cavicchi_restored',
        overrideExisting: false,
    );

    expect($result['restored_db_name'])->toBe('wp_cavicchi_restored');
});
