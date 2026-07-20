<?php

use App\Models\BackupHost;
use App\Models\BackupSource;

it('links a source to different hosts per type', function () {
    $db = BackupHost::factory()->withMysql()->create();
    $web = BackupHost::factory()->withFilesystem()->create();

    $source = BackupSource::factory()->create([
        'config' => ['mysql' => ['databases' => ['app']], 'filesystem' => ['paths' => ['/var/www'], 'exclude_patterns' => '']],
        'mysql_host_id' => $db->id,
        'filesystem_host_id' => $web->id,
    ]);

    expect($source->mysqlHost->id)->toBe($db->id);
    expect($source->filesystemHost->id)->toBe($web->id);
    expect($source->mongodbHost)->toBeNull();
});

it('nulls the type host_id when that host is deleted', function () {
    $db = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->create(['config' => ['mysql' => ['databases' => ['x']]], 'mysql_host_id' => $db->id]);

    $db->delete();

    expect($source->fresh()->mysql_host_id)->toBeNull();
});
