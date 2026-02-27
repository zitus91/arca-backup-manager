<?php

use App\Livewire\Backup\Dashboard;
use App\Models\BackupJob;
use App\Models\BackupLog;
use Livewire\Livewire;

it('renders dashboard', function () {
    Livewire::test(Dashboard::class)
        ->assertStatus(200);
});

it('shows correct stats', function () {
    BackupJob::factory()->count(3)->create(['is_active' => true]);
    BackupJob::factory()->inactive()->create();

    BackupLog::factory()->success()->create(['started_at' => now()]);
    BackupLog::factory()->failed()->create(['started_at' => now()]);

    $component = Livewire::test(Dashboard::class);

    expect($component->get('stats')['active_jobs'])->toBe(3);
    expect($component->get('stats')['today_count'])->toBe(2);
});

it('shows recent logs', function () {
    BackupLog::factory()->count(5)->create();

    $component = Livewire::test(Dashboard::class);

    expect($component->get('recentLogs'))->toHaveCount(5);
});

it('provides chart data for last 7 days', function () {
    $component = Livewire::test(Dashboard::class);

    expect($component->get('chartData'))->toHaveCount(7);
    expect($component->get('chartData')[0])->toHaveKeys(['label', 'success', 'failed']);
});
