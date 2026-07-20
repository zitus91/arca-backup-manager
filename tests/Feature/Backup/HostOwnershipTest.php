<?php

use App\Livewire\Backup\BackupSourceForm;
use App\Models\BackupHost;
use App\Models\User;
use Livewire\Livewire;

it('rejects a mysql_host_id belonging to another user (IDOR)', function () {
    $userB = User::factory()->create();
    $this->actingAs($userB);
    $othersHost = BackupHost::factory()->withMysql()->create();

    $userA = User::factory()->create();
    $this->actingAs($userA);

    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Cross Tenant MySQL')
        ->set('enable_mysql', true)
        ->set('mysql_host_id', $othersHost->id)
        ->set('mysql_databases', ['x'])
        ->call('save')
        ->assertHasErrors('mysql_host_id');
});

it('rejects a mongodb_host_id belonging to another user (IDOR)', function () {
    $userB = User::factory()->create();
    $this->actingAs($userB);
    $othersHost = BackupHost::factory()->withMongodb()->create();

    $userA = User::factory()->create();
    $this->actingAs($userA);

    Livewire::test(BackupSourceForm::class)
        ->set('name', 'Cross Tenant Mongo')
        ->set('enable_mongodb', true)
        ->set('mongodb_host_id', $othersHost->id)
        ->set('mongodb_databases', ['x'])
        ->call('save')
        ->assertHasErrors('mongodb_host_id');
});
