<?php

use App\Livewire\Backup\RestoreIndex;
use App\Models\BackupHost;
use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\RestoreLog;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders restore index', function () {
    Livewire::test(RestoreIndex::class)
        ->assertStatus(200);
});

it('shows restore logs', function () {
    RestoreLog::factory()->success()->count(3)->create(['user_id' => auth()->id()]);

    $component = Livewire::test(RestoreIndex::class);

    expect($component->viewData('restoreLogs'))->toHaveCount(3);
});

it('filters available backups by job', function () {
    $job1 = BackupJob::factory()->create(['name' => 'Job A']);
    $job2 = BackupJob::factory()->create(['name' => 'Job B']);

    BackupLog::factory()->success()->for($job1, 'job')->create(['storage_path' => 'backups/a.gz']);
    BackupLog::factory()->success()->for($job2, 'job')->create(['storage_path' => 'backups/b.gz']);

    $component = Livewire::test(RestoreIndex::class)
        ->set('filterJobId', (string) $job1->id);

    expect($component->viewData('backups'))->toHaveCount(1);
    expect($component->viewData('backups')->first()->backup_job_id)->toBe($job1->id);
});

it('opens detail modal for a restore log', function () {
    $restoreLog = RestoreLog::factory()->success()->create();

    Livewire::test(RestoreIndex::class)
        ->call('openDetail', $restoreLog->id)
        ->assertSet('showDetail', true)
        ->assertSet('detailRestoreLogId', $restoreLog->id);
});

it('closes detail modal', function () {
    $restoreLog = RestoreLog::factory()->success()->create();

    Livewire::test(RestoreIndex::class)
        ->call('openDetail', $restoreLog->id)
        ->call('closeDetail')
        ->assertSet('showDetail', false)
        ->assertSet('detailRestoreLogId', null);
});

it('rejects restore modal for non-success backup logs', function () {
    $backupLog = BackupLog::factory()->failed()->create();

    Livewire::test(RestoreIndex::class)
        ->call('openRestoreModal', $backupLog->id)
        ->assertSet('showRestoreModal', false);
});

it('dispatches restore job and creates restore log', function () {
    Queue::fake();

    $backupLog = BackupLog::factory()->success()->create([
        'storage_path' => 'backups/test/backup.sql.gz',
    ]);

    Livewire::test(RestoreIndex::class)
        ->call('openRestoreModal', $backupLog->id)
        ->set('restoreType', 'full')
        ->call('confirmRestore')
        ->call('executeRestore');

    Queue::assertPushed(\App\Jobs\Restore\ProcessRestoreJob::class);

    $this->assertDatabaseHas('restore_logs', [
        'backup_log_id' => $backupLog->id,
        'status' => 'pending',
    ]);
});

it('prefills remote config from a registered host, ssh tunnel included', function () {
    $host = BackupHost::factory()->withMysql()->withFilesystem()->create();

    $backupLog = BackupLog::factory()->success()->create([
        'storage_path' => 'backups/test/backup.sql.gz',
    ]);

    $component = Livewire::test(RestoreIndex::class)
        ->call('openRestoreModal', $backupLog->id)
        ->set('restoreTarget', 'known_host')
        ->set('knownHostId', $host->id);

    $mysql = $component->get('remoteConfig')['mysql'];
    expect($mysql['host'])->toBe('127.0.0.1');
    expect($mysql['username'])->toBe('root');
    expect($mysql['ssh']['enabled'])->toBeTrue();
    expect($mysql['ssh']['host'])->toBe($host->config['ssh']['host']);

    // Filesystem section is the ssh config itself: keys must match SshTunnelService
    $filesystem = $component->get('remoteConfig')['filesystem'];
    expect($filesystem)->toHaveKeys(['enabled', 'host', 'port', 'user', 'key_path']);
    expect($filesystem['user'])->toBe('ubuntu');
});

it('never tunnels through the source host when a remote host is typed manually', function () {
    $backupLog = BackupLog::factory()->success()->create([
        'storage_path' => 'backups/test/backup.sql.gz',
    ]);

    $component = Livewire::test(RestoreIndex::class)
        ->call('openRestoreModal', $backupLog->id)
        ->set('restoreTarget', 'remote_host');

    expect($component->get('remoteConfig')['mysql']['ssh'])->toBe(['enabled' => false]);
});
