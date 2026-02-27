<?php

namespace App\Services\Backup;

use App\Models\BackupJob;
use Carbon\Carbon;
use Cron\CronExpression;

class BackupSchedulerService
{
    /**
     * Calculate the next run time for a backup job based on its schedule type.
     */
    public function calculateNextRun(BackupJob $job, ?Carbon $from = null): ?Carbon
    {
        $from = $from ?? now();

        return match ($job->schedule_type) {
            'manual' => null,
            'hourly' => $this->nextHourly($from),
            'daily' => $this->nextDaily($from, $job->schedule_time),
            'weekly' => $this->nextWeekly($from, $job->schedule_time, $job->schedule_day_of_week),
            'monthly' => $this->nextMonthly($from, $job->schedule_time, $job->schedule_day_of_month),
            'custom' => $this->nextCron($from, $job->schedule_cron),
            default => null,
        };
    }

    /**
     * Update the next_run_at for a job after execution.
     */
    public function updateNextRun(BackupJob $job): void
    {
        $nextRun = $this->calculateNextRun($job);
        $job->update([
            'last_run_at' => now(),
            'next_run_at' => $nextRun,
        ]);
    }

    protected function nextHourly(Carbon $from): Carbon
    {
        return $from->copy()->addHour()->startOfHour();
    }

    protected function nextDaily(Carbon $from, ?string $time): Carbon
    {
        $next = $from->copy();

        if ($time) {
            [$hour, $minute] = explode(':', $time);
            $next->setTime((int) $hour, (int) $minute);
        }

        if ($next->lte($from)) {
            $next->addDay();
        }

        return $next;
    }

    protected function nextWeekly(Carbon $from, ?string $time, ?int $dayOfWeek): Carbon
    {
        $next = $from->copy();
        $dayOfWeek = $dayOfWeek ?? 1; // Default Monday

        if ($time) {
            [$hour, $minute] = explode(':', $time);
            $next->setTime((int) $hour, (int) $minute);
        }

        // Move to next occurrence of the target day
        while ($next->dayOfWeek !== $dayOfWeek || $next->lte($from)) {
            $next->addDay();
        }

        if ($time) {
            [$hour, $minute] = explode(':', $time);
            $next->setTime((int) $hour, (int) $minute);
        }

        return $next;
    }

    protected function nextMonthly(Carbon $from, ?string $time, ?int $dayOfMonth): Carbon
    {
        $next = $from->copy();
        $dayOfMonth = $dayOfMonth ?? 1;

        if ($time) {
            [$hour, $minute] = explode(':', $time);
            $next->setTime((int) $hour, (int) $minute);
        }

        // Set the target day of month
        $next->day = min($dayOfMonth, $next->daysInMonth);

        if ($next->lte($from)) {
            $next->addMonth();
            $next->day = min($dayOfMonth, $next->daysInMonth);
        }

        if ($time) {
            [$hour, $minute] = explode(':', $time);
            $next->setTime((int) $hour, (int) $minute);
        }

        return $next;
    }

    protected function nextCron(Carbon $from, ?string $cronExpression): ?Carbon
    {
        if (! $cronExpression) {
            return null;
        }

        try {
            $cron = new CronExpression($cronExpression);

            return Carbon::instance($cron->getNextRunDate($from->toDateTime()));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get a human-readable description of a cron expression.
     */
    public function describeCron(string $cronExpression): string
    {
        try {
            $parts = explode(' ', trim($cronExpression));
            if (count($parts) !== 5) {
                return $cronExpression;
            }

            [$min, $hour, $dom, $month, $dow] = $parts;

            if ($min === '0' && $hour === '*') {
                return 'Every hour';
            }
            if ($min === '0' && $dom === '*' && $month === '*' && $dow === '*') {
                return "Daily at {$hour}:00";
            }
            if (str_starts_with($hour, '*/')) {
                return 'Every ' . substr($hour, 2) . ' hours';
            }
            if (str_starts_with($min, '*/')) {
                return 'Every ' . substr($min, 2) . ' minutes';
            }

            return $cronExpression;
        } catch (\Throwable) {
            return $cronExpression;
        }
    }
}
