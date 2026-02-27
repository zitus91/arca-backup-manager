<?php

namespace App\Livewire\Backup;

use App\Jobs\Restore\ProcessRestoreJob;
use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\RestoreLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class RestoreIndex extends Component
{
    use WithPagination;

    // Filters
    public string $filterJobId = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';

    // Restore modal
    public bool $showRestoreModal = false;
    public ?int $selectedBackupLogId = null;
    public string $restoreType = 'full'; // full, db_only, files_only
    public array $selectedBackupInfo = [];

    // Detail modal
    public bool $showDetail = false;
    public ?int $detailRestoreLogId = null;

    // Confirmation
    public bool $showConfirmation = false;

    // Running state
    public bool $isRestoring = false;

    public function updatedFilterJobId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Open the restore modal for a specific backup log.
     */
    public function openRestoreModal(int $backupLogId): void
    {
        $log = BackupLog::with(['job.source', 'job.destination'])->find($backupLogId);

        if (! $log || $log->status !== 'success' || ! $log->storage_path) {
            $this->dispatch('notify', type: 'error', message: __('restore.backup_not_available'));
            return;
        }

        $this->selectedBackupLogId = $backupLogId;
        $this->restoreType = 'full';

        // Build info about what can be restored
        $sourceConfig = $log->job->source->config ?? [];
        $this->selectedBackupInfo = [
            'job_name' => $log->job->name,
            'source_name' => $log->job->source->name,
            'destination_name' => $log->job->destination->name,
            'file_name' => $log->file_name,
            'backup_date' => $log->started_at->format('d/m/Y H:i'),
            'file_size' => $log->formatted_size,
            'has_mysql' => isset($sourceConfig['mysql']),
            'has_mongodb' => isset($sourceConfig['mongodb']),
            'has_filesystem' => isset($sourceConfig['filesystem']),
            'mysql_databases' => isset($sourceConfig['mysql'])
                ? ($sourceConfig['mysql']['databases'] ?? (isset($sourceConfig['mysql']['database']) ? [$sourceConfig['mysql']['database']] : []))
                : [],
            'mongodb_databases' => isset($sourceConfig['mongodb'])
                ? ($sourceConfig['mongodb']['databases'] ?? (isset($sourceConfig['mongodb']['database']) ? [$sourceConfig['mongodb']['database']] : []))
                : [],
            'filesystem_paths' => isset($sourceConfig['filesystem'])
                ? ($sourceConfig['filesystem']['paths'] ?? (isset($sourceConfig['filesystem']['path']) ? [$sourceConfig['filesystem']['path']] : []))
                : [],
        ];

        // Determine available restore types
        $hasDb = $this->selectedBackupInfo['has_mysql'] || $this->selectedBackupInfo['has_mongodb'];
        $hasFs = $this->selectedBackupInfo['has_filesystem'];

        // If only one type, force that
        if ($hasDb && ! $hasFs) {
            $this->restoreType = 'db_only';
        } elseif (! $hasDb && $hasFs) {
            $this->restoreType = 'files_only';
        }

        $this->showRestoreModal = true;
        $this->showConfirmation = false;
    }

    /**
     * Close the restore modal.
     */
    public function closeRestoreModal(): void
    {
        $this->showRestoreModal = false;
        $this->selectedBackupLogId = null;
        $this->selectedBackupInfo = [];
        $this->showConfirmation = false;
    }

    /**
     * Show the confirmation step.
     */
    public function confirmRestore(): void
    {
        $this->showConfirmation = true;
    }

    /**
     * Execute the restore.
     */
    public function executeRestore(): void
    {
        if (! $this->selectedBackupLogId) {
            return;
        }

        $this->isRestoring = true;

        $restoreLog = RestoreLog::create([
            'backup_log_id' => $this->selectedBackupLogId,
            'user_id' => auth()->id(),
            'restore_type' => $this->restoreType,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        ProcessRestoreJob::dispatch($restoreLog->id);

        $this->closeRestoreModal();
        $this->isRestoring = false;

        $this->dispatch('notify', type: 'info', message: __('restore.restore_started'));
    }

    /**
     * Open restore log detail.
     */
    public function openDetail(int $restoreLogId): void
    {
        $this->detailRestoreLogId = $restoreLogId;
        $this->showDetail = true;
    }

    /**
     * Close restore log detail.
     */
    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailRestoreLogId = null;
    }

    #[On('echo:backup-jobs,.restore.completed')]
    public function onRestoreCompleted(): void
    {
        // Re-renders automatically
    }

    #[On('echo:backup-jobs,.restore.started')]
    public function onRestoreStarted(): void
    {
        // Re-renders automatically
    }

    public function render()
    {
        // Available backups (successful with storage_path)
        $backupsQuery = BackupLog::with(['job.source', 'job.destination'])
            ->where('status', 'success')
            ->whereNotNull('storage_path')
            ->latest('started_at');

        if ($this->filterJobId) {
            $backupsQuery->where('backup_job_id', (int) $this->filterJobId);
        }

        if ($this->filterDateFrom) {
            $backupsQuery->where('started_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $backupsQuery->where('started_at', '<=', $this->filterDateTo . ' 23:59:59');
        }

        // Restore history
        $restoreLogs = RestoreLog::with(['backupLog.job.source', 'backupLog.job.destination', 'user'])
            ->latest()
            ->limit(20)
            ->get();

        $detailLog = null;
        if ($this->detailRestoreLogId) {
            $detailLog = RestoreLog::with(['backupLog.job.source', 'backupLog.job.destination', 'user'])
                ->find($this->detailRestoreLogId);
        }

        return view('livewire.backup.restore-index', [
            'backups' => $backupsQuery->paginate(15),
            'jobs' => BackupJob::all(),
            'restoreLogs' => $restoreLogs,
            'detailLog' => $detailLog,
        ]);
    }
}
