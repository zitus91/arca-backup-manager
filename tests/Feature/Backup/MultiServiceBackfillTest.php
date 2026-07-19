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
