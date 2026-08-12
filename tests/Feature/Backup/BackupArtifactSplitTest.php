<?php

use App\Jobs\Backup\ProcessBackupJob;
use App\Jobs\Restore\ProcessRestoreJob;
use App\Models\BackupHost;
use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\BackupSource;
use App\Models\BackupStorageDestination;
use App\Models\RestoreLog;
use App\Models\User;
use App\Services\Backup\FilesystemBackupService;
use App\Services\Backup\MysqlBackupService;
use App\Services\Restore\FilesystemRestoreService;
use App\Services\Restore\MysqlRestoreService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Event::fake();
    Mail::fake();
});

/**
 * Local destination rooted at a throwaway directory, so uploads land on disk
 * and can be asserted on.
 */
function localDestination(string $root): BackupStorageDestination
{
    File::ensureDirectoryExists($root);

    return BackupStorageDestination::factory()->create([
        'type' => 'local',
        'config' => ['path' => $root],
    ]);
}

/**
 * A backup service dump result whose file actually exists on disk.
 */
function fakeDump(string $tmpRoot, string $fileName, string $contents): array
{
    File::ensureDirectoryExists($tmpRoot);
    $path = $tmpRoot.'/'.$fileName;
    File::put($path, $contents);

    return [
        'file_name' => $fileName,
        'file_path' => $path,
        'file_size' => strlen($contents),
        'meta' => [],
    ];
}

function runBackup(BackupJob $job, BackupLog $log, $mysqlService = null, $filesystemService = null): void
{
    (new ProcessBackupJob($job->id, $log->id))->handle(
        $mysqlService ?? app(MysqlBackupService::class),
        app(\App\Services\Backup\PostgresBackupService::class),
        app(\App\Services\Backup\MongodbBackupService::class),
        $filesystemService ?? app(FilesystemBackupService::class),
        app(\App\Services\Backup\S3StorageService::class),
        app(\App\Services\Backup\FtpStorageService::class),
        app(\App\Services\Backup\BackupSchedulerService::class),
    );
}

function runRestore(RestoreLog $restoreLog): void
{
    (new ProcessRestoreJob($restoreLog->id))->handle(
        app(MysqlRestoreService::class),
        app(\App\Services\Restore\PostgresRestoreService::class),
        app(\App\Services\Restore\MongodbRestoreService::class),
        app(FilesystemRestoreService::class),
        app(\App\Services\Backup\S3StorageService::class),
        app(\App\Services\Backup\FtpStorageService::class),
    );
}

it('uploads one object per database instead of a single package archive', function () {
    $root = sys_get_temp_dir().'/art_dest_'.uniqid();
    $tmp = sys_get_temp_dir().'/art_tmp_'.uniqid();

    $host = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->create([
        'mysql_host_id' => $host->id,
        'name' => 'Shop',
        'config' => ['mysql' => ['databases' => ['shop', 'blog']]],
    ]);
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => localDestination($root)->id,
    ]);
    $log = BackupLog::factory()->pending()->create(['backup_job_id' => $job->id, 'started_at' => now()]);

    $mysql = \Mockery::mock(MysqlBackupService::class);
    $mysql->shouldReceive('dump')->twice()->andReturnUsing(
        fn ($config) => fakeDump($tmp, 'mysql_'.$config['database'].'_20260812_100000.sql.gz', 'dump of '.$config['database'])
    );

    runBackup($job, $log, $mysql);

    $log->refresh();
    expect($log->status)->toBe('success');

    $artifacts = $log->meta['artifacts'];
    expect($artifacts)->toHaveCount(2);
    expect(array_column($artifacts, 'key'))->toBe(['shop', 'blog']);

    // Each dump is its own object under mysql/ — no tar package anywhere.
    foreach ($artifacts as $artifact) {
        expect($root.'/'.$artifact['storage_path'])->toBeFile();
        expect($artifact['storage_path'])->toContain('/mysql/');
    }
    expect(File::allFiles($root))->toHaveCount(2);
    expect(collect(File::allFiles($root))->filter(fn ($f) => str_ends_with($f->getFilename(), '.tar.gz')))->toBeEmpty();

    File::deleteDirectory($root);
    File::deleteDirectory($tmp);
});

