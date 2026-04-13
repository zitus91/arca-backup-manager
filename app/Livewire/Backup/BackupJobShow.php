<?php

namespace App\Livewire\Backup;

use App\Jobs\Backup\ProcessBackupJob;
use App\Jobs\Restore\ProcessRestoreJob;
use App\Models\AuditLog;
use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\BackupSource;
use App\Models\RestoreLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class BackupJobShow extends Component
{
    use WithPagination;

    public int $jobId;

    public string $logsTab = 'backups'; // 'backups' | 'restores'

    // ── Edit form ──────────────────────────────────────────────
    public bool $showEditForm = false;

    // ── Log detail modal ───────────────────────────────────────
    public bool $showLogDetail = false;
    public ?int $detailLogId = null;

    // ── Restore modal ──────────────────────────────────────────
    public bool $showRestoreModal = false;
    public ?int $selectedBackupLogId = null;
    public string $restoreType = 'full';
    public array $selectedBackupInfo = [];
    public array $selectedDatabases = [];
    public array $selectedPaths = [];
    public array $customDbNames = [];
    public array $customPaths = [];
    public string $restoreTarget = 'same_host';
    public ?int $knownSourceId = null;
    public array $remoteConfig = [];
    public bool $overrideExisting = false;
    public bool $showConfirmation = false;
    public bool $isRestoring = false;

    public function mount(int $job): void
    {
        $this->jobId = $job;
        BackupJob::findOrFail($job);
    }

    // ── Real-time updates ──────────────────────────────────────

    #[On('echo:backup-jobs,.backup.started')]
    public function onBackupStarted(): void
    {
        unset($this->stats, $this->recentLogs, $this->job);
    }

    #[On('echo:backup-jobs,.backup.completed')]
    public function onBackupCompleted(): void
    {
        unset($this->stats, $this->recentLogs, $this->restoreStats, $this->job);
    }

    #[On('job-saved')]
    public function onJobSaved(): void
    {
        $this->showEditForm = false;
        unset($this->job, $this->stats);
        session()->flash('message', __('backup-job-show.job_saved'));
    }

    // ── Computed properties ───────────────────────────────────

    #[Computed]
    public function job(): BackupJob
    {
        return BackupJob::with(['source', 'destination', 'latestLog'])
            ->findOrFail($this->jobId);
    }

    #[Computed]
    public function stats(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        $total     = BackupLog::ofJob($this->jobId)->count();
        $success   = BackupLog::ofJob($this->jobId)->ofStatus('success')->count();
        $failed    = BackupLog::ofJob($this->jobId)->ofStatus('failed')->count();
        $running   = BackupLog::ofJob($this->jobId)->ofStatus('running')->count();

        $totalBytes = BackupLog::ofJob($this->jobId)
            ->ofStatus('success')
            ->whereNotNull('storage_path')
            ->sum('file_size_bytes');

        $avgDuration = BackupLog::ofJob($this->jobId)
            ->ofStatus('success')
            ->where('started_at', '>=', $thirtyDaysAgo)
            ->avg('duration_seconds');

        $last30Total   = BackupLog::ofJob($this->jobId)
            ->where('started_at', '>=', $thirtyDaysAgo)
            ->whereIn('status', ['success', 'failed'])
            ->count();
        $last30Success = BackupLog::ofJob($this->jobId)
            ->where('started_at', '>=', $thirtyDaysAgo)
            ->ofStatus('success')
            ->count();

        // Restore stats
        $logIds       = BackupLog::ofJob($this->jobId)->pluck('id');
        $totalRestores = RestoreLog::whereIn('backup_log_id', $logIds)->count();
        $successRestores = RestoreLog::whereIn('backup_log_id', $logIds)->ofStatus('completed')->count();

        return [
            'total'            => $total,
            'success'          => $success,
            'failed'           => $failed,
            'running'          => $running,
            'total_bytes'      => $totalBytes,
            'avg_duration'     => $avgDuration ? (int) round($avgDuration) : null,
            'success_rate'     => $last30Total > 0 ? round(($last30Success / $last30Total) * 100) : ($total > 0 ? 100 : null),
            'total_restores'   => $totalRestores,
            'success_restores' => $successRestores,
        ];
    }

    #[Computed]
    public function recentLogs()
    {
        return BackupLog::ofJob($this->jobId)
            ->latest('started_at')
            ->paginate(10);
    }

    #[Computed]
    public function lockedLogs()
    {
        return BackupLog::ofJob($this->jobId)
            ->locked()
            ->whereNotNull('storage_path')
            ->latest('started_at')
            ->get();
    }

    #[Computed]
    public function restoreLogs()
    {
        $logIds = BackupLog::ofJob($this->jobId)->pluck('id');

        return RestoreLog::with(['backupLog', 'user'])
            ->whereIn('backup_log_id', $logIds)
            ->latest('started_at')
            ->paginate(10);
    }

    #[Computed]
    public function chartData(): array
    {
        $data = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = [
                'label'    => $date->format('d/m'),
                'success'  => BackupLog::ofJob($this->jobId)
                    ->whereDate('started_at', $date->format('Y-m-d'))
                    ->ofStatus('success')
                    ->count(),
                'failed'   => BackupLog::ofJob($this->jobId)
                    ->whereDate('started_at', $date->format('Y-m-d'))
                    ->ofStatus('failed')
                    ->count(),
                'is_today' => $i === 0,
            ];
        }

        return $data;
    }

    // ── Actions ───────────────────────────────────────────────

    public function openEdit(): void
    {
        $this->showEditForm = true;
    }

    public function closeEdit(): void
    {
        $this->showEditForm = false;
    }

    public function runNow(): void
    {
        $job = BackupJob::with(['source', 'destination'])->findOrFail($this->jobId);

        $log = BackupLog::create([
            'backup_job_id' => $job->id,
            'status'        => 'pending',
            'started_at'    => now(),
        ]);

        ProcessBackupJob::dispatch($job->id, $log->id);

        AuditLog::record('backup_job_run', "Manual run triggered for job: {$job->name}");

        unset($this->stats, $this->recentLogs, $this->job);

        session()->flash('message', __('backup-job-show.dispatched'));
    }

    public function setTab(string $tab): void
    {
        $this->logsTab = $tab;
        $this->resetPage();
        unset($this->recentLogs, $this->restoreLogs);
    }

    // ── Log detail ────────────────────────────────────────────

    public function openLogDetail(int $logId): void
    {
        $this->detailLogId = $logId;
        $this->showLogDetail = true;
    }

    public function closeLogDetail(): void
    {
        $this->showLogDetail = false;
        $this->detailLogId = null;
    }

    public function closeLogDetailAndRestore(int $id): void
    {
        $this->closeLogDetail();
        $this->openRestoreModal($id);
    }

    public function toggleLock(int $logId): void
    {
        $log = BackupLog::find($logId);

        if (! $log || $log->backup_job_id !== $this->jobId) {
            return;
        }

        $locked    = ! $log->is_locked;
        $now       = $locked ? now() : null;
        $lockedBy  = $locked ? auth()->id() : null;
        $lockData  = ['is_locked' => $locked, 'locked_at' => $now, 'locked_by' => $lockedBy];

        // Collect all IDs that need to be toggled
        $affectedIds = collect([$log->id]);

        if ($log->is_full) {
            // Full backup: propagate to all child incrementals
            $childIds = BackupLog::where('backup_job_id', $this->jobId)
                ->where('parent_backup_log_id', $log->id)
                ->pluck('id');
            $affectedIds = $affectedIds->merge($childIds);
        } else {
            // Incremental: when locking, also lock the full parent chain up to root
            //              when unlocking, only unlock itself
            if ($locked && $log->parent_backup_log_id) {
                $parentId = $log->parent_backup_log_id;
                while ($parentId) {
                    $affectedIds->push($parentId);
                    $parent   = BackupLog::select('id', 'parent_backup_log_id', 'is_full')->find($parentId);
                    $parentId = ($parent && ! $parent->is_full) ? $parent->parent_backup_log_id : null;
                }
            }
        }

        BackupLog::whereIn('id', $affectedIds->unique()->values())
            ->where('backup_job_id', $this->jobId)
            ->update($lockData);

        AuditLog::record(
            $locked ? 'backup_log.locked' : 'backup_log.unlocked',
            "backup_log_id={$logId} affected_ids=".$affectedIds->unique()->values()->implode(','). " backup_job_id={$this->jobId}"
        );

        $count = $affectedIds->unique()->count();
        $this->dispatch('notify',
            type: 'success',
            message: $locked
                ? __('backup-job-show.lock_chain_locked', ['count' => $count])
                : __('backup-job-show.lock_chain_unlocked', ['count' => $count])
        );

        unset($this->recentLogs, $this->lockedLogs);
    }

    // ── Restore modal ─────────────────────────────────────────

    public function openRestoreModal(int $backupLogId): void
    {
        $log = BackupLog::with(['job.source', 'job.destination'])->find($backupLogId);

        if (! $log || $log->status !== 'success' || ! $log->storage_path) {
            $this->dispatch('notify', type: 'error', message: __('restore.backup_not_available'));
            return;
        }

        $this->selectedBackupLogId = $backupLogId;
        $this->restoreType = 'full';

        $sourceConfig = $log->job->source->config ?? [];
        $this->selectedBackupInfo = [
            'job_name'          => $log->job->name,
            'source_name'       => $log->job->source->name,
            'destination_name'  => $log->job->destination->name,
            'file_name'         => $log->file_name,
            'backup_date'       => $log->started_at->format('d/m/Y H:i'),
            'file_size'         => $log->formatted_size,
            'has_mysql'         => isset($sourceConfig['mysql']),
            'has_mongodb'       => isset($sourceConfig['mongodb']),
            'has_filesystem'    => isset($sourceConfig['filesystem']),
            'mysql_databases'   => isset($sourceConfig['mysql'])
                ? ($sourceConfig['mysql']['databases'] ?? (isset($sourceConfig['mysql']['database']) ? [$sourceConfig['mysql']['database']] : []))
                : [],
            'mongodb_databases' => isset($sourceConfig['mongodb'])
                ? ($sourceConfig['mongodb']['databases'] ?? (isset($sourceConfig['mongodb']['database']) ? [$sourceConfig['mongodb']['database']] : []))
                : [],
            'filesystem_paths'  => isset($sourceConfig['filesystem'])
                ? ($sourceConfig['filesystem']['paths'] ?? (isset($sourceConfig['filesystem']['path']) ? [$sourceConfig['filesystem']['path']] : []))
                : [],
        ];

        $hasDb = $this->selectedBackupInfo['has_mysql'] || $this->selectedBackupInfo['has_mongodb'];
        $hasFs = $this->selectedBackupInfo['has_filesystem'];

        if ($hasDb && ! $hasFs) {
            $this->restoreType = 'db_only';
        } elseif (! $hasDb && $hasFs) {
            $this->restoreType = 'files_only';
        }

        $this->selectedDatabases = array_merge(
            $this->selectedBackupInfo['mysql_databases'] ?? [],
            $this->selectedBackupInfo['mongodb_databases'] ?? [],
        );
        $this->selectedPaths = $this->selectedBackupInfo['filesystem_paths'] ?? [];

        $timestamp = now()->format('Ymd_His');
        $this->customDbNames = [];
        $this->customPaths = [];

        foreach ($this->selectedBackupInfo['mysql_databases'] ?? [] as $db) {
            $this->customDbNames[] = ['original' => $db, 'target' => $db . '_restored_' . $timestamp, 'type' => 'mysql'];
        }
        foreach ($this->selectedBackupInfo['mongodb_databases'] ?? [] as $db) {
            $this->customDbNames[] = ['original' => $db, 'target' => $db . '_restored_' . $timestamp, 'type' => 'mongodb'];
        }
        foreach ($this->selectedBackupInfo['filesystem_paths'] ?? [] as $path) {
            $this->customPaths[] = ['original' => $path, 'target' => rtrim($path, '/') . '_restored_' . $timestamp];
        }

        $this->restoreTarget = 'same_host';
        $this->knownSourceId = null;
        $this->overrideExisting = false;
        $this->remoteConfig = [
            'mysql'      => ['host' => '', 'port' => '3306', 'username' => '', 'password' => ''],
            'mongodb'    => ['host' => '', 'port' => '27017', 'username' => '', 'password' => '', 'auth_database' => 'admin'],
            'filesystem' => ['ssh_host' => '', 'ssh_port' => '22', 'ssh_user' => '', 'ssh_key_path' => ''],
        ];

        $this->showRestoreModal = true;
        $this->showConfirmation = false;
    }

    public function closeRestoreModal(): void
    {
        $this->showRestoreModal = false;
        $this->selectedBackupLogId = null;
        $this->selectedBackupInfo = [];
        $this->selectedDatabases = [];
        $this->selectedPaths = [];
        $this->customDbNames = [];
        $this->customPaths = [];
        $this->restoreTarget = 'same_host';
        $this->knownSourceId = null;
        $this->remoteConfig = [];
        $this->overrideExisting = false;
        $this->showConfirmation = false;
    }

    public function resetCustomNames(): void
    {
        $timestamp = now()->format('Ymd_His');
        foreach ($this->customDbNames as $index => $item) {
            $this->customDbNames[$index]['target'] = $item['original'] . '_restored_' . $timestamp;
        }
        foreach ($this->customPaths as $index => $item) {
            $this->customPaths[$index]['target'] = rtrim($item['original'], '/') . '_restored_' . $timestamp;
        }
    }

    public function confirmRestore(): void
    {
        $hasSelectedDb = ! empty($this->selectedDatabases) && in_array($this->restoreType, ['full', 'db_only']);
        $hasSelectedFs = ! empty($this->selectedPaths) && in_array($this->restoreType, ['full', 'files_only']);

        if (! $hasSelectedDb && ! $hasSelectedFs) {
            $this->dispatch('notify', type: 'error', message: __('restore.no_items_selected'));
            return;
        }

        if (in_array($this->restoreTarget, ['remote_host', 'known_host'])) {
            if ($hasSelectedDb) {
                if (($this->selectedBackupInfo['has_mysql'] ?? false) && empty($this->remoteConfig['mysql']['host'])) {
                    $this->dispatch('notify', type: 'error', message: __('restore.remote_mysql_required'));
                    return;
                }
                if (($this->selectedBackupInfo['has_mongodb'] ?? false) && empty($this->remoteConfig['mongodb']['host'])) {
                    $this->dispatch('notify', type: 'error', message: __('restore.remote_mongodb_required'));
                    return;
                }
            }
            if ($hasSelectedFs && empty($this->remoteConfig['filesystem']['ssh_host'])) {
                $this->dispatch('notify', type: 'error', message: __('restore.remote_filesystem_required'));
                return;
            }
        }

        foreach ($this->customDbNames as $item) {
            if (in_array($item['original'], $this->selectedDatabases) && empty(trim($item['target']))) {
                $this->dispatch('notify', type: 'error', message: __('restore.custom_name_empty'));
                return;
            }
        }
        foreach ($this->customPaths as $item) {
            if (in_array($item['original'], $this->selectedPaths) && empty(trim($item['target']))) {
                $this->dispatch('notify', type: 'error', message: __('restore.custom_name_empty'));
                return;
            }
        }

        $this->showConfirmation = true;
    }

    public function executeRestore(): void
    {
        if (! $this->selectedBackupLogId) {
            return;
        }

        $this->isRestoring = true;

        $selectedItems = [];
        if (in_array($this->restoreType, ['full', 'db_only'])) {
            $allMysql = $this->selectedBackupInfo['mysql_databases'] ?? [];
            $allMongo = $this->selectedBackupInfo['mongodb_databases'] ?? [];
            $selectedItems['mysql_databases']   = array_values(array_intersect($this->selectedDatabases, $allMysql));
            $selectedItems['mongodb_databases']  = array_values(array_intersect($this->selectedDatabases, $allMongo));
        }
        if (in_array($this->restoreType, ['full', 'files_only'])) {
            $selectedItems['filesystem_paths'] = array_values($this->selectedPaths);
        }

        $customNames = ['databases' => [], 'paths' => []];
        foreach ($this->customDbNames as $item) {
            if (in_array($item['original'], $this->selectedDatabases)) {
                $customNames['databases'][$item['original']] = $item['target'];
            }
        }
        foreach ($this->customPaths as $item) {
            if (in_array($item['original'], $this->selectedPaths)) {
                $customNames['paths'][$item['original']] = $item['target'];
            }
        }

        $remoteHostConfig = null;
        if (in_array($this->restoreTarget, ['remote_host', 'known_host'])) {
            $remoteHostConfig = [];
            if ($this->selectedBackupInfo['has_mysql'] ?? false) $remoteHostConfig['mysql'] = $this->remoteConfig['mysql'];
            if ($this->selectedBackupInfo['has_mongodb'] ?? false) $remoteHostConfig['mongodb'] = $this->remoteConfig['mongodb'];
            if ($this->selectedBackupInfo['has_filesystem'] ?? false) $remoteHostConfig['filesystem'] = $this->remoteConfig['filesystem'];
        }

        $effectiveTarget = in_array($this->restoreTarget, ['remote_host', 'known_host']) ? 'remote_host' : $this->restoreTarget;

        $restoreLog = RestoreLog::create([
            'backup_log_id'      => $this->selectedBackupLogId,
            'user_id'            => auth()->id(),
            'restore_type'       => $this->restoreType,
            'restore_target'     => $effectiveTarget,
            'remote_host_config' => $remoteHostConfig,
            'custom_names'       => $customNames,
            'override_existing'  => $this->overrideExisting,
            'selected_items'     => $selectedItems,
            'status'             => 'pending',
            'started_at'         => now(),
        ]);

        ProcessRestoreJob::dispatch($restoreLog->id);

        $this->closeRestoreModal();
        $this->isRestoring = false;
        unset($this->restoreLogs, $this->stats);

        $this->dispatch('notify', type: 'info', message: __('restore.restore_started'));
    }

    public function updatedKnownSourceId(?int $value): void
    {
        if (! $value) return;
        $source = BackupSource::find($value);
        if (! $source) return;
        $cfg = $source->config ?? [];
        if (isset($cfg['mysql'])) {
            $this->remoteConfig['mysql'] = ['host' => $cfg['mysql']['host'] ?? '', 'port' => (string) ($cfg['mysql']['port'] ?? 3306), 'username' => $cfg['mysql']['username'] ?? '', 'password' => $cfg['mysql']['password'] ?? ''];
        }
        if (isset($cfg['mongodb'])) {
            $this->remoteConfig['mongodb'] = ['host' => $cfg['mongodb']['host'] ?? '', 'port' => (string) ($cfg['mongodb']['port'] ?? 27017), 'username' => $cfg['mongodb']['username'] ?? '', 'password' => $cfg['mongodb']['password'] ?? '', 'auth_database' => 'admin'];
        }
        if (isset($cfg['filesystem'])) {
            $ssh = $cfg['filesystem']['ssh'] ?? [];
            $this->remoteConfig['filesystem'] = ['ssh_host' => $ssh['host'] ?? '', 'ssh_port' => (string) ($ssh['port'] ?? 22), 'ssh_user' => $ssh['user'] ?? '', 'ssh_key_path' => $ssh['key_path'] ?? ''];
        }
    }

    public function updatedRestoreTarget(string $value): void
    {
        if ($value !== 'known_host') {
            $this->knownSourceId = null;
        }
    }

    // ── Helpers ───────────────────────────────────────────────

    public function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function formatDuration(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $secs    = $seconds % 60;
        return $minutes > 0 ? "{$minutes}m {$secs}s" : "{$secs}s";
    }

    public function render(): \Illuminate\View\View
    {
        $detailLog = $this->detailLogId
            ? BackupLog::with(['job.source', 'job.destination'])->find($this->detailLogId)
            : null;

        return view('livewire.backup.backup-job-show', [
            'backupSources' => BackupSource::where('is_active', true)->orderBy('name')->get(),
            'detailLog'     => $detailLog,
        ]);
    }
}
