<?php

use App\Models\BackupJob;
use App\Services\Backup\BackupSchedulerService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new BackupSchedulerService();
});

it('returns null for manual schedule', function () {
    $job = BackupJob::factory()->manual()->make();

    expect($this->service->calculateNextRun($job))->toBeNull();
});

it('calculates next hourly run', function () {
    $job = BackupJob::factory()->make(['schedule_type' => 'hourly']);

    $next = $this->service->calculateNextRun($job, Carbon::parse('2026-02-25 10:30:00'));

    expect($next->format('Y-m-d H:i'))->toBe('2026-02-25 11:00');
});

it('calculates next daily run', function () {
    $job = BackupJob::factory()->daily()->make(['schedule_time' => '03:00']);

    $next = $this->service->calculateNextRun($job, Carbon::parse('2026-02-25 10:00:00'));

    expect($next->format('H:i'))->toBe('03:00');
    expect($next->format('Y-m-d'))->toBe('2026-02-26');
});

it('calculates next daily run same day if before schedule time', function () {
    $job = BackupJob::factory()->daily()->make(['schedule_time' => '15:00']);

    $next = $this->service->calculateNextRun($job, Carbon::parse('2026-02-25 10:00:00'));

    expect($next->format('Y-m-d H:i'))->toBe('2026-02-25 15:00');
});

it('calculates next weekly run', function () {
    $job = BackupJob::factory()->weekly()->make([
        'schedule_time' => '02:00',
        'schedule_day_of_week' => 1, // Monday
    ]);

    // Wednesday
    $next = $this->service->calculateNextRun($job, Carbon::parse('2026-02-25 10:00:00'));

    expect($next->dayOfWeek)->toBe(1);
    expect($next->format('H:i'))->toBe('02:00');
});

it('calculates next monthly run', function () {
    $job = BackupJob::factory()->monthly()->make([
        'schedule_time' => '01:00',
        'schedule_day_of_month' => 15,
    ]);

    $next = $this->service->calculateNextRun($job, Carbon::parse('2026-02-20 10:00:00'));

    expect($next->day)->toBe(15);
    expect($next->format('H:i'))->toBe('01:00');
    expect($next->month)->toBe(3); // March since Feb 15 has passed (from Feb 20)
});

it('calculates next custom cron run', function () {
    $job = BackupJob::factory()->custom()->make([
        'schedule_cron' => '0 */6 * * *',
    ]);

    $next = $this->service->calculateNextRun($job, Carbon::parse('2026-02-25 10:30:00'));

    expect($next->format('H:i'))->toBe('12:00');
});

it('describes cron expressions', function () {
    expect($this->service->describeCron('0 * * * *'))->toBe('Every hour');
    expect($this->service->describeCron('0 3 * * *'))->toBe('Daily at 3:00');
    expect($this->service->describeCron('*/15 * * * *'))->toBe('Every 15 minutes');
});
