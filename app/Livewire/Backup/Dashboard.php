<?php

namespace App\Livewire\Backup;

use App\Models\BackupJob;
use App\Models\BackupLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    #[On('echo:backup-jobs,.backup.started')]
    public function onBackupStarted(): void
    {
        unset($this->stats);
        unset($this->recentLogs);
    }

    #[On('echo:backup-jobs,.backup.completed')]
    public function onBackupCompleted(): void
    {
        unset($this->stats);
        unset($this->recentLogs);
        unset($this->chartData);
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'active_jobs' => BackupJob::active()->count(),
            'last_success' => BackupLog::ofStatus('success')->latest('finished_at')->first(),
            'last_failure' => BackupLog::ofStatus('failed')->latest('finished_at')->first(),
            'today_count' => BackupLog::whereDate('started_at', today())->count(),
            'today_success' => BackupLog::whereDate('started_at', today())->ofStatus('success')->count(),
            'today_failed' => BackupLog::whereDate('started_at', today())->ofStatus('failed')->count(),
            'running' => BackupLog::ofStatus('running')->count(),
        ];
    }

    #[Computed]
    public function recentLogs(): \Illuminate\Database\Eloquent\Collection
    {
        return BackupLog::with(['job.source', 'job.destination'])
            ->latest('started_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function chartData(): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $label = $date->format('d/m');

            $data[] = [
                'label' => $label,
                'success' => BackupLog::whereDate('started_at', $dateStr)->ofStatus('success')->count(),
                'failed' => BackupLog::whereDate('started_at', $dateStr)->ofStatus('failed')->count(),
            ];
        }

        return $data;
    }

    public function render()
    {
        return view('livewire.backup.dashboard');
    }
}
