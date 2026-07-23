<?php

use App\Events\Backup\BackupJobCompleted;
use App\Events\Backup\BackupJobStarted;
use App\Jobs\Backup\ProcessBackupJob;
use App\Models\BackupHost;
use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\BackupSource;
use App\Models\BackupStorageDestination;
use App\Services\Backup\PostgresBackupService;
use App\Services\Backup\S3StorageService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

it('processes a postgres backup job successfully', function () {
    Event::fake();
    Mail::fake();

    $postgresHost = BackupHost::factory()->withPostgres()->create();
    $source = BackupSource::factory()->postgres()->create(['postgres_host_id' => $postgresHost->id]);
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

    $mockPostgres = \Mockery::mock(PostgresBackupService::class);
    $mockPostgres->shouldReceive('dump')->once()->andReturn([
        'file_name' => 'postgres_appdb_20260722_120000.sql.gz',
        'file_path' => '/tmp/postgres/postgres_appdb_20260722_120000.sql.gz',
        'file_size' => 2048,
        'meta' => ['tables_dumped' => 15],
    ]);

    $mockS3 = \Mockery::mock(S3StorageService::class);
    $mockS3->shouldReceive('upload')->once()->andReturn('backups/test/postgres_appdb_20260722_120000.sql.gz');
    $mockS3->shouldReceive('delete')->andReturn(true);

    app()->instance(PostgresBackupService::class, $mockPostgres);
    app()->instance(S3StorageService::class, $mockS3);

    $processJob = new ProcessBackupJob($job->id, $log->id);
    $processJob->handle(
        app(\App\Services\Backup\MysqlBackupService::class),
        $mockPostgres,
        app(\App\Services\Backup\MongodbBackupService::class),
        app(\App\Services\Backup\FilesystemBackupService::class),
        $mockS3,
        app(\App\Services\Backup\FtpStorageService::class),
        app(\App\Services\Backup\BackupSchedulerService::class),
    );

    $log->refresh();
    expect($log->status)->toBe('success');
    expect($log->file_name)->toBe('postgres_appdb_20260722_120000.sql.gz');
    expect($log->file_size_bytes)->toBe(2048);

    Event::assertDispatched(BackupJobStarted::class);
    Event::assertDispatched(BackupJobCompleted::class, function ($event) {
        return $event->status === 'success';
    });
});

it('processes an incremental postgres backup', function () {
    Event::fake();
    Mail::fake();

    $postgresHost = BackupHost::factory()->withPostgres()->create();
    $source = BackupSource::factory()->postgres()->create(['postgres_host_id' => $postgresHost->id]);
    $dest = BackupStorageDestination::factory()->s3()->create();
    $job = BackupJob::factory()->incremental()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => $dest->id,
        'compression' => 'gzip',
    ]);

    // Seed a prior successful full backup so resolveIncrementalState returns is_incremental=true
    $parentLog = BackupLog::factory()->success()->create([
        'backup_job_id' => $job->id,
        'is_full' => true,
        'started_at' => now()->subDay(),
        'file_name' => 'postgres_appdb_full.sql.gz',
        'storage_path' => 'backups/test/postgres_appdb_full.sql.gz',
    ]);

    $log = BackupLog::factory()->pending()->create([
        'backup_job_id' => $job->id,
        'started_at' => now(),
    ]);

    $mockPostgres = \Mockery::mock(PostgresBackupService::class);
    $mockPostgres->shouldReceive('incrementalDump')->once()->andReturn([
        'file_name' => 'postgres_appdb_incr_20260722_130000.sql.gz',
        'file_path' => '/tmp/postgres/postgres_appdb_incr_20260722_130000.sql.gz',
        'file_size' => 512,
        'meta' => ['tables_dumped' => 3],
        'incremental_checkpoint' => ['timestamp' => '2026-07-22 13:00:00'],
    ]);

    $mockS3 = \Mockery::mock(S3StorageService::class);
    $mockS3->shouldReceive('upload')->once()->andReturn('backups/test/postgres_appdb_incr_20260722_130000.sql.gz');
    $mockS3->shouldReceive('delete')->andReturn(true);

    app()->instance(PostgresBackupService::class, $mockPostgres);
    app()->instance(S3StorageService::class, $mockS3);

    $processJob = new ProcessBackupJob($job->id, $log->id);
    $processJob->handle(
        app(\App\Services\Backup\MysqlBackupService::class),
        $mockPostgres,
        app(\App\Services\Backup\MongodbBackupService::class),
        app(\App\Services\Backup\FilesystemBackupService::class),
        $mockS3,
        app(\App\Services\Backup\FtpStorageService::class),
        app(\App\Services\Backup\BackupSchedulerService::class),
    );

    $log->refresh();
    expect($log->status)->toBe('success');
    expect($log->file_name)->toBe('postgres_appdb_incr_20260722_130000.sql.gz');
    expect($log->is_full)->toBeFalse();
});

it('handles postgres backup failure', function () {
    Event::fake();
    Mail::fake();

    $postgresHost = BackupHost::factory()->withPostgres()->create();
    $source = BackupSource::factory()->postgres()->create(['postgres_host_id' => $postgresHost->id]);
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

    $mockPostgres = \Mockery::mock(PostgresBackupService::class);
    $mockPostgres->shouldReceive('dump')->once()->andThrow(new \RuntimeException('pg_dump failed: connection refused'));

    app()->instance(PostgresBackupService::class, $mockPostgres);

    $processJob = new ProcessBackupJob($job->id, $log->id);
    $processJob->handle(
        app(\App\Services\Backup\MysqlBackupService::class),
        $mockPostgres,
        app(\App\Services\Backup\MongodbBackupService::class),
        app(\App\Services\Backup\FilesystemBackupService::class),
        app(\App\Services\Backup\S3StorageService::class),
        app(\App\Services\Backup\FtpStorageService::class),
        app(\App\Services\Backup\BackupSchedulerService::class),
    );

    $log->refresh();
    expect($log->status)->toBe('failed');
    expect($log->error_message)->toContain('pg_dump failed');

    Event::assertDispatched(BackupJobCompleted::class, function ($event) {
        return $event->status === 'failed';
    });
});
