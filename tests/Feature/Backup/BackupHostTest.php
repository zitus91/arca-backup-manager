<?php

use App\Models\BackupHost;
use App\Models\BackupSource;

it('creates a host with encrypted ssh config', function () {
    $host = BackupHost::factory()->create([
        'name' => 'prod-web-01',
        'config' => [
            'host' => 'ssh.example.com',
            'port' => 22,
            'user' => 'ubuntu',
            'auth_method' => 'key',
            'key_path' => '/home/ubuntu/.ssh/id_rsa',
            'password' => '',
        ],
    ]);

    expect($host->config['host'])->toBe('ssh.example.com');
    expect($host->is_active)->toBeTrue();
});

it('links a source to a host', function () {
    $host = BackupHost::factory()->create();
    $source = BackupSource::factory()->mysql()->create(['host_id' => $host->id]);

    expect($source->host->id)->toBe($host->id);
    expect($host->backupSources->pluck('id'))->toContain($source->id);
});

it('nulls source host_id when host deleted', function () {
    $host = BackupHost::factory()->create();
    $source = BackupSource::factory()->mysql()->create(['host_id' => $host->id]);

    $host->delete();

    expect($source->fresh()->host_id)->toBeNull();
});
