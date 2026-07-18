<?php

use App\Models\BackupHost;
use App\Models\BackupSource;
use Illuminate\Support\Facades\Artisan;

function backfillHosts(): void
{
    // Re-run only the backfill migration logic against current data.
    (require database_path('migrations/2026_07_18_000003_backfill_backup_hosts_from_sources.php'))->up();
}

it('creates and links a host from inline ssh config', function () {
    $source = BackupSource::factory()->mysql()->create([
        'host_id' => null,
        'config' => [
            'mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'databases' => ['x']],
            'ssh' => [
                'enabled' => true,
                'host' => 'ssh.example.com',
                'port' => 22,
                'user' => 'ubuntu',
                'auth_method' => 'key',
                'key_path' => '/home/ubuntu/.ssh/id_rsa',
                'password' => '',
            ],
        ],
    ]);

    backfillHosts();

    $source->refresh();
    expect($source->host_id)->not->toBeNull();
    expect($source->host->config['host'])->toBe('ssh.example.com');
    expect(BackupHost::count())->toBe(1);
});

it('dedupes hosts sharing the same host/port/user', function () {
    $ssh = [
        'enabled' => true, 'host' => 'ssh.example.com', 'port' => 22,
        'user' => 'ubuntu', 'auth_method' => 'key', 'key_path' => '/k', 'password' => '',
    ];
    $a = BackupSource::factory()->mysql()->create(['host_id' => null, 'config' => ['mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'databases' => ['x']], 'ssh' => $ssh]]);
    $b = BackupSource::factory()->mongodb()->create(['host_id' => null, 'config' => ['mongodb' => ['host' => '127.0.0.1', 'port' => 27017, 'databases' => ['y']], 'ssh' => $ssh]]);

    backfillHosts();

    expect(BackupHost::count())->toBe(1);
    expect($a->fresh()->host_id)->toBe($b->fresh()->host_id);
});

it('skips sources without enabled ssh', function () {
    BackupSource::factory()->mysql()->create([
        'host_id' => null,
        'config' => ['mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'databases' => ['x']], 'ssh' => ['enabled' => false]],
    ]);

    backfillHosts();

    expect(BackupHost::count())->toBe(0);
});
