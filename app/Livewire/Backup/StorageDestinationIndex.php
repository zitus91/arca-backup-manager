<?php

namespace App\Livewire\Backup;

use App\Models\AuditLog;
use App\Models\BackupStorageDestination;
use App\Services\Backup\FtpStorageService;
use App\Services\Backup\S3StorageService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class StorageDestinationIndex extends Component
{
    use WithPagination;
    public string $filterType = '';
    public string $filterStatus = '';
    public ?int $testingId = null;
    public ?string $testResult = null;

    public bool $showForm = false;
    public ?int $editId = null;

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

    #[On('destination-saved')]
    public function onDestinationSaved(): void
    {
        $this->closeForm();
        session()->flash('message', __('backup-storage-destination.saved'));
    }

    public function testConnection(int $id): void
    {
        $this->testingId = $id;
        $this->testResult = null;

        $destination = BackupStorageDestination::findOrFail($id);
        $config = $destination->config;

        try {
            $success = match ($destination->type) {
                's3' => app(S3StorageService::class)->testConnection($config),
                'ftp' => app(FtpStorageService::class)->testConnection($config),
                'local' => is_dir($config['path'] ?? '') && is_writable($config['path'] ?? ''),
                default => false,
            };

            $this->testResult = $success ? 'success' : 'failed';
        } catch (\Throwable $e) {
            $this->testResult = 'failed';
        }

        $this->testingId = null;
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
        $dest = BackupStorageDestination::findOrFail($id);
        AuditLog::record('deleted', "Deleted storage destination: {$dest->name}", $dest);
        $dest->delete();
        session()->flash('message', __('backup-storage-destination.deleted'));
    }

    public function render()
    {
        $query = BackupStorageDestination::query();

        if ($this->filterType !== '') {
            $query->ofType($this->filterType);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === 'active');
        }

        return view('livewire.backup.storage-destination-index', [
            'destinations' => $query->latest()->paginate(10),
        ]);
    }
}
