<?php

namespace App\Livewire\Backup;

use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\BackupSource;
use App\Models\BackupStorageDestination;
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
        unset($this->jobsHealth);
    }

    #[On('echo:backup-jobs,.backup.completed')]
    public function onBackupCompleted(): void
    {
        unset($this->stats);
        unset($this->recentLogs);
        unset($this->chartData);
        unset($this->jobsHealth);
        unset($this->storageBreakdown);
    }

    #[Computed]
    public function stats(): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        $totalLast30 = BackupLog::where('started_at', '>=', $thirtyDaysAgo)
            ->whereIn('status', ['success', 'failed'])
            ->count();
        $successLast30 = BackupLog::where('started_at', '>=', $thirtyDaysAgo)
            ->ofStatus('success')
            ->count();

        $totalStorageBytes = BackupLog::ofStatus('success')
            ->whereNotNull('storage_path')
            ->sum('file_size_bytes');

        $avgDuration = BackupLog::ofStatus('success')
            ->where('started_at', '>=', $thirtyDaysAgo)
            ->avg('duration_seconds');

        return [
            'active_jobs' => BackupJob::active()->count(),
            'total_jobs' => BackupJob::count(),
            'total_sources' => BackupSource::count(),
            'total_destinations' => BackupStorageDestination::count(),
            'last_success' => BackupLog::ofStatus('success')->latest('finished_at')->first(),
            'last_failure' => BackupLog::ofStatus('failed')->latest('finished_at')->first(),
            'today_count' => BackupLog::whereDate('started_at', today())->count(),
            'today_success' => BackupLog::whereDate('started_at', today())->ofStatus('success')->count(),
            'today_failed' => BackupLog::whereDate('started_at', today())->ofStatus('failed')->count(),
            'running' => BackupLog::ofStatus('running')->count(),
            'success_rate' => $totalLast30 > 0 ? round(($successLast30 / $totalLast30) * 100) : 100,
            'total_storage_bytes' => $totalStorageBytes,
            'avg_duration' => $avgDuration ? (int) round($avgDuration) : null,
            'total_backups_ever' => BackupLog::count(),
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

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $label = $date->format('d/m');
            $isToday = $i === 0;

            $data[] = [
                'label' => $label,
                'success' => BackupLog::whereDate('started_at', $dateStr)->ofStatus('success')->count(),
                'failed' => BackupLog::whereDate('started_at', $dateStr)->ofStatus('failed')->count(),
                'is_today' => $isToday,
            ];
        }

        return $data;
    }

    #[Computed]
    public function jobsHealth(): \Illuminate\Database\Eloquent\Collection
    {
        return BackupJob::with(['source', 'destination', 'latestLog'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function upcomingBackups(): \Illuminate\Database\Eloquent\Collection
    {
        return BackupJob::with(['source', 'destination'])
            ->active()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '>', now())
            ->orderBy('next_run_at')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function storageBreakdown(): array
    {
        $destinations = BackupStorageDestination::all();
        $breakdown = [];

        foreach ($destinations as $dest) {
            $jobIds = BackupJob::where('backup_storage_destination_id', $dest->id)->pluck('id');
            $bytes = BackupLog::whereIn('backup_job_id', $jobIds)
                ->ofStatus('success')
                ->whereNotNull('storage_path')
                ->sum('file_size_bytes');
            $count = BackupLog::whereIn('backup_job_id', $jobIds)
                ->ofStatus('success')
                ->whereNotNull('storage_path')
                ->count();

            $breakdown[] = [
                'name' => $dest->name,
                'type' => $dest->type,
                'bytes' => $bytes,
                'count' => $count,
            ];
        }

        // Sort by bytes descending
        usort($breakdown, fn($a, $b) => $b['bytes'] <=> $a['bytes']);

        return $breakdown;
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1) . ' ' . $units[$i];
    }

    public function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        if ($minutes < 60) {
            return $minutes . 'm ' . $secs . 's';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return $hours . 'h ' . $mins . 'm';
    }

    public function render()
    {
        return view('livewire.backup.dashboard');
    }
}
