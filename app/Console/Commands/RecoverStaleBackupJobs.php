<?php

namespace App\Console\Commands;

use App\Events\Backup\BackupJobCompleted;
use App\Models\BackupLog;
use App\Models\RestoreLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RecoverStaleBackupJobs extends Command
{
    protected $signature = 'backup:recover-stale-jobs
                            {--minutes=70 : Minutes after which a running/pending job is considered stale}';

    protected $description = 'Mark stale running or pending backup jobs as failed and prune leftover temp files (e.g. after a worker crash)';

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

            $this->pruneTempFiles($threshold->getTimestamp());

            return self::SUCCESS;
        }

        foreach ($staleLogs as $log) {
            $duration = now()->diffInSeconds($log->started_at);

            $log->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'error_message' => 'Job terminated: worker crashed or timed out after recovery check.',
            ]);

            try {
                event(new BackupJobCompleted(
                    jobId: $log->backup_job_id,
                    logId: $log->id,
                    status: 'failed',
                    jobName: $log->job?->name ?? 'Unknown',
                    errorMessage: 'Job terminated: worker crashed or timed out.',
                ));
            } catch (\Throwable) {
                // Broadcast failure must not block the recovery
            }

            $this->line("  Recovered log <comment>#{$log->id}</comment> for job <comment>#{$log->backup_job_id}</comment> (was {$log->getOriginal('status')})");
        }

        $this->info("Recovered {$staleLogs->count()} stale backup job(s).");

        $this->pruneTempFiles($threshold->getTimestamp());

        return self::SUCCESS;
    }

    /**
     * Remove leftover backup/restore temp files. A job kills its own temp dir in a
     * finally block, so anything still here belongs to a worker that was killed.
     */
    protected function pruneTempFiles(int $threshold): void
    {
        $roots = [
            storage_path('app/backups/tmp') => BackupLog::class,
            storage_path('app/restores/tmp') => RestoreLog::class,
        ];

        $pruned = 0;

        foreach ($roots as $root => $model) {
            foreach (glob($root.'/*') ?: [] as $path) {
                // Temp dirs are named after the log id: never touch a job still working
                $logId = (int) strtok(basename($path), '-');

                if ($logId > 0 && $model::whereKey($logId)->whereIn('status', ['running', 'pending'])->exists()) {
                    continue;
                }

                if (filemtime($path) > $threshold) {
                    continue;
                }

                is_dir($path) ? File::deleteDirectory($path) : File::delete($path);
                $pruned++;
            }
        }

        if ($pruned > 0) {
            $this->info("Pruned {$pruned} leftover temp file(s)/dir(s).");
        }
    }
}
