<?php

use App\Livewire\Backup\BackupLogIndex;
use App\Models\BackupJob;
use App\Models\BackupLog;
use Livewire\Livewire;

it('renders backup log index', function () {
    BackupLog::factory()->success()->create();

    Livewire::test(BackupLogIndex::class)
        ->assertStatus(200);
});

it('filters logs by job', function () {
    $job1 = BackupJob::factory()->create(['name' => 'Job One']);
    $job2 = BackupJob::factory()->create(['name' => 'Job Two']);

    $log1 = BackupLog::factory()->for($job1, 'job')->create();
    BackupLog::factory()->for($job2, 'job')->create();

    $component = Livewire::test(BackupLogIndex::class)
        ->set('filterJobId', (string) $job1->id);

    $visibleIds = $component->viewData('logs')->pluck('id')->all();
    expect($visibleIds)->toContain($log1->id);
    expect($visibleIds)->toHaveCount(1);
});

it('filters logs by status', function () {
    BackupLog::factory()->success()->create();
    BackupLog::factory()->failed()->create();

    Livewire::test(BackupLogIndex::class)
        ->set('filterStatus', 'success')
        ->assertSee(__('backup-log.status_success'));
});

it('paginates logs at 25 per page', function () {
    BackupLog::factory()->count(30)->create();

    Livewire::test(BackupLogIndex::class)
        ->assertStatus(200);
});

it('opens log detail modal', function () {
    $log = BackupLog::factory()->success()->create();

    Livewire::test(BackupLogIndex::class)
        ->call('openDetail', $log->id)
        ->assertSet('showDetail', true)
        ->assertSet('detailLogId', $log->id);
});

it('closes log detail modal', function () {
    $log = BackupLog::factory()->create();

    Livewire::test(BackupLogIndex::class)
        ->call('openDetail', $log->id)
        ->call('closeDetail')
        ->assertSet('showDetail', false)
        ->assertSet('detailLogId', null);
});
