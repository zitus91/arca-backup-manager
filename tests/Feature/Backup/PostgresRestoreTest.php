<?php

use App\Events\Restore\RestoreJobCompleted;
use App\Events\Restore\RestoreJobStarted;
use App\Jobs\Restore\ProcessRestoreJob;
use App\Models\BackupHost;
use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\BackupSource;
use App\Models\BackupStorageDestination;
use App\Models\RestoreLog;
use App\Models\User;
use App\Services\Restore\PostgresRestoreService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    Event::fake();
});

/**
 * Helper: create a local destination rooted at $localRoot and place
 * $dumpContent at $storagePath inside it so downloadFromLocal() can copy.
 */
function setupLocalDest(string $localRoot, string $storagePath, string $dumpContent): BackupStorageDestination
{
    File::ensureDirectoryExists(dirname($localRoot.'/'.$storagePath));
    File::put($localRoot.'/'.$storagePath, $dumpContent);

    return BackupStorageDestination::factory()->create([
        'type' => 'local',
        'config' => ['path' => $localRoot],
    ]);
}

it('restores a postgres backup to the same host', function () {
    $localRoot = sys_get_temp_dir().'/rst_local_'.uniqid();
    $storagePath = 'backups/test/postgres_appdb_20260722_120000.sql.gz';

    $postgresHost = BackupHost::factory()->withPostgres()->create();
    $source = BackupSource::factory()->postgres()->create([
        'postgres_host_id' => $postgresHost->id,
        'config' => ['postgres' => ['databases' => ['appdb']]],
    ]);
    $dest = setupLocalDest($localRoot, $storagePath, 'dummy postgres dump');
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => $dest->id,
    ]);
    $backupLog = BackupLog::factory()->success()->create([
        'backup_job_id' => $job->id,
        'file_name' => 'postgres_appdb_20260722_120000.sql.gz',
        'storage_path' => $storagePath,
    ]);
    $user = User::factory()->create();
    $restoreLog = RestoreLog::factory()->pending()->create([
        'backup_log_id' => $backupLog->id,
        'user_id' => $user->id,
        'restore_type' => 'db_only',
        'restore_target' => 'same_host',
    ]);

    $mockPostgresRestore = \Mockery::mock(PostgresRestoreService::class);
    $mockPostgresRestore->shouldReceive('restore')
        ->once()
        ->withArgs(function ($config, $dumpPath, $targetDbName, $override) {
            return str_contains($dumpPath, 'postgres_appdb_20260722_120000.sql.gz')
                && $targetDbName === null
                && $override === false;
        })
        ->andReturn([
            'restored_db_name' => 'appdb_restored_20260722_130000',
            'original_db' => 'appdb',
            'dump_file' => 'postgres_appdb_20260722_120000.sql.gz',
            'override_existing' => false,
        ]);

    app()->instance(PostgresRestoreService::class, $mockPostgresRestore);

    $processJob = new ProcessRestoreJob($restoreLog->id);
    $processJob->handle(
        app(\App\Services\Restore\MysqlRestoreService::class),
        $mockPostgresRestore,
        app(\App\Services\Restore\MongodbRestoreService::class),
        app(\App\Services\Restore\FilesystemRestoreService::class),
        app(\App\Services\Backup\S3StorageService::class),
        app(\App\Services\Backup\FtpStorageService::class),
    );

    $restoreLog->refresh();
    expect($restoreLog->status)->toBe('success');
    expect($restoreLog->restored_db_name)->toBe('appdb_restored_20260722_130000');

    Event::assertDispatched(RestoreJobStarted::class);
    Event::assertDispatched(RestoreJobCompleted::class, function ($event) {
        return $event->status === 'success';
    });

    File::deleteDirectory($localRoot);
});

