<?php

use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\BackupSource;
use App\Models\User;

it('scopes resources to the authenticated owner', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->actingAs($a);
    BackupSource::factory()->mysql()->create();

    $this->actingAs($b);
    BackupSource::factory()->mysql()->create();

    $this->actingAs($a);
    expect(BackupSource::count())->toBe(1);
    expect(BackupSource::first()->user_id)->toBe($a->id);

    $this->actingAs($b);
    expect(BackupSource::count())->toBe(1);
    expect(BackupSource::first()->user_id)->toBe($b->id);
});

it('auto-stamps the owner on a resource created in a web request', function () {
    $a = User::factory()->create();
    $this->actingAs($a);

    $job = BackupJob::factory()->create();

    expect($job->fresh()->user_id)->toBe($a->id);
});

it('does not scope queries when unauthenticated (scheduler / queue context)', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    BackupJob::factory()->create(['user_id' => $a->id]);
    BackupJob::factory()->create(['user_id' => $b->id]);

    // No actingAs: background jobs see every owner's rows.
    expect(BackupJob::count())->toBe(2);
});

it('a scheduled pending log inherits and is scoped to its job owner', function () {
    $a = User::factory()->create();
    $job = BackupJob::factory()->create(['user_id' => $a->id]);

    // Mirrors the scheduler in routes/console.php (console context, no auth).
    $log = BackupLog::create([
        'backup_job_id' => $job->id,
        'user_id' => $job->user_id,
        'status' => 'pending',
        'started_at' => now(),
    ]);

    expect($log->user_id)->toBe($a->id);

    $this->actingAs($a);
    expect(BackupLog::count())->toBe(1);

    $this->actingAs(User::factory()->create());
    expect(BackupLog::count())->toBe(0);
});
