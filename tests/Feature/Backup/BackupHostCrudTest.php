<?php

use App\Livewire\Backup\BackupHostForm;
use App\Livewire\Backup\BackupHostIndex;
use App\Models\BackupHost;
use Livewire\Livewire;

it('renders host index', function () {
    BackupHost::factory()->create();
    Livewire::test(BackupHostIndex::class)->assertStatus(200);
});

it('creates a host with key auth', function () {
    Livewire::test(BackupHostForm::class)
        ->set('name', 'prod-web-01')
        ->set('ssh_host', 'ssh.example.com')
        ->set('ssh_port', 22)
        ->set('ssh_user', 'ubuntu')
        ->set('ssh_auth_method', 'key')
        ->set('ssh_key_path', '/home/ubuntu/.ssh/id_rsa')
        ->call('save')
        ->assertDispatched('host-saved');

    $host = BackupHost::first();
    expect($host->name)->toBe('prod-web-01');
    expect($host->config['host'])->toBe('ssh.example.com');
    expect($host->config['auth_method'])->toBe('key');
});

it('requires a key path when auth method is key', function () {
    Livewire::test(BackupHostForm::class)
        ->set('name', 'no-key')
        ->set('ssh_host', 'ssh.example.com')
        ->set('ssh_user', 'ubuntu')
        ->set('ssh_auth_method', 'key')
        ->set('ssh_key_path', '')
        ->call('save')
        ->assertHasErrors(['ssh_key_path']);
});

it('edits an existing host', function () {
    $host = BackupHost::factory()->create(['name' => 'old']);

    Livewire::test(BackupHostForm::class, ['hostId' => $host->id])
        ->set('name', 'new')
        ->call('save')
        ->assertDispatched('host-saved');

    expect($host->fresh()->name)->toBe('new');
});
