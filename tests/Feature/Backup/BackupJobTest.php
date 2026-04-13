<?php

use App\Jobs\Backup\ProcessBackupJob;
use App\Livewire\Backup\BackupJobForm;
use App\Livewire\Backup\BackupJobIndex;
use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\BackupSource;
use App\Models\BackupStorageDestination;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('renders backup job index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    BackupJob::factory()->create();

    Livewire::test(BackupJobIndex::class)
        ->assertStatus(200);
});

it('creates a daily backup job', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $source = BackupSource::factory()->mysql()->create();
    $dest = BackupStorageDestination::factory()->s3()->create();

    Livewire::test(BackupJobForm::class)
        ->set('name', 'Daily DB Backup')
        ->set('backup_source_id', (string) $source->id)
        ->set('backup_storage_destination_id', (string) $dest->id)
        ->set('schedule_type', 'daily')
        ->set('schedule_time', '03:00')
        ->set('retention_count', 7)
        ->set('compression', 'gzip')
        ->set('notify_on_failure', true)
        ->set('notification_emails', ['admin@example.com'])
        ->call('save')
        ->assertDispatched('job-saved');

    $job = BackupJob::first();
    expect($job->name)->toBe('Daily DB Backup');
    expect($job->schedule_type)->toBe('daily');
    expect($job->next_run_at)->not->toBeNull();
});

it('creates a weekly backup job', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $source = BackupSource::factory()->mysql()->create();
    $dest = BackupStorageDestination::factory()->s3()->create();

    Livewire::test(BackupJobForm::class)
        ->set('name', 'Weekly Backup')
        ->set('backup_source_id', (string) $source->id)
        ->set('backup_storage_destination_id', (string) $dest->id)
        ->set('schedule_type', 'weekly')
        ->set('schedule_time', '02:00')
        ->set('schedule_day_of_week', '1')
        ->set('retention_count', 4)
        ->set('compression', 'zip')
        ->call('save')
        ->assertDispatched('job-saved');

    $job = BackupJob::first();
    expect($job->schedule_day_of_week)->toBe(1);
});

it('creates a monthly backup job', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $source = BackupSource::factory()->filesystem()->create();
    $dest = BackupStorageDestination::factory()->ftp()->create();

    Livewire::test(BackupJobForm::class)
        ->set('name', 'Monthly FS Backup')
        ->set('backup_source_id', (string) $source->id)
        ->set('backup_storage_destination_id', (string) $dest->id)
        ->set('schedule_type', 'monthly')
        ->set('schedule_time', '01:00')
        ->set('schedule_day_of_month', '15')
        ->set('retention_count', 12)
        ->call('save')
        ->assertDispatched('job-saved');

    $job = BackupJob::first();
    expect($job->schedule_day_of_month)->toBe(15);
});

it('creates a custom cron backup job', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $source = BackupSource::factory()->mongodb()->create();
    $dest = BackupStorageDestination::factory()->s3()->create();

    Livewire::test(BackupJobForm::class)
        ->set('name', 'Custom Cron Backup')
        ->set('backup_source_id', (string) $source->id)
        ->set('backup_storage_destination_id', (string) $dest->id)
        ->set('schedule_type', 'custom')
        ->set('schedule_cron', '0 */6 * * *')
        ->set('retention_count', 5)
        ->call('save')
        ->assertDispatched('job-saved');

    $job = BackupJob::first();
    expect($job->schedule_cron)->toBe('0 */6 * * *');
});

it('validates schedule fields for daily', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $source = BackupSource::factory()->create();
    $dest = BackupStorageDestination::factory()->create();

    Livewire::test(BackupJobForm::class)
        ->set('name', 'Test')
        ->set('backup_source_id', (string) $source->id)
        ->set('backup_storage_destination_id', (string) $dest->id)
        ->set('schedule_type', 'daily')
        ->set('schedule_time', '')
        ->call('save')
        ->assertHasErrors(['schedule_time']);
});

it('requires email when notifications enabled', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $source = BackupSource::factory()->create();
    $dest = BackupStorageDestination::factory()->create();

    Livewire::test(BackupJobForm::class)
        ->set('name', 'Test Job')
        ->set('backup_source_id', (string) $source->id)
        ->set('backup_storage_destination_id', (string) $dest->id)
        ->set('schedule_type', 'manual')
        ->set('notify_on_failure', true)
        ->set('notification_emails', [])
        ->call('save')
        ->assertHasErrors(['notification_emails']);
});

it('dispatches a job for immediate execution', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);
    $job = BackupJob::factory()->create();

    Livewire::test(BackupJobIndex::class)
        ->call('runNow', $job->id);

    Queue::assertPushed(ProcessBackupJob::class);
    $log = BackupLog::first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe('pending');
});

it('deletes a backup job', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $job = BackupJob::factory()->create();

    Livewire::test(BackupJobIndex::class)
        ->call('delete', $job->id);

    expect(BackupJob::count())->toBe(0);
});
