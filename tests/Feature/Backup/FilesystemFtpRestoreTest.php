<?php

use App\Services\Backup\FtpStorageService;
use App\Services\Restore\FilesystemRestoreService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

it('restores a filesystem archive over ftp transport', function () {
    // Build a small tar.gz to restore
    $srcDir = sys_get_temp_dir().'/rst_src_'.uniqid();
    File::ensureDirectoryExists($srcDir.'/payload');
    File::put($srcDir.'/payload/hello.txt', 'hi');
    $archive = sys_get_temp_dir().'/rst_'.uniqid().'.tar.gz';
    Process::run('tar -czf '.escapeshellarg($archive).' -C '.escapeshellarg($srcDir).' payload');

    $remote = sys_get_temp_dir().'/rst_remote_'.uniqid();
    File::ensureDirectoryExists($remote);
    app()->bind(FtpStorageService::class, fn () => new class($remote) extends FtpStorageService
    {
        public function __construct(public string $root) {}

        public function disk(array $config): \Illuminate\Contracts\Filesystem\Filesystem
        {
            return Storage::build(['driver' => 'local', 'root' => $this->root]);
        }
    });

    $config = ['path' => '/var/www/site', 'transport' => 'ftp', 'ssh' => ['enabled' => false],
        'ftp' => ['host' => 'h', 'port' => 21, 'username' => 'u', 'password' => 'p', 'root_path' => '/', 'passive' => true, 'ssl' => false]];

    $r = app(FilesystemRestoreService::class)->restore($config, $archive, '/restored');

    // the extracted tree (payload/hello.txt) is pushed under the remote target
    expect(collect(File::allFiles($remote))->contains(fn ($f) => str_ends_with($f->getPathname(), 'hello.txt')))->toBeTrue();

    File::deleteDirectory($srcDir);
    File::deleteDirectory($remote);
    @unlink($archive);
});

it('wipes the ftp target directory when overriding', function () {
    $srcDir = sys_get_temp_dir().'/rst_src_'.uniqid();
    File::ensureDirectoryExists($srcDir.'/payload');
    File::put($srcDir.'/payload/hello.txt', 'hi');
    $archive = sys_get_temp_dir().'/rst_'.uniqid().'.tar.gz';
    Process::run('tar -czf '.escapeshellarg($archive).' -C '.escapeshellarg($srcDir).' payload');

    // a stale file the archive does not contain: override must remove it, not keep it
    $remote = sys_get_temp_dir().'/rst_remote_'.uniqid();
    File::ensureDirectoryExists($remote.'/restored');
    File::put($remote.'/restored/stale.txt', 'old');

    app()->bind(FtpStorageService::class, fn () => new class($remote) extends FtpStorageService
    {
        public function __construct(public string $root) {}

        public function disk(array $config): \Illuminate\Contracts\Filesystem\Filesystem
        {
            return Storage::build(['driver' => 'local', 'root' => $this->root]);
        }
    });

    $config = ['path' => '/var/www/site', 'transport' => 'ftp', 'ssh' => ['enabled' => false],
        'ftp' => ['host' => 'h', 'port' => 21, 'username' => 'u', 'password' => 'p', 'root_path' => '/', 'passive' => true, 'ssl' => false]];

    app(FilesystemRestoreService::class)->restore($config, $archive, '/restored', overrideExisting: true);

    expect(File::exists($remote.'/restored/stale.txt'))->toBeFalse()
        ->and(File::exists($remote.'/restored/payload/hello.txt'))->toBeTrue();

    File::deleteDirectory($srcDir);
    File::deleteDirectory($remote);
    @unlink($archive);
});

it('refuses an ftp restore into a populated target without override', function () {
    $remote = sys_get_temp_dir().'/rst_remote_'.uniqid();
    File::ensureDirectoryExists($remote.'/restored');
    File::put($remote.'/restored/stale.txt', 'old');

    app()->bind(FtpStorageService::class, fn () => new class($remote) extends FtpStorageService
    {
        public function __construct(public string $root) {}

        public function disk(array $config): \Illuminate\Contracts\Filesystem\Filesystem
        {
            return Storage::build(['driver' => 'local', 'root' => $this->root]);
        }
    });

    $config = ['path' => '/var/www/site', 'transport' => 'ftp', 'ssh' => ['enabled' => false], 'ftp' => []];

    try {
        app(FilesystemRestoreService::class)->restore($config, '/tmp/whatever.tar.gz', '/restored');
        expect(false)->toBeTrue('restore should have thrown');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('already exists on the FTP host');
    }

    File::deleteDirectory($remote);
});
