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
        ->set('enable_ssh', true)
        ->set('ssh_host', 'ssh.example.com')
        ->set('ssh_port', 22)
        ->set('ssh_user', 'ubuntu')
        ->set('ssh_auth_method', 'key')
        ->set('ssh_key_path', '/home/ubuntu/.ssh/id_rsa')
        ->call('save')
        ->assertDispatched('host-saved');

    $host = BackupHost::first();
    expect($host->name)->toBe('prod-web-01');
    expect($host->config['ssh']['host'])->toBe('ssh.example.com');
    expect($host->config['ssh']['auth_method'])->toBe('key');
});

it('requires a key path when auth method is key', function () {
    Livewire::test(BackupHostForm::class)
        ->set('name', 'no-key')
        ->set('enable_ssh', true)
        ->set('ssh_host', 'ssh.example.com')
        ->set('ssh_user', 'ubuntu')
        ->set('ssh_auth_method', 'key')
        ->set('ssh_key_path', '')
        ->call('save')
        ->assertHasErrors(['ssh_key_path']);
});

it('creates a host offering mysql + filesystem with ssh', function () {
    Livewire::test(App\Livewire\Backup\BackupHostForm::class)
        ->set('name', 'db-prod')
        ->set('enable_ssh', true)
        ->set('ssh_host', 'ssh.example.com')->set('ssh_user', 'ubuntu')
        ->set('ssh_auth_method', 'key')->set('ssh_key_path', '/k')
        ->set('enable_mysql', true)
        ->set('mysql_host', '127.0.0.1')->set('mysql_port', 3306)
        ->set('mysql_user', 'root')->set('mysql_password', 'secret')
        ->set('enable_filesystem', true)
        ->call('save')
        ->assertDispatched('host-saved');

    $host = App\Models\BackupHost::where('name', 'db-prod')->first();
    expect($host->offers('mysql'))->toBeTrue();
    expect($host->offers('filesystem'))->toBeTrue();
    expect($host->offers('mongodb'))->toBeFalse();
    expect($host->config['mysql']['user'])->toBe('root');
    expect($host->config['ssh']['host'])->toBe('ssh.example.com');
});

it('requires at least one service or ssh', function () {
    Livewire::test(App\Livewire\Backup\BackupHostForm::class)
        ->set('name', 'empty')
        ->set('enable_ssh', false)->set('enable_mysql', false)
        ->set('enable_mongodb', false)->set('enable_filesystem', false)
        ->call('save')
        ->assertHasErrors('enable_services');
});

it('edits an existing host', function () {
    $host = BackupHost::factory()->create(['name' => 'old']);

    Livewire::test(BackupHostForm::class, ['hostId' => $host->id])
        ->set('name', 'new')
        ->call('save')
        ->assertDispatched('host-saved');

    expect($host->fresh()->name)->toBe('new');
});
