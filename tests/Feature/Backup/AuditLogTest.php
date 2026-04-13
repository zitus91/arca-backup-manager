<?php

use App\Livewire\Backup\AuditLogIndex;
use App\Models\AuditLog;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders audit log index', function () {
    Livewire::test(AuditLogIndex::class)
        ->assertStatus(200);
});

it('shows audit log entries', function () {
    AuditLog::factory()->count(5)->create();

    $component = Livewire::test(AuditLogIndex::class);

    expect($component->viewData('logs'))->toHaveCount(5);
});

it('filters audit logs by action', function () {
    AuditLog::factory()->create(['action' => 'login']);
    AuditLog::factory()->create(['action' => 'backup_run']);

    $component = Livewire::test(AuditLogIndex::class)
        ->set('filterAction', 'login');

    $ids = $component->viewData('logs')->pluck('action')->all();
    expect($ids)->each->toBe('login');
});

it('filters audit logs by user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    AuditLog::factory()->create(['user_id' => $user1->id]);
    AuditLog::factory()->create(['user_id' => $user2->id]);

    $component = Livewire::test(AuditLogIndex::class)
        ->set('filterUserId', (string) $user1->id);

    expect($component->viewData('logs'))->toHaveCount(1);
    expect($component->viewData('logs')->first()->user_id)->toBe($user1->id);
});

it('filters audit logs by date range', function () {
    AuditLog::factory()->create(['created_at' => now()->subDays(10)]);
    AuditLog::factory()->create(['created_at' => now()->subDays(2)]);

    $component = Livewire::test(AuditLogIndex::class)
        ->set('filterDateFrom', now()->subDays(5)->toDateString());

    expect($component->viewData('logs'))->toHaveCount(1);
});

it('records audit log via static record method', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    AuditLog::record('test_action', 'Test description');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'test_action',
        'description' => 'Test description',
        'user_id' => $user->id,
    ]);
});