it('restores each database from its own dump', function () {
    $root = sys_get_temp_dir().'/art_dest_'.uniqid();
    $tmp = sys_get_temp_dir().'/art_tmp_'.uniqid();

    $host = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->create([
        'mysql_host_id' => $host->id,
        'config' => ['mysql' => ['databases' => ['shop', 'blog']]],
    ]);
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => localDestination($root)->id,
    ]);
    $log = BackupLog::factory()->pending()->create(['backup_job_id' => $job->id, 'started_at' => now()]);

    $mysql = \Mockery::mock(MysqlBackupService::class);
    $mysql->shouldReceive('dump')->twice()->andReturnUsing(
        fn ($config) => fakeDump($tmp, 'mysql_'.$config['database'].'_20260812_100000.sql.gz', 'dump of '.$config['database'])
    );
    runBackup($job, $log, $mysql);

    $restoreLog = RestoreLog::factory()->pending()->create([
        'backup_log_id' => $log->refresh()->id,
        'user_id' => User::factory()->create()->id,
        'restore_type' => 'db_only',
        'restore_target' => 'same_host',
    ]);

    // Every database must be restored from the dump that belongs to it.
    $restored = [];
    $mysqlRestore = \Mockery::mock(MysqlRestoreService::class);
    $mysqlRestore->shouldReceive('restore')->twice()->andReturnUsing(function ($config, $dumpPath) use (&$restored) {
        $restored[$config['database']] = File::get($dumpPath);

        return ['restored_db_name' => $config['database'].'_restored', 'original_db' => $config['database']];
    });
    app()->instance(MysqlRestoreService::class, $mysqlRestore);

    runRestore($restoreLog);

    expect($restoreLog->refresh()->status)->toBe('success');
    expect($restored)->toBe([
        'shop' => 'dump of shop',
        'blog' => 'dump of blog',
    ]);

    File::deleteDirectory($root);
    File::deleteDirectory($tmp);
});

it('downloads only the database dump for a db_only restore', function () {
    $root = sys_get_temp_dir().'/art_dest_'.uniqid();
    $tmp = sys_get_temp_dir().'/art_tmp_'.uniqid();

    $host = BackupHost::factory()->withMysql()->withFilesystem()->create();
    $source = BackupSource::factory()->create([
        'mysql_host_id' => $host->id,
        'filesystem_host_id' => $host->id,
        'config' => [
            'mysql' => ['databases' => ['shop']],
            'filesystem' => ['paths' => ['/var/www/html'], 'exclude_patterns' => []],
        ],
    ]);
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => localDestination($root)->id,
    ]);
    $log = BackupLog::factory()->pending()->create(['backup_job_id' => $job->id, 'started_at' => now()]);

    $mysql = \Mockery::mock(MysqlBackupService::class);
    $mysql->shouldReceive('dump')->once()->andReturn(fakeDump($tmp, 'mysql_shop_20260812_100000.sql.gz', 'db dump'));

    $fs = \Mockery::mock(FilesystemBackupService::class);
    $fs->shouldReceive('backup')->once()->andReturn(fakeDump($tmp, 'fs_html_20260812_100000.tar.gz', str_repeat('files', 100)));

    runBackup($job, $log, $mysql, $fs);
    $log->refresh();

    expect($log->meta['artifacts'])->toHaveCount(2);

    $restoreLog = RestoreLog::factory()->pending()->create([
        'backup_log_id' => $log->id,
        'user_id' => User::factory()->create()->id,
        'restore_type' => 'db_only',
        'restore_target' => 'same_host',
    ]);

    $mysqlRestore = \Mockery::mock(MysqlRestoreService::class);
    $mysqlRestore->shouldReceive('restore')->once()->andReturn(['restored_db_name' => 'shop_restored', 'original_db' => 'shop']);
    app()->instance(MysqlRestoreService::class, $mysqlRestore);

    // The filesystem archive must never be touched by a db_only restore.
    $fsRestore = \Mockery::mock(FilesystemRestoreService::class);
    $fsRestore->shouldReceive('restore')->never();
    app()->instance(FilesystemRestoreService::class, $fsRestore);

    runRestore($restoreLog);

    expect($restoreLog->refresh()->status)->toBe('success');

    File::deleteDirectory($root);
    File::deleteDirectory($tmp);
});

it('retention deletes every artifact of an expired backup', function () {
    $root = sys_get_temp_dir().'/art_dest_'.uniqid();
    $tmp = sys_get_temp_dir().'/art_tmp_'.uniqid();

    $host = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->create([
        'mysql_host_id' => $host->id,
        'config' => ['mysql' => ['databases' => ['shop', 'blog']]],
    ]);
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => localDestination($root)->id,
        'backup_type' => 'full',
        'retention_count' => 1,
    ]);

    $runBackupOnce = function (string $stamp) use ($job, $tmp) {
        $log = BackupLog::factory()->pending()->create(['backup_job_id' => $job->id, 'started_at' => now()]);
        $mysql = \Mockery::mock(MysqlBackupService::class);
        $mysql->shouldReceive('dump')->twice()->andReturnUsing(
            fn ($config) => fakeDump($tmp, 'mysql_'.$config['database'].'_'.$stamp.'.sql.gz', 'x')
        );
        runBackup($job, $log, $mysql);

        return $log->refresh();
    };

    $old = $runBackupOnce('20260811_100000');
    $this->travel(1)->minutes();
    $runBackupOnce('20260812_100000');

    // retention_count = 1, so the older backup's two objects are both gone.
    foreach ($old->meta['artifacts'] as $artifact) {
        expect($root.'/'.$artifact['storage_path'])->not->toBeFile();
    }
    expect(File::allFiles($root))->toHaveCount(2);

    File::deleteDirectory($root);
    File::deleteDirectory($tmp);
});

