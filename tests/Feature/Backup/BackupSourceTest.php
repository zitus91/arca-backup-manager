<?php

use App\Livewire\Backup\BackupSourceForm;
use App\Livewire\Backup\BackupSourceIndex;
use App\Models\BackupSource;
use Livewire\Livewire;

it('renders backup source index', function () {
    BackupSource::factory()->mysql()->create();
    BackupSource::factory()->mongodb()->create();

    Livewire::test(BackupSourceIndex::class)
        ->assertStatus(200);
});

it('creates a MySQL source', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Production MySQL')
        ->set('type', 'mysql')
        ->set('mysql_host', '127.0.0.1')
        ->set('mysql_port', 3306)
        ->set('mysql_database', 'myapp')
        ->set('mysql_username', 'root')
        ->set('mysql_password', 'secret')
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::first();
    expect($source->type)->toBe('mysql');
    expect($source->config['database'])->toBe('myapp');
});

it('creates a MongoDB source', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('name', 'MongoDB Main')
        ->set('type', 'mongodb')
        ->set('mongodb_host', '127.0.0.1')
        ->set('mongodb_port', 27017)
        ->set('mongodb_database', 'mydb')
        ->set('mongodb_username', 'admin')
        ->set('mongodb_password', 'mongopass')
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::first();
    expect($source->type)->toBe('mongodb');
    expect($source->config['database'])->toBe('mydb');
});

it('creates a Filesystem source', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Uploads Folder')
        ->set('type', 'filesystem')
        ->set('fs_path', '/var/www/uploads')
        ->set('fs_exclude_patterns', '*.log, *.tmp')
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::first();
    expect($source->type)->toBe('filesystem');
    expect($source->config['path'])->toBe('/var/www/uploads');
    expect($source->config['exclude_patterns'])->toBe(['*.log', '*.tmp']);
});

it('validates required fields for MySQL', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('type', 'mysql')
        ->set('name', '')
        ->set('mysql_database', '')
        ->call('save')
        ->assertHasErrors(['name', 'mysql_database']);
});

it('validates required fields for filesystem', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('type', 'filesystem')
        ->set('name', '')
        ->set('fs_path', '')
        ->call('save')
        ->assertHasErrors(['name', 'fs_path']);
});

it('filters sources by type', function () {
    BackupSource::factory()->mysql()->create(['name' => 'MySQL DB']);
    BackupSource::factory()->filesystem()->create(['name' => 'FS Source']);

    Livewire::test(BackupSourceIndex::class)
        ->set('filterType', 'mysql')
        ->assertSee('MySQL DB')
        ->assertDontSee('FS Source');
});

it('encrypts source credentials in database', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Encrypted Source')
        ->set('type', 'mysql')
        ->set('mysql_host', '127.0.0.1')
        ->set('mysql_port', 3306)
        ->set('mysql_database', 'testdb')
        ->set('mysql_username', 'root')
        ->set('mysql_password', 'supersecret')
        ->call('save');

    $raw = \DB::table('backup_sources')->first();
    expect($raw->config)->not->toContain('supersecret');
});
