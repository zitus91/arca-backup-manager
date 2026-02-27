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
    $dueJobs = BackupJob::due()->with(['source', 'destination'])->get();

    foreach ($dueJobs as $job) {
        $log = BackupLog::create([
            'backup_job_id' => $job->id,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        ProcessBackupJob::dispatch($job->id, $log->id);
    }
})->everyMinute()->name('backup-scheduler')->withoutOverlapping();