it('still restores a legacy multi-type package backup', function () {
    $root = sys_get_temp_dir().'/art_dest_'.uniqid();
    $build = sys_get_temp_dir().'/art_pkg_'.uniqid();

    // Rebuild what the old backup job produced: one tar.gz with per-type subdirectories.
    File::ensureDirectoryExists($build.'/mysql');
    File::put($build.'/mysql/mysql_shop_20260101_100000.sql.gz', 'legacy shop dump');
    File::put($build.'/mysql/mysql_incr_blog_20260101_100000.sql.gz', 'legacy blog dump');
    $storagePath = 'backups/legacy/2026/01/01/package.tar.gz';
    File::ensureDirectoryExists(dirname($root.'/'.$storagePath));
    exec('tar -czf '.escapeshellarg($root.'/'.$storagePath).' -C '.escapeshellarg($build).' .');

    $host = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->create([
        'mysql_host_id' => $host->id,
        'config' => ['mysql' => ['databases' => ['shop', 'blog']]],
    ]);
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => localDestination($root)->id,
    ]);
    $backupLog = BackupLog::factory()->success()->create([
        'backup_job_id' => $job->id,
        'file_name' => 'package.tar.gz',
        'storage_path' => $storagePath,
        'meta' => ['types' => ['mysql'], 'files' => ['mysql/mysql_shop_20260101_100000.sql.gz']],
    ]);
    $restoreLog = RestoreLog::factory()->pending()->create([
        'backup_log_id' => $backupLog->id,
        'user_id' => User::factory()->create()->id,
        'restore_type' => 'db_only',
        'restore_target' => 'same_host',
    ]);

    $restored = [];
    $mysqlRestore = \Mockery::mock(MysqlRestoreService::class);
    $mysqlRestore->shouldReceive('restore')->twice()->andReturnUsing(function ($config, $dumpPath) use (&$restored) {
        $restored[$config['database']] = File::get($dumpPath);

        return ['restored_db_name' => $config['database'].'_restored', 'original_db' => $config['database']];
    });
    app()->instance(MysqlRestoreService::class, $mysqlRestore);

    runRestore($restoreLog);

    expect($restoreLog->refresh()->status)->toBe('success');
    // The incremental dump keeps its own _incr name: it must not land in the wrong database.
    expect($restored)->toBe([
        'shop' => 'legacy shop dump',
        'blog' => 'legacy blog dump',
    ]);

    File::deleteDirectory($root);
    File::deleteDirectory($build);
});

it('fails the job instead of storing an empty dump', function () {
    $root = sys_get_temp_dir().'/art_dest_'.uniqid();
    $tmp = sys_get_temp_dir().'/art_tmp_'.uniqid();

    $host = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->create([
        'mysql_host_id' => $host->id,
        'config' => ['mysql' => ['databases' => ['shop']]],
    ]);
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => localDestination($root)->id,
    ]);
    $log = BackupLog::factory()->pending()->create(['backup_job_id' => $job->id, 'started_at' => now()]);

    // What a failing mysqldump piped into gzip leaves behind: a valid, empty 20-byte archive.
    File::ensureDirectoryExists($tmp);
    $emptyGzip = $tmp.'/mysql_shop_20260812_100000.sql.gz';
    File::put($emptyGzip, gzencode(''));
    expect(filesize($emptyGzip))->toBe(20);

    $mysql = \Mockery::mock(MysqlBackupService::class);
    $mysql->shouldReceive('dump')->once()->andReturn([
        'file_name' => basename($emptyGzip),
        'file_path' => $emptyGzip,
        'file_size' => 20,
        'meta' => [],
    ]);

    runBackup($job, $log, $mysql);

    $log->refresh();
    expect($log->status)->toBe('failed');
    expect($log->error_message)->toContain('empty');
    expect(File::exists($root) ? File::allFiles($root) : [])->toBeEmpty();

    File::deleteDirectory($root);
    File::deleteDirectory($tmp);
});

it('still accepts an incremental run that found no changes', function () {
    $root = sys_get_temp_dir().'/art_dest_'.uniqid();
    $tmp = sys_get_temp_dir().'/art_tmp_'.uniqid();

    $host = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->create([
        'mysql_host_id' => $host->id,
        'config' => ['mysql' => ['databases' => ['shop']]],
    ]);
    $job = BackupJob::factory()->create([
        'backup_source_id' => $source->id,
        'backup_storage_destination_id' => localDestination($root)->id,
        'backup_type' => 'incremental',
    ]);
    BackupLog::factory()->success()->create([
        'backup_job_id' => $job->id,
        'is_full' => true,
        'started_at' => now()->subDay(),
    ]);
    $log = BackupLog::factory()->pending()->create(['backup_job_id' => $job->id, 'started_at' => now()]);

    $marker = fakeDump($tmp, 'mysql_incr_shop_20260812_100000.sql', '-- incremental: no changes');
    $marker['meta'] = ['incremental' => true, 'no_changes' => true];

    $mysql = \Mockery::mock(MysqlBackupService::class);
    $mysql->shouldReceive('incrementalDump')->once()->andReturn($marker);

    runBackup($job, $log, $mysql);

    expect($log->refresh()->status)->toBe('success');

    File::deleteDirectory($root);
    File::deleteDirectory($tmp);
});
