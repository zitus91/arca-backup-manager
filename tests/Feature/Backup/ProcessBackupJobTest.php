<?php

use App\Events\Backup\BackupJobCompleted;
use App\Events\Backup\BackupJobStarted;
use App\Jobs\Backup\ProcessBackupJob;
use App\Models\BackupHost;
use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\BackupSource;
use App\Models\BackupStorageDestination;
use App\Services\Backup\MysqlBackupService;
use App\Services\Backup\S3StorageService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

it('processes a backup job successfully', function () {
    Event::fake();
    Mail::fake();

    $mysqlHost = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->mysql()->create(['mysql_host_id' => $mysqlHost->id]);
    $dest = BackupStorageDestination::factory()->s3()->create();
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => $dest->id,
        'compression' => 'gzip',
        'notify_on_success' => true,
        'notification_emails' => ['admin@test.com'],
    ]);
    $log = BackupLog::factory()->pending()->create([
        'backup_job_id' => $job->id,
        'started_at' => now(),
    ]);

    // Mock services
    $mockMysql = \Mockery::mock(MysqlBackupService::class);
    $mockMysql->shouldReceive('dump')->once()->andReturn([
        'file_name' => 'backup.sql.gz',
        'file_path' => '/tmp/backup.sql.gz',
        'file_size' => 1024,
        'meta' => ['tables_dumped' => 10],
    ]);

    $mockS3 = \Mockery::mock(S3StorageService::class);
    $mockS3->shouldReceive('upload')->once()->andReturn('backups/test/backup.sql.gz');
    $mockS3->shouldReceive('delete')->andReturn(true);

    app()->instance(MysqlBackupService::class, $mockMysql);
    app()->instance(S3StorageService::class, $mockS3);

    $processJob = new ProcessBackupJob($job->id, $log->id);
    $processJob->handle(
        $mockMysql,
        app(\App\Services\Backup\MongodbBackupService::class),
        app(\App\Services\Backup\FilesystemBackupService::class),
        $mockS3,
        app(\App\Services\Backup\FtpStorageService::class),
        app(\App\Services\Backup\BackupSchedulerService::class),
    );

    $log->refresh();
    expect($log->status)->toBe('success');
    expect($log->file_name)->toBe('backup.sql.gz');

    Event::assertDispatched(BackupJobStarted::class);
    Event::assertDispatched(BackupJobCompleted::class, function ($event) {
        return $event->status === 'success';
    });
});

it('handles backup job failure', function () {
    Event::fake();
    Mail::fake();

    $mysqlHost = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->mysql()->create(['mysql_host_id' => $mysqlHost->id]);
    $dest = BackupStorageDestination::factory()->s3()->create();
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => $dest->id,
        'notify_on_failure' => true,
        'notification_emails' => ['admin@test.com'],
    ]);
    $log = BackupLog::factory()->pending()->create([
        'backup_job_id' => $job->id,
        'started_at' => now(),
    ]);

    $mockMysql = \Mockery::mock(MysqlBackupService::class);
    $mockMysql->shouldReceive('dump')->once()->andThrow(new \RuntimeException('Connection failed'));

    app()->instance(MysqlBackupService::class, $mockMysql);

    $processJob = new ProcessBackupJob($job->id, $log->id);
    $processJob->handle(
        $mockMysql,
        app(\App\Services\Backup\MongodbBackupService::class),
        app(\App\Services\Backup\FilesystemBackupService::class),
        app(\App\Services\Backup\S3StorageService::class),
        app(\App\Services\Backup\FtpStorageService::class),
        app(\App\Services\Backup\BackupSchedulerService::class),
    );

    $log->refresh();
    expect($log->status)->toBe('failed');
    expect($log->error_message)->toContain('Connection failed');

    Event::assertDispatched(BackupJobCompleted::class, function ($event) {
        return $event->status === 'failed';
    });
});

it('updates next_run_at after execution', function () {
    Event::fake();

    $mysqlHost = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->mysql()->create(['mysql_host_id' => $mysqlHost->id]);
    $dest = BackupStorageDestination::factory()->s3()->create();
    $job = BackupJob::factory()->daily()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => $dest->id,
        'schedule_time' => '03:00',
    ]);
    $log = BackupLog::factory()->pending()->create([
        'backup_job_id' => $job->id,
        'started_at' => now(),
    ]);

    $mockMysql = \Mockery::mock(MysqlBackupService::class);
    $mockMysql->shouldReceive('dump')->andReturn([
        'file_name' => 'backup.sql.gz',
        'file_path' => '/tmp/backup.sql.gz',
        'file_size' => 1024,
        'meta' => [],
    ]);

    $mockS3 = \Mockery::mock(S3StorageService::class);
    $mockS3->shouldReceive('upload')->andReturn('path');
    $mockS3->shouldReceive('delete')->andReturn(true);

    $processJob = new ProcessBackupJob($job->id, $log->id);
    $processJob->handle(
        $mockMysql,
        app(\App\Services\Backup\MongodbBackupService::class),
        app(\App\Services\Backup\FilesystemBackupService::class),
        $mockS3,
        app(\App\Services\Backup\FtpStorageService::class),
        app(\App\Services\Backup\BackupSchedulerService::class),
    );

    $job->refresh();
    expect($job->last_run_at)->not->toBeNull();
    expect($job->next_run_at)->not->toBeNull();
});
