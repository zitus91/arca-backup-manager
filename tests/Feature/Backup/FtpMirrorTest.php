<?php

use App\Services\Backup\FtpStorageService;
use Illuminate\Support\Facades\File;

it('mirrors remote tree down into local dir applying excludes', function () {
    // Arrange source tree on temp "remote" dir, exposed via local-driver disk.
    $remoteRoot = sys_get_temp_dir().'/ftpsrc_'.uniqid();
    File::ensureDirectoryExists($remoteRoot.'/sub');
    File::put($remoteRoot.'/keep.txt', 'a');
    File::put($remoteRoot.'/skip.log', 'b');
    File::put($remoteRoot.'/sub/deep.txt', 'c');

    $localDir = sys_get_temp_dir().'/ftpdst_'.uniqid();

    $svc = new class extends FtpStorageService
    {
        public string $root = '';

        public function disk(array $config): \Illuminate\Contracts\Filesystem\Filesystem
        {
            return \Illuminate\Support\Facades\Storage::build(['driver' => 'local', 'root' => $this->root]);
        }
    };
    $svc->root = $remoteRoot;

    $count = $svc->mirrorDown([], '/', $localDir, ['*.log']);

    expect(file_exists($localDir.'/keep.txt'))->toBeTrue();
    expect(file_exists($localDir.'/sub/deep.txt'))->toBeTrue();
    expect(file_exists($localDir.'/skip.log'))->toBeFalse();
    expect($count)->toBe(2);

    File::deleteDirectory($localDir);
    File::deleteDirectory($remoteRoot);
});

it('mirrors local dir up into remote tree', function () {
    $localRoot = sys_get_temp_dir().'/up_src_'.uniqid();
    File::ensureDirectoryExists($localRoot.'/x');
    File::put($localRoot.'/a.txt', '1');
    File::put($localRoot.'/x/b.txt', '2');

    $remoteRoot = sys_get_temp_dir().'/up_dst_'.uniqid();
    File::ensureDirectoryExists($remoteRoot);

    $svc = new class extends FtpStorageService
    {
        public string $root = '';

        public function disk(array $config): \Illuminate\Contracts\Filesystem\Filesystem
        {
            return \Illuminate\Support\Facades\Storage::build(['driver' => 'local', 'root' => $this->root]);
        }
    };
    $svc->root = $remoteRoot;

    $count = $svc->mirrorUp([], $localRoot, '/dest');

    expect(file_exists($remoteRoot.'/dest/a.txt'))->toBeTrue();
    expect(file_exists($remoteRoot.'/dest/x/b.txt'))->toBeTrue();
    expect($count)->toBe(2);

    File::deleteDirectory($localRoot);
    File::deleteDirectory($remoteRoot);
});
