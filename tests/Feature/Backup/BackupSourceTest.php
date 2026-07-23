<?php

use App\Livewire\Backup\BackupSourceForm;
use App\Livewire\Backup\BackupSourceIndex;
use App\Models\BackupHost;
use App\Models\BackupSource;
use Livewire\Livewire;

it('renders backup source index', function () {
    BackupSource::factory()->mysql()->create();
    BackupSource::factory()->mongodb()->create();

    Livewire::test(BackupSourceIndex::class)
        ->assertStatus(200);
});

it('creates a MySQL source linked to a host', function () {
    $host = BackupHost::factory()->withMysql()->create();

    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Production MySQL')
        ->set('enable_mysql', true)
        ->set('mysql_host_id', $host->id)
        ->set('mysql_databases', ['myapp'])
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::first();
    expect($source->hasType('mysql'))->toBeTrue();
    expect($source->mysql_host_id)->toBe($host->id);
    expect($source->config['mysql']['databases'])->toContain('myapp');
});

it('creates a MongoDB source linked to a host', function () {
    $host = BackupHost::factory()->withMongodb()->create();

    Livewire::test(BackupSourceForm::class)
        ->set('name', 'MongoDB Main')
        ->set('enable_mongodb', true)
        ->set('mongodb_host_id', $host->id)
        ->set('mongodb_databases', ['mydb'])
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::first();
    expect($source->hasType('mongodb'))->toBeTrue();
    expect($source->mongodb_host_id)->toBe($host->id);
    expect($source->config['mongodb']['databases'])->toContain('mydb');
});

it('creates a Filesystem source linked to a host', function () {
    $host = BackupHost::factory()->withFilesystem()->create();

    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Uploads Folder')
        ->set('enable_filesystem', true)
        ->set('filesystem_host_id', $host->id)
        ->set('fs_paths', ['/var/www/uploads'])
        ->set('fs_exclude_patterns', '*.log, *.tmp')
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::first();
    expect($source->hasType('filesystem'))->toBeTrue();
    expect($source->filesystem_host_id)->toBe($host->id);
    expect($source->config['filesystem']['paths'])->toContain('/var/www/uploads');
    expect($source->config['filesystem']['exclude_patterns'])->toBe(['*.log', '*.tmp']);
});

it('validates required fields for MySQL', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('enable_mysql', true)
        ->set('name', '')
        ->set('mysql_databases', [])
        ->call('save')
        ->assertHasErrors(['name', 'mysql_host_id', 'mysql_databases']);
});

it('validates required fields for filesystem', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('enable_filesystem', true)
        ->set('name', '')
        ->set('fs_paths', [''])
        ->call('save')
        ->assertHasErrors(['name', 'filesystem_host_id', 'fs_paths.0']);
});

it('filters sources by type', function () {
    BackupSource::factory()->mysql()->create(['name' => 'MySQL DB']);
    BackupSource::factory()->filesystem()->create(['name' => 'FS Source']);

    Livewire::test(BackupSourceIndex::class)
        ->set('filterType', 'mysql')
        ->assertSee('MySQL DB')
        ->assertDontSee('FS Source');
});

it('encrypts source config in database', function () {
    $host = BackupHost::factory()->withMysql()->create();

    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Encrypted Source')
        ->set('enable_mysql', true)
        ->set('mysql_host_id', $host->id)
        ->set('mysql_databases', ['testdb'])
        ->call('save');

    $raw = \DB::table('backup_sources')->first();
    expect($raw->config)->not->toContain('testdb');
});

it('links a source mysql host and stores only databases', function () {
    $host = BackupHost::factory()->withMysql()->create();

    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Prod DB')
        ->set('enable_mysql', true)
        ->set('mysql_host_id', $host->id)
        ->set('mysql_databases', ['app'])
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::where('name', 'Prod DB')->first();
    expect($source->mysql_host_id)->toBe($host->id);
    expect($source->config['mysql'])->toBe(['databases' => ['app']]);
    expect($source->config['mysql'])->not->toHaveKey('host');
});

it('requires at least one source type', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('name', 'empty')
        ->call('save')
        ->assertHasErrors('enable_sources');
});

it('loads per-type hosts when editing', function () {
    $host = BackupHost::factory()->withMysql()->create();
    $source = BackupSource::factory()->create([
        'config' => ['mysql' => ['databases' => ['x']]],
        'mysql_host_id' => $host->id,
    ]);

    Livewire::test(BackupSourceForm::class, ['sourceId' => $source->id])
        ->assertSet('mysql_host_id', $host->id);
});

it('creates a PostgreSQL source linked to a host', function () {
    $host = BackupHost::factory()->withPostgres()->create();

    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Production PostgreSQL')
        ->set('enable_postgres', true)
        ->set('postgres_host_id', $host->id)
        ->set('postgres_databases', ['myapp'])
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::first();
    expect($source->hasType('postgres'))->toBeTrue();
    expect($source->postgres_host_id)->toBe($host->id);
    expect($source->config['postgres']['databases'])->toContain('myapp');
});

it('validates required fields for PostgreSQL', function () {
    Livewire::test(BackupSourceForm::class)
        ->set('enable_postgres', true)
        ->set('name', '')
        ->set('postgres_databases', [])
        ->call('save')
        ->assertHasErrors(['name', 'postgres_host_id', 'postgres_databases']);
});

it('links a source postgres host and stores only databases', function () {
    $host = BackupHost::factory()->withPostgres()->create();

    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Prod PG')
        ->set('enable_postgres', true)
        ->set('postgres_host_id', $host->id)
        ->set('postgres_databases', ['app'])
        ->call('save')
        ->assertDispatched('source-saved');

    $source = BackupSource::where('name', 'Prod PG')->first();
    expect($source->postgres_host_id)->toBe($host->id);
    expect($source->config['postgres'])->toBe(['databases' => ['app']]);
    expect($source->config['postgres'])->not->toHaveKey('host');
});
