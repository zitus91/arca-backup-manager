<?php

namespace App\Livewire\Backup;

use App\Models\BackupJob;
use App\Models\BackupLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class BackupLogIndex extends Component
{
    use WithPagination;

    #[Url(as: 'filterJobId')]
    public string $filterJobId = '';
    public string $filterStatus = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';

    public bool $showDetail = false;
    public ?int $detailLogId = null;

    public function updatedFilterJobId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
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

    public function openDetail(int $logId): void
    {
        $this->detailLogId = $logId;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailLogId = null;
    }

    #[On('echo:backup-jobs,.backup.completed')]
    public function onBackupCompleted(): void
    {
        // Re-renders automatically
    }

    #[On('echo:backup-jobs,.backup.started')]
    public function onBackupStarted(): void
    {
        // Re-renders automatically
    }

    public function render()
    {
        $query = BackupLog::with(['job.source', 'job.destination'])->latest('started_at');

        if ($this->filterJobId) {
            $query->ofJob((int) $this->filterJobId);
        }

        if ($this->filterStatus) {
            $query->ofStatus($this->filterStatus);
        }

        if ($this->filterDateFrom) {
            $query->where('started_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->where('started_at', '<=', $this->filterDateTo . ' 23:59:59');
        }

        $detailLog = null;
        if ($this->detailLogId) {
            $detailLog = BackupLog::with(['job.source', 'job.destination'])->find($this->detailLogId);
        }

        return view('livewire.backup.backup-log-index', [
            'logs' => $query->paginate(25),
            'jobs' => BackupJob::all(),
            'detailLog' => $detailLog,
        ]);
    }
}
