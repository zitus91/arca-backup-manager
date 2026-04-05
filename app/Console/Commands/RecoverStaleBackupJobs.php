<?php

namespace App\Console\Commands;

use App\Events\Backup\BackupJobCompleted;
use App\Models\BackupLog;
use Illuminate\Console\Command;

class RecoverStaleBackupJobs extends Command
{
    protected $signature = 'backup:recover-stale-jobs
                            {--minutes=70 : Minutes after which a running/pending job is considered stale}';

    protected $description = 'Mark stale running or pending backup jobs as failed (e.g. after a worker crash)';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $threshold = now()->subMinutes($minutes);

        $staleLogs = BackupLog::with('job')
            ->whereIn('status', ['running', 'pending'])
            ->where('started_at', '<', $threshold)
            ->get();

        if ($staleLogs->isEmpty()) {
            $this->info('No stale backup jobs found.');

            return self::SUCCESS;
        }

        foreach ($staleLogs as $log) {
            $duration = now()->diffInSeconds($log->started_at);

            $log->update([
                'status'           => 'failed',
                'finished_at'      => now(),
                'duration_seconds' => $duration,
                'error_message'    => 'Job terminated: worker crashed or timed out after recovery check.',
            ]);

            try {
                event(new BackupJobCompleted(
                    jobId:        $log->backup_job_id,
                    logId:        $log->id,
                    status:       'failed',
                    jobName:      $log->job?->name ?? 'Unknown',
                    errorMessage: 'Job terminated: worker crashed or timed out.',
                ));
            } catch (\Throwable) {
                // Broadcast failure must not block the recovery
            }

            $this->line("  Recovered log <comment>#{$log->id}</comment> for job <comment>#{$log->backup_job_id}</comment> (was {$log->getOriginal('status')})");
        }

        $this->info("Recovered {$staleLogs->count()} stale backup job(s).");

        return self::SUCCESS;
    }
}
