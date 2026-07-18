<?php

use App\Models\BackupHost;
use App\Models\BackupSource;

it('resolves ssh config from a linked host', function () {
    $host = BackupHost::factory()->create([
        'config' => [
            'host' => 'ssh.example.com',
            'port' => 2222,
            'user' => 'deploy',
            'auth_method' => 'key',
            'key_path' => '/root/.ssh/id_rsa',
            'password' => '',
        ],
    ]);
    $source = BackupSource::factory()->mysql()->create(['host_id' => $host->id]);

    $ssh = resolveSharedSsh($source);

    expect($ssh['enabled'])->toBeTrue();
    expect($ssh['host'])->toBe('ssh.example.com');
    expect($ssh['port'])->toBe(2222);
});

it('falls back to inline ssh config when no host linked', function () {
    $source = BackupSource::factory()->mysql()->create([
        'host_id' => null,
        'config' => [
            'mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'databases' => ['x']],
            'ssh' => ['enabled' => true, 'host' => 'legacy.example.com'],
        ],
    ]);

    $ssh = resolveSharedSsh($source);

    expect($ssh['host'])->toBe('legacy.example.com');
});

it('returns disabled ssh when neither host nor inline config present', function () {
    $source = BackupSource::factory()->mysql()->create(['host_id' => null]);
    $source->update(['config' => ['mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'databases' => ['x']]]]);

    expect(resolveSharedSsh($source)['enabled'])->toBeFalse();
});

function resolveSharedSsh(\App\Models\BackupSource $source): array
{
    $sourceConfig = $source->config;

    return $source->host
        ? array_merge($source->host->config, ['enabled' => true])
        : ($sourceConfig['ssh'] ?? ['enabled' => false]);
}
