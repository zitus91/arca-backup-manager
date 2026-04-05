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
        ->set('enable_mysql', true)
        ->set('mysql_host', '127.0.0.1')
        ->set('mysql_port', 3306)
        ->set('mysql_databases', ['myapp'])
        ->set('mysql_username', 'root')
        ->set('mysql_password', 'secret')
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::first();
    expect($source->hasType('mysql'))->toBeTrue();
    expect($source->config['mysql']['databases'])->toContain('myapp');
});

it('creates a MongoDB source', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('name', 'MongoDB Main')
        ->set('enable_mongodb', true)
        ->set('mongodb_host', '127.0.0.1')
        ->set('mongodb_port', 27017)
        ->set('mongodb_databases', ['mydb'])
        ->set('mongodb_username', 'admin')
        ->set('mongodb_password', 'mongopass')
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::first();
    expect($source->hasType('mongodb'))->toBeTrue();
    expect($source->config['mongodb']['databases'])->toContain('mydb');
});

it('creates a Filesystem source', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Uploads Folder')
        ->set('enable_filesystem', true)
        ->set('fs_paths', ['/var/www/uploads'])
        ->set('fs_exclude_patterns', '*.log, *.tmp')
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::first();
    expect($source->hasType('filesystem'))->toBeTrue();
    expect($source->config['filesystem']['paths'])->toContain('/var/www/uploads');
    expect($source->config['filesystem']['exclude_patterns'])->toBe(['*.log', '*.tmp']);
});

it('validates required fields for MySQL', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('enable_mysql', true)
        ->set('name', '')
        ->set('mysql_databases', [])
        ->call('save')
        ->assertHasErrors(['name', 'mysql_databases']);
});

it('validates required fields for filesystem', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('enable_filesystem', true)
        ->set('name', '')
        ->set('fs_paths', [''])
        ->call('save')
        ->assertHasErrors(['name', 'fs_paths.0']);
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
        ->set('enable_mysql', true)
        ->set('mysql_host', '127.0.0.1')
        ->set('mysql_port', 3306)
        ->set('mysql_databases', ['testdb'])
        ->set('mysql_username', 'root')
        ->set('mysql_password', 'supersecret')
        ->call('save');

    $raw = \DB::table('backup_sources')->first();
    expect($raw->config)->not->toContain('supersecret');
});
