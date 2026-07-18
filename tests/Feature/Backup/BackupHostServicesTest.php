<?php

use App\Models\BackupHost;

it('reports which services a host offers', function () {
    $host = BackupHost::factory()->withMysql()->withFilesystem()->create();

    expect($host->offers('mysql'))->toBeTrue();
    expect($host->offers('filesystem'))->toBeTrue();
    expect($host->offers('mongodb'))->toBeFalse();
});

it('returns ssh config or a disabled default', function () {
    $withSsh = BackupHost::factory()->create(); // default has ssh block
    expect($withSsh->sshConfig()['enabled'])->toBeTrue();

    $noSsh = BackupHost::factory()->sshOnly()->state(fn () => [
        'config' => ['mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'password' => 'x']],
    ])->create();
    expect($noSsh->sshConfig())->toBe(['enabled' => false]);
});

it('stores nested service credentials encrypted', function () {
    $host = BackupHost::factory()->withMysql()->create();
    expect($host->config['mysql']['user'])->not->toBeNull();
    expect($host->config['ssh']['host'])->not->toBeNull();
});
