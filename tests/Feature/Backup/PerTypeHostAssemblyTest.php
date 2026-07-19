<?php

use App\Models\BackupHost;
use App\Models\BackupSource;

// Mirrors the per-type assembly rule the jobs use: host creds + host ssh + source selection.
function assembleTypeConf(BackupSource $source, string $type): ?array
{
    $hostId = $source->{$type.'_host_id'};
    if (! $hostId) {
        return null;
    }

    $host = BackupHost::find($hostId);

    return array_merge(
        $host->config[$type] ?? [],
        ['ssh' => $host->sshConfig()],
        $source->config[$type] ?? [],
    );
}

it('assembles mysql conf from host creds + host ssh + source databases', function () {
    $host = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->create([
        'config' => ['mysql' => ['databases' => ['shop']]],
        'mysql_host_id' => $host->id,
    ]);

    $conf = assembleTypeConf($source, 'mysql');

    expect($conf['host'])->toBe($host->config['mysql']['host']);
    expect($conf['user'])->toBe($host->config['mysql']['user']);
    expect($conf['databases'])->toBe(['shop']);
    expect($conf['ssh']['enabled'])->toBeTrue();
});

it('returns ssh disabled when host has no ssh configured', function () {
    $host = BackupHost::factory()->sshOnly()->state(fn () => [
        'config' => ['mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'x']],
    ])->create();
    $source = BackupSource::factory()->create([
        'config' => ['mysql' => ['databases' => ['a']]],
        'mysql_host_id' => $host->id,
    ]);

    $conf = assembleTypeConf($source, 'mysql');

    expect($conf['ssh'])->toBe(['enabled' => false]);
});

it('skips assembly when source has no host set for the type', function () {
    $source = BackupSource::factory()->create(['config' => []]);

    expect(assembleTypeConf($source, 'mysql'))->toBeNull();
});
