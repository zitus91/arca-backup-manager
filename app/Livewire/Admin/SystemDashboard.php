<?php

namespace App\Livewire\Admin;

use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\BackupSource;
use App\Models\BackupStorageDestination;
use App\Models\Scopes\OwnedByUserScope;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class SystemDashboard extends Component
{
    /** Query builder for a backup model with the per-user ownership scope removed. */
    private function unscoped(string $modelClass)
    {
        return $modelClass::withoutGlobalScope(OwnedByUserScope::class);
    }

    #[Computed]
    public function stats(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        $total = $this->unscoped(BackupLog::class)
            ->where('started_at', '>=', $thirtyDaysAgo)
            ->whereIn('status', ['success', 'failed'])
            ->count();
        $success = $this->unscoped(BackupLog::class)
            ->where('started_at', '>=', $thirtyDaysAgo)
            ->where('status', 'success')
            ->count();

        return [
            'total_users' => User::count(),
            'total_jobs' => $this->unscoped(BackupJob::class)->count(),
            'active_jobs' => $this->unscoped(BackupJob::class)->where('is_active', true)->count(),
            'total_sources' => $this->unscoped(BackupSource::class)->count(),
            'total_destinations' => $this->unscoped(BackupStorageDestination::class)->count(),
            'total_backups' => $this->unscoped(BackupLog::class)->count(),
            'running' => $this->unscoped(BackupLog::class)->where('status', 'running')->count(),
            'today_failed' => $this->unscoped(BackupLog::class)->whereDate('started_at', today())->where('status', 'failed')->count(),
            'total_storage_bytes' => $this->unscoped(BackupLog::class)->where('status', 'success')->whereNotNull('storage_path')->sum('file_size_bytes'),
            'success_rate' => $total > 0 ? round(($success / $total) * 100) : 100,
        ];
    }

    #[Computed]
    public function perUser(): array
    {
        return User::orderBy('name')->get()->map(function (User $user) {
            $jobIds = $this->unscoped(BackupJob::class)->where('user_id', $user->id)->pluck('id');

            return [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'jobs' => $jobIds->count(),
                'sources' => $this->unscoped(BackupSource::class)->where('user_id', $user->id)->count(),
                'destinations' => $this->unscoped(BackupStorageDestination::class)->where('user_id', $user->id)->count(),
                'storage_bytes' => $this->unscoped(BackupLog::class)->where('user_id', $user->id)->where('status', 'success')->whereNotNull('storage_path')->sum('file_size_bytes'),
                'last_backup' => $this->unscoped(BackupLog::class)->where('user_id', $user->id)->where('status', 'success')->latest('finished_at')->value('finished_at'),
            ];
        })->all();
    }

    #[Computed]
    public function recentLogs()
    {
        return $this->unscoped(BackupLog::class)
            ->with(['job.source', 'job.user'])
            ->latest('started_at')
            ->limit(12)
            ->get();
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

        return round($size, 1).' '.$units[$i];
    }

    public function render()
    {
        return view('livewire.admin.system-dashboard');
    }
}
