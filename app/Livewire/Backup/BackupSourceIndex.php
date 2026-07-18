<?php

namespace App\Livewire\Backup;

use App\Models\AuditLog;
use App\Models\BackupSource;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class BackupSourceIndex extends Component
{
    use WithPagination;

    public string $filterType = '';

    public string $filterStatus = '';

    public bool $showForm = false;

    public ?int $editId = null;

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
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

    #[On('source-saved')]
    public function onSourceSaved(): void
    {
        $this->closeForm();
        session()->flash('message', __('backup-source.saved'));
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
        $source = BackupSource::findOrFail($id);
        AuditLog::record('deleted', "Deleted backup source: {$source->name}", $source);
        $source->delete();
        session()->flash('message', __('backup-source.deleted'));
    }

    public function render()
    {
        $query = BackupSource::query()->with('host');

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === 'active');
        }

        $sources = $query->latest()->get();

        // Filter by type in PHP (config is encrypted, can't query SQL)
        if ($this->filterType) {
            $sources = $sources->filter(fn ($s) => $s->hasType($this->filterType));
        }

        return view('livewire.backup.backup-source-index', [
            'sources' => $sources,
        ]);
    }
}
