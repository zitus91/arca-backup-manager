<?php

use App\Livewire\Backup\StorageDestinationForm;
use App\Livewire\Backup\StorageDestinationIndex;
use App\Models\BackupStorageDestination;
use Livewire\Livewire;

it('renders storage destination index', function () {
    BackupStorageDestination::factory()->s3()->create();
    BackupStorageDestination::factory()->ftp()->create();

    Livewire::test(StorageDestinationIndex::class)
        ->assertStatus(200)
        ->assertSee('S3')
        ->assertSee('FTP');
});

it('filters destinations by type', function () {
    BackupStorageDestination::factory()->s3()->create(['name' => 'S3 Prod']);
    BackupStorageDestination::factory()->ftp()->create(['name' => 'FTP Staging']);

    Livewire::test(StorageDestinationIndex::class)
        ->set('filterType', 's3')
        ->assertSee('S3 Prod')
        ->assertDontSee('FTP Staging');
});

it('filters destinations by status', function () {
    BackupStorageDestination::factory()->s3()->create(['name' => 'Active One']);
    BackupStorageDestination::factory()->ftp()->inactive()->create(['name' => 'Inactive One']);

    Livewire::test(StorageDestinationIndex::class)
        ->set('filterStatus', 'active')
        ->assertSee('Active One')
        ->assertDontSee('Inactive One');
});

it('creates an S3 destination with encrypted config', function () {
    Livewire::test(StorageDestinationForm::class)
        ->set('name', 'My S3 Bucket')
        ->set('type', 's3')
        ->set('s3_bucket', 'my-bucket')
        ->set('s3_region', 'us-east-1')
        ->set('s3_access_key', 'AKIAIOSFODNN7EXAMPLE')
        ->set('s3_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY')
        ->set('is_active', true)
        ->call('save')
        ->assertDispatched('destination-saved');

    $destination = BackupStorageDestination::first();
    expect($destination->name)->toBe('My S3 Bucket');
    expect($destination->type)->toBe('s3');
    expect($destination->config)->toBeArray();
    expect($destination->config['bucket'])->toBe('my-bucket');
    expect($destination->config['access_key'])->toBe('AKIAIOSFODNN7EXAMPLE');

    // Verify config is encrypted in DB (raw value should not be plaintext)
    $raw = \DB::table('backup_storage_destinations')->first();
    expect($raw->config)->not->toContain('AKIAIOSFODNN7EXAMPLE');
});

it('creates an FTP destination', function () {
    Livewire::test(StorageDestinationForm::class)
        ->set('name', 'My FTP Server')
        ->set('type', 'ftp')
        ->set('ftp_host', 'ftp.example.com')
        ->set('ftp_port', 21)
        ->set('ftp_username', 'admin')
        ->set('ftp_password', 'secret123')
        ->set('ftp_root_path', '/backups')
        ->set('ftp_passive', true)
        ->set('ftp_ssl', false)
        ->call('save')
        ->assertDispatched('destination-saved');

    $destination = BackupStorageDestination::first();
    expect($destination->type)->toBe('ftp');
    expect($destination->config['host'])->toBe('ftp.example.com');
});

it('validates required fields for S3', function () {
    Livewire::test(StorageDestinationForm::class)
        ->set('type', 's3')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name', 's3_bucket', 's3_access_key', 's3_secret_key'])
        ->assertHasNoErrors(['s3_region']);
});

it('validates required fields for FTP', function () {
    Livewire::test(StorageDestinationForm::class)
        ->set('type', 'ftp')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name', 'ftp_host', 'ftp_username', 'ftp_password']);
});

it('deletes a destination', function () {
    $dest = BackupStorageDestination::factory()->s3()->create();

    Livewire::test(StorageDestinationIndex::class)
        ->call('delete', $dest->id);

    expect(BackupStorageDestination::count())->toBe(0);
});

it('opens edit form with existing data', function () {
    $dest = BackupStorageDestination::factory()->s3()->create([
        'name' => 'Existing S3',
    ]);

    Livewire::test(StorageDestinationForm::class, ['destinationId' => $dest->id])
        ->assertSet('name', 'Existing S3')
        ->assertSet('type', 's3');
});
