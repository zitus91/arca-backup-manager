<?php

use App\Models\BackupHost;

it('does not use ssh for mysql when the service opted out even though the host has ssh', function () {
    $host = BackupHost::factory()->create(['config' => [
        'ssh' => ['enabled' => true, 'host' => 'jump.example.com', 'port' => 22, 'user' => 'u', 'auth_method' => 'key', 'key_path' => '/k', 'password' => ''],
        'mysql' => ['host' => 'db.example.com', 'port' => 3306, 'username' => 'root', 'password' => 'p', 'use_ssh' => false],
    ]]);

    expect($host->usesSshFor('mysql'))->toBeFalse();
});

it('uses ssh for mysql by default when the host has ssh (backward compatible)', function () {
    $host = BackupHost::factory()->create(['config' => [
        'ssh' => ['enabled' => true, 'host' => 'j', 'port' => 22, 'user' => 'u', 'auth_method' => 'key', 'key_path' => '/k', 'password' => ''],
        'mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'username' => 'root', 'password' => 'p'],
        // no use_ssh key
    ]]);

    expect($host->usesSshFor('mysql'))->toBeTrue();
});

it('never uses ssh when the host has no ssh block', function () {
    $host = BackupHost::factory()->create(['config' => [
        'mysql' => ['host' => 'x', 'port' => 3306, 'username' => 'r', 'password' => 'p'],
    ]]);

    expect($host->usesSshFor('mysql'))->toBeFalse();
});

it('assembles mysql conf with disabled ssh when the service opts out', function () {
    $host = BackupHost::factory()->create(['config' => [
        'ssh' => ['enabled' => true, 'host' => 'j', 'port' => 22, 'user' => 'u', 'auth_method' => 'key', 'key_path' => '/k', 'password' => ''],
        'mysql' => ['host' => 'db', 'port' => 3306, 'username' => 'root', 'password' => 'p', 'use_ssh' => false],
    ]]);

    $ssh = $host->usesSshFor('mysql') ? $host->sshConfig() : ['enabled' => false];

    expect($ssh)->toBe(['enabled' => false]);
});
