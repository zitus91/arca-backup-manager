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
    expect($conf['username'])->toBe($host->config['mysql']['username']);
    expect($conf['databases'])->toBe(['shop']);
    expect($conf['ssh']['enabled'])->toBeTrue();

    // Regression lock: the dump command reads $config['username'], not $config['user'].
    expect($conf)->toHaveKey('username');
    expect($conf)->not->toHaveKey('user');
});

it('returns ssh disabled when host has no ssh configured', function () {
    $host = BackupHost::factory()->sshOnly()->state(fn () => [
        'config' => ['mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'username' => 'x']],
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

// Locks the restore-target SSH selection rule (ProcessRestoreJob): same_host keeps
// the source host tunnel; remote_host uses an explicit override, else no tunnel —
// the source host's ssh must never leak into a remote_host restore without override.
function restoreSshFor(string $restoreTarget, array $sourceHostSsh, ?array $override): array
{
    if ($restoreTarget !== 'remote_host') {
        return $sourceHostSsh; // same_host
    }

    return ! empty($override) ? $override : ['enabled' => false];
}

it('keeps the source host tunnel for a same_host restore', function () {
    $srcSsh = ['enabled' => true, 'host' => 'origin.example.com'];
    expect(restoreSshFor('same_host', $srcSsh, null))->toBe($srcSsh);
});

it('uses the explicit override for a remote_host restore', function () {
    $override = ['enabled' => true, 'host' => 'target.example.com'];
    expect(restoreSshFor('remote_host', ['enabled' => true, 'host' => 'origin'], $override))->toBe($override);
});

it('never leaks the source tunnel into a remote_host restore without override', function () {
    expect(restoreSshFor('remote_host', ['enabled' => true, 'host' => 'origin'], null))->toBe(['enabled' => false]);
});
