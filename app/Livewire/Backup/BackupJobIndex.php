<?php

namespace App\Livewire\Backup;

use App\Jobs\Backup\ProcessBackupJob;
use App\Models\AuditLog;
use App\Models\BackupJob;
use App\Models\BackupLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class BackupJobIndex extends Component
{
    use WithPagination;

    public string $filterStatus = '';
    public string $filterScheduleType = '';

    public bool $showForm = false;
    public ?int $editId = null;

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterScheduleType(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editId = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->editId = $id;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editId = null;
    }

    public function runNow(int $id): void
    {
        $job = BackupJob::with(['source', 'destination'])->findOrFail($id);

        $log = BackupLog::create([
            'backup_job_id' => $job->id,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        ProcessBackupJob::dispatch($job->id, $log->id);

        AuditLog::record('run', "Manually started backup job: {$job->name}", $job);

        session()->flash('message', __('backup-job.dispatched'));
    }

    public ?int $confirmingDeleteId = null;

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function deleteConfirmed(): void
    {
        if ($this->confirmingDeleteId === null) {
            return;
        }
        $this->delete($this->confirmingDeleteId);
        $this->confirmingDeleteId = null;
    }

    public function delete(int $id): void
    {
        $job = BackupJob::findOrFail($id);
        AuditLog::record('deleted', "Deleted backup job: {$job->name}", $job);
        $job->delete();
        session()->flash('message', __('backup-job.deleted'));
    }

    #[On('job-saved')]
    public function onJobSaved(): void
    {
        $this->closeForm();
        session()->flash('message', __('backup-job.saved'));
    }

    #[On('echo:backup-jobs,.backup.started')]
    public function onBackupStarted(): void
    {
        // Re-renders automatically to show running status
    }

    #[On('echo:backup-jobs,.backup.completed')]
    public function onBackupCompleted(): void
    {
        // Re-renders automatically to show updated status
    }

    public function render()
    {
        $query = BackupJob::with(['source', 'destination', 'latestLog']);

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === 'active');
        }

        if ($this->filterScheduleType) {
            $query->ofScheduleType($this->filterScheduleType);
        }

        return view('livewire.backup.backup-job-index', [
            'jobs' => $query->latest()->paginate(10),
        ]);
    }
}
