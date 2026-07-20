<?php

use App\Services\Backup\FilesystemBackupService;
use App\Services\Backup\FtpStorageService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->remote = sys_get_temp_dir().'/ftpb_src_'.uniqid();
    File::ensureDirectoryExists($this->remote);
    File::put($this->remote.'/one.txt', 'x');
    File::put($this->remote.'/two.txt', 'y');

    $root = $this->remote;
    app()->bind(FtpStorageService::class, fn () => new class($root) extends FtpStorageService {
        public function __construct(public string $root) {}
        public function disk(array $config): \Illuminate\Contracts\Filesystem\Filesystem
        {
            return Storage::build(['driver' => 'local', 'root' => $this->root]);
        }
    });
});

it('backs up a filesystem source over ftp (always full)', function () {
    $out = sys_get_temp_dir().'/ftpb_out_'.uniqid();
    File::ensureDirectoryExists($out);

    $config = [
        'path' => '/', 'exclude_patterns' => [], 'transport' => 'ftp',
        'ftp' => ['host' => 'h', 'port' => 21, 'username' => 'u', 'password' => 'p', 'root_path' => '/', 'passive' => true, 'ssl' => false],
    ];

    $r = app(FilesystemBackupService::class)->backup($config, $out, 'gzip');

    expect(file_exists($r['file_path']))->toBeTrue();
    expect($r['file_size'])->toBeGreaterThan(0);

    File::deleteDirectory($this->remote);
    File::deleteDirectory($out);
});

it('forces a full backup when incremental is requested over ftp', function () {
    $out = sys_get_temp_dir().'/ftpb_out2_'.uniqid();
    File::ensureDirectoryExists($out);
    $config = ['path' => '/', 'exclude_patterns' => [], 'transport' => 'ftp',
        'ftp' => ['host' => 'h', 'port' => 21, 'username' => 'u', 'password' => 'p', 'root_path' => '/', 'passive' => true, 'ssl' => false]];

    $r = app(FilesystemBackupService::class)->incrementalBackup($config, $out, 'gzip', ['timestamp' => now()->subDay()->timestamp]);

    expect($r['meta']['incremental'] ?? true)->toBeFalse();
    expect($r)->not->toHaveKey('incremental_checkpoint');
    expect(file_exists($r['file_path']))->toBeTrue();

    File::deleteDirectory($out);
});
