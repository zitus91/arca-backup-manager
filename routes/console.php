<?php

use App\Jobs\Backup\ProcessBackupJob;
use App\Models\BackupJob;
use App\Models\BackupLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup Scheduler - runs every minute, checks for due backup jobs
Schedule::call(function () {
    $schedulerService = app(\App\Services\Backup\BackupSchedulerService::class);
    $dueJobs = BackupJob::due()->with(['source', 'destination'])->get();

    foreach ($dueJobs as $job) {
        $log = BackupLog::create([
            'backup_job_id' => $job->id,
            'user_id' => $job->user_id,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        ProcessBackupJob::dispatch($job->id, $log->id);

        // Aggiorna next_run_at immediatamente per evitare re-dispatch al prossimo tick
        $schedulerService->updateNextRun($job);
    }
})->everyMinute()->name('backup-scheduler')->withoutOverlapping();

// Recovery - marks jobs stuck in running/pending as failed (handles worker crashes)
Schedule::command('backup:recover-stale-jobs')->everyFiveMinutes()->name('backup-recover-stale')->withoutOverlapping();