it('restores a postgres backup with a custom target database name', function () {
    $localRoot = sys_get_temp_dir().'/rst_local_custom_'.uniqid();
    $storagePath = 'backups/test/postgres_appdb_20260722_120000.sql.gz';

    $postgresHost = BackupHost::factory()->withPostgres()->create();
    $source = BackupSource::factory()->postgres()->create([
        'postgres_host_id' => $postgresHost->id,
        'config' => ['postgres' => ['databases' => ['appdb']]],
    ]);
    $dest = setupLocalDest($localRoot, $storagePath, 'dummy postgres dump');
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => $dest->id,
    ]);
    $backupLog = BackupLog::factory()->success()->create([
        'backup_job_id' => $job->id,
        'file_name' => 'postgres_appdb_20260722_120000.sql.gz',
        'storage_path' => $storagePath,
    ]);
    $user = User::factory()->create();
    $restoreLog = RestoreLog::factory()->pending()->create([
        'backup_log_id' => $backupLog->id,
        'user_id' => $user->id,
        'restore_type' => 'db_only',
        'restore_target' => 'same_host',
        'custom_names' => ['databases' => ['appdb' => 'my_custom_db']],
    ]);

    $mockPostgresRestore = \Mockery::mock(PostgresRestoreService::class);
    $mockPostgresRestore->shouldReceive('restore')
        ->once()
        ->withArgs(function ($config, $dumpPath, $targetDbName, $override) {
            return $targetDbName === 'my_custom_db' && $override === false;
        })
        ->andReturn([
            'restored_db_name' => 'my_custom_db',
            'original_db' => 'appdb',
            'dump_file' => 'postgres_appdb_20260722_120000.sql.gz',
            'override_existing' => false,
        ]);

    app()->instance(PostgresRestoreService::class, $mockPostgresRestore);

    $processJob = new ProcessRestoreJob($restoreLog->id);
    $processJob->handle(
        app(\App\Services\Restore\MysqlRestoreService::class),
        $mockPostgresRestore,
        app(\App\Services\Restore\MongodbRestoreService::class),
        app(\App\Services\Restore\FilesystemRestoreService::class),
        app(\App\Services\Backup\S3StorageService::class),
        app(\App\Services\Backup\FtpStorageService::class),
    );

    $restoreLog->refresh();
    expect($restoreLog->status)->toBe('success');
    expect($restoreLog->restored_db_name)->toBe('my_custom_db');

    File::deleteDirectory($localRoot);
});

it('restores a postgres backup to a remote host without SSH', function () {
    $localRoot = sys_get_temp_dir().'/rst_local_remote_'.uniqid();
    $storagePath = 'backups/test/postgres_appdb_20260722_120000.sql.gz';

    $postgresHost = BackupHost::factory()->withPostgres()->create();
    $source = BackupSource::factory()->postgres()->create([
        'postgres_host_id' => $postgresHost->id,
        'config' => ['postgres' => ['databases' => ['appdb']]],
    ]);
    $dest = setupLocalDest($localRoot, $storagePath, 'dummy postgres dump');
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => $dest->id,
    ]);
    $backupLog = BackupLog::factory()->success()->create([
        'backup_job_id' => $job->id,
        'file_name' => 'postgres_appdb_20260722_120000.sql.gz',
        'storage_path' => $storagePath,
    ]);
    $user = User::factory()->create();
    $restoreLog = RestoreLog::factory()->pending()->create([
        'backup_log_id' => $backupLog->id,
        'user_id' => $user->id,
        'restore_type' => 'db_only',
        'restore_target' => 'remote_host',
        'remote_host_config' => [
            'postgres' => [
                'host' => '192.168.1.100',
                'port' => 5432,
                'username' => 'remote_user',
                'password' => 'remote_pass',
                'ssh' => ['enabled' => false],
            ],
        ],
    ]);

    $mockPostgresRestore = \Mockery::mock(PostgresRestoreService::class);
    $mockPostgresRestore->shouldReceive('restore')
        ->once()
        ->withArgs(function ($config, $dumpPath, $targetDbName, $override) {
            return $config['host'] === '192.168.1.100'
                && ($config['ssh']['enabled'] ?? false) === false
                && $override === false;
        })
        ->andReturn([
            'restored_db_name' => 'appdb_restored_20260722_130000',
            'original_db' => 'appdb',
            'dump_file' => 'postgres_appdb_20260722_120000.sql.gz',
            'override_existing' => false,
        ]);

    app()->instance(PostgresRestoreService::class, $mockPostgresRestore);

    $processJob = new ProcessRestoreJob($restoreLog->id);
    $processJob->handle(
        app(\App\Services\Restore\MysqlRestoreService::class),
        $mockPostgresRestore,
        app(\App\Services\Restore\MongodbRestoreService::class),
        app(\App\Services\Restore\FilesystemRestoreService::class),
        app(\App\Services\Backup\S3StorageService::class),
        app(\App\Services\Backup\FtpStorageService::class),
    );

    $restoreLog->refresh();
    expect($restoreLog->status)->toBe('success');

    File::deleteDirectory($localRoot);
});

