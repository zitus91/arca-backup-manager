<?php

use App\Console\Commands\RecoverStaleBackupJobs;
use App\Events\Backup\BackupJobCompleted;
use App\Models\BackupJob;
use App\Models\BackupLog;
use Illuminate\Support\Facades\Event;

it('recovers stale running jobs', function () {
    Event::fake();

    $job = BackupJob::factory()->create();
    $staleLog = BackupLog::factory()->create([
        'backup_job_id' => $job->id,
        'status' => 'running',
        'started_at' => now()->subMinutes(80),
    ]);

    $this->artisan('backup:recover-stale-jobs', ['--minutes' => 70])
        ->assertSuccessful();

    $staleLog->refresh();
    expect($staleLog->status)->toBe('failed');
    expect($staleLog->error_message)->toContain('worker crashed');
    expect($staleLog->finished_at)->not->toBeNull();

    Event::assertDispatched(BackupJobCompleted::class, function ($event) use ($staleLog) {
        return $event->logId === $staleLog->id && $event->status === 'failed';
    });
});

it('recovers stale pending jobs', function () {
    Event::fake();

    $job = BackupJob::factory()->create();
    $staleLog = BackupLog::factory()->create([
        'backup_job_id' => $job->id,
        'status' => 'pending',
        'started_at' => now()->subMinutes(90),
    ]);

    $this->artisan('backup:recover-stale-jobs')
        ->assertSuccessful();

    $staleLog->refresh();
    expect($staleLog->status)->toBe('failed');
});

it('does not touch recent running jobs', function () {
    Event::fake();

    $job = BackupJob::factory()->create();
    $recentLog = BackupLog::factory()->create([
        'backup_job_id' => $job->id,
        'status' => 'running',
        'started_at' => now()->subMinutes(10),
    ]);

    $this->artisan('backup:recover-stale-jobs', ['--minutes' => 70])
        ->assertSuccessful();

    $recentLog->refresh();
    expect($recentLog->status)->toBe('running');

    Event::assertNotDispatched(BackupJobCompleted::class);
});

it('does not touch already completed jobs', function () {
    Event::fake();

    $job = BackupJob::factory()->create();
    $successLog = BackupLog::factory()->create([
        'backup_job_id' => $job->id,
        'status' => 'success',
        'started_at' => now()->subHours(5),
    ]);

    $this->artisan('backup:recover-stale-jobs')
        ->assertSuccessful();

    $successLog->refresh();
    expect($successLog->status)->toBe('success');

    Event::assertNotDispatched(BackupJobCompleted::class);
});

it('reports no stale jobs when none exist', function () {
    $this->artisan('backup:recover-stale-jobs')
        ->expectsOutput('No stale backup jobs found.')
        ->assertSuccessful();
});

it('accepts custom minutes threshold', function () {
    Event::fake();

    $job = BackupJob::factory()->create();

    // Stale only under 30-minute threshold
    BackupLog::factory()->create([
        'backup_job_id' => $job->id,
        'status' => 'running',
        'started_at' => now()->subMinutes(35),
    ]);

    $this->artisan('backup:recover-stale-jobs', ['--minutes' => 30])
        ->assertSuccessful();

    Event::assertDispatched(BackupJobCompleted::class);
});
