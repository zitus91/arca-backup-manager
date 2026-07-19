<?php

use App\Models\BackupHost;
use App\Models\BackupSource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

function runMultiServiceBackfill(): void
{
    (require database_path('migrations/2026_07_18_100005_migrate_hosts_to_multiservice.php'))->up();
}

// RefreshDatabase applies every migration (including the one under test, which
// drops backup_sources.host_id) before each test's arrange step runs. Re-add the
// column here to reconstruct the pre-migration legacy state these tests build
// fixtures against.
beforeEach(function () {
    if (! Schema::hasColumn('backup_sources', 'host_id')) {
        Schema::table('backup_sources', function (Blueprint $table) {
            $table->foreignId('host_id')->nullable()->after('user_id')->constrained('backup_hosts')->nullOnDelete();
        });
    }
});

it('converts a legacy flat-ssh host to nested and backfills a mysql source', function () {
    // Legacy host row: flat ssh shape (pre-multiservice)
    $host = BackupHost::factory()->create(['config' => [
        'host' => 'ssh.example.com', 'port' => 22, 'user' => 'ubuntu',
        'auth_method' => 'key', 'key_path' => '/k', 'password' => '',
    ]]);

    // Legacy source: inline mysql creds + link to the ssh host + databases
    $source = BackupSource::factory()->create([
        'config' => ['mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'password' => 'secret', 'databases' => ['app']]],
        'host_id' => $host->id,
        'mysql_host_id' => null,
    ]);

    runMultiServiceBackfill();

    $source->refresh();
    expect($source->mysql_host_id)->not->toBeNull();
    $mysqlHost = BackupHost::find($source->mysql_host_id);
    expect($mysqlHost->config['mysql']['user'])->toBe('root');
    expect($mysqlHost->config['ssh']['host'])->toBe('ssh.example.com');
    expect($source->config['mysql'])->toBe(['databases' => ['app']]);
});

it('is idempotent', function () {
    $host = BackupHost::factory()->create(['config' => ['host' => 'h', 'port' => 22, 'user' => 'u', 'auth_method' => 'key', 'key_path' => '/k', 'password' => '']]);
    $source = BackupSource::factory()->create(['config' => ['mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'password' => 'p', 'databases' => ['x']]], 'host_id' => $host->id]);

    runMultiServiceBackfill();
    $countAfterFirst = BackupHost::count();
    runMultiServiceBackfill();

    expect(BackupHost::count())->toBe($countAfterFirst);
});

it('dedupes two legacy sources sharing the same mysql creds + ssh onto one host', function () {
    $sshHost = BackupHost::factory()->create(['config' => [
        'host' => 'ssh.shared.com', 'port' => 22, 'user' => 'ubuntu',
        'auth_method' => 'key', 'key_path' => '/k', 'password' => '',
    ]]);

    $mysql = ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'password' => 'p'];
    $a = BackupSource::factory()->create(['config' => ['mysql' => $mysql + ['databases' => ['app']]], 'host_id' => $sshHost->id]);
    $b = BackupSource::factory()->create(['config' => ['mysql' => $mysql + ['databases' => ['shop']]], 'host_id' => $sshHost->id]);

    runMultiServiceBackfill();

    $a->refresh();
    $b->refresh();
    expect($a->mysql_host_id)->not->toBeNull();
    expect($b->mysql_host_id)->toBe($a->mysql_host_id); // reused, not duplicated
    // one flattened ssh host + exactly one shared mysql host
    expect(BackupHost::whereNotNull('id')->get()->filter(fn ($h) => isset($h->config['mysql']))->count())->toBe(1);
});

it('backfills a legacy filesystem source through its ssh host', function () {
    $sshHost = BackupHost::factory()->create(['config' => [
        'host' => 'web.example.com', 'port' => 22, 'user' => 'deploy',
        'auth_method' => 'key', 'key_path' => '/k', 'password' => '',
    ]]);
    $source = BackupSource::factory()->create([
        'config' => ['filesystem' => ['paths' => ['/var/www'], 'exclude_patterns' => '*.log']],
        'host_id' => $sshHost->id,
    ]);

    runMultiServiceBackfill();

    $source->refresh();
    expect($source->filesystem_host_id)->not->toBeNull();
    $fsHost = BackupHost::find($source->filesystem_host_id);
    expect($fsHost->offers('filesystem'))->toBeTrue();
    expect($fsHost->config['ssh']['host'])->toBe('web.example.com');
    expect($source->config['filesystem'])->toBe(['paths' => ['/var/www'], 'exclude_patterns' => '*.log']);
});