it('restores a combined postgres and filesystem backup package', function () {
    // Build a real tar.gz with postgres/ and filesystem/ subdirectories
    $pkgSrc = sys_get_temp_dir().'/rst_pkg_src_'.uniqid();
    File::ensureDirectoryExists($pkgSrc.'/postgres');
    File::ensureDirectoryExists($pkgSrc.'/filesystem');
    File::put($pkgSrc.'/postgres/postgres_appdb_20260722_120000.sql.gz', 'dummy postgres dump');
    File::put($pkgSrc.'/filesystem/fs_uploads_20260722_120000.tar.gz', 'dummy fs archive');

    $archive = sys_get_temp_dir().'/rst_pkg_'.uniqid().'.tar.gz';
    Process::run('tar -czf '.escapeshellarg($archive).' -C '.escapeshellarg($pkgSrc).' postgres filesystem');

    $localRoot = sys_get_temp_dir().'/rst_local_pkg_'.uniqid();
    $storagePath = 'backups/test/backup_20260722_120000.tar.gz';
    File::ensureDirectoryExists(dirname($localRoot.'/'.$storagePath));
    copy($archive, $localRoot.'/'.$storagePath);

    $postgresHost = BackupHost::factory()->withPostgres()->create();
    $fsHost = BackupHost::factory()->withFilesystem()->create();
    $source = BackupSource::factory()->create([
        'postgres_host_id' => $postgresHost->id,
        'filesystem_host_id' => $fsHost->id,
        'config' => [
            'postgres' => ['databases' => ['appdb']],
            'filesystem' => ['paths' => ['/var/www/uploads'], 'exclude_patterns' => '*.log'],
        ],
    ]);
    $dest = BackupStorageDestination::factory()->create([
        'type' => 'local',
        'config' => ['path' => $localRoot],
    ]);
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => $dest->id,
    ]);
    $backupLog = BackupLog::factory()->success()->create([
        'backup_job_id' => $job->id,
        'file_name' => 'backup_20260722_120000.tar.gz',
        'storage_path' => $storagePath,
    ]);
    $user = User::factory()->create();
    $restoreLog = RestoreLog::factory()->pending()->create([
        'backup_log_id' => $backupLog->id,
        'user_id' => $user->id,
        'restore_type' => 'full',
        'restore_target' => 'same_host',
    ]);

    $mockPostgresRestore = \Mockery::mock(PostgresRestoreService::class);
    $mockPostgresRestore->shouldReceive('restore')
        ->once()
        ->withArgs(function ($config, $dumpPath, $targetDbName, $override) {
            return str_contains($dumpPath, 'postgres/postgres_appdb_20260722_120000.sql.gz');
        })
        ->andReturn([
            'restored_db_name' => 'appdb_restored_20260722_130000',
            'original_db' => 'appdb',
            'dump_file' => 'postgres_appdb_20260722_120000.sql.gz',
            'override_existing' => false,
        ]);

    $mockFilesystemRestore = \Mockery::mock(\App\Services\Restore\FilesystemRestoreService::class);
    $mockFilesystemRestore->shouldReceive('restore')
        ->once()
        ->andReturn([
            'restored_path' => '/var/www/uploads_restored_20260722_130000',
            'original_path' => '/var/www/uploads',
            'override_existing' => false,
        ]);

    app()->instance(PostgresRestoreService::class, $mockPostgresRestore);
    app()->instance(\App\Services\Restore\FilesystemRestoreService::class, $mockFilesystemRestore);

    $processJob = new ProcessRestoreJob($restoreLog->id);
    $processJob->handle(
        app(\App\Services\Restore\MysqlRestoreService::class),
        $mockPostgresRestore,
        app(\App\Services\Restore\MongodbRestoreService::class),
        $mockFilesystemRestore,
        app(\App\Services\Backup\S3StorageService::class),
        app(\App\Services\Backup\FtpStorageService::class),
    );

    $restoreLog->refresh();
    expect($restoreLog->status)->toBe('success');
    expect($restoreLog->restored_db_name)->toBe('appdb_restored_20260722_130000');
    expect($restoreLog->restored_path)->toBe('/var/www/uploads_restored_20260722_130000');

    File::deleteDirectory($localRoot);
    File::deleteDirectory($pkgSrc);
    @unlink($archive);
});
