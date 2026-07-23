<?php

namespace App\Livewire\Backup;

use App\Models\AuditLog;
use App\Models\BackupHost;
use App\Models\BackupSource;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class BackupHostIndex extends Component
{
    public string $filterStatus = '';

    public bool $showForm = false;

    public ?int $editId = null;

    public ?int $confirmingDeleteId = null;

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

    #[On('host-saved')]
    public function onHostSaved(): void
    {
        $this->closeForm();
        session()->flash('message', __('backup-host.saved'));
    }

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
        $host = BackupHost::findOrFail($id);
        AuditLog::record('deleted', "Deleted backup host: {$host->name}", $host);
        $host->delete();
        session()->flash('message', __('backup-host.deleted'));
    }

    public function render()
    {
        $query = BackupHost::query();

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === 'active');
        }

        $hosts = $query->latest()->get();

        $sourceCounts = BackupSource::query()
            ->selectRaw('mysql_host_id, postgres_host_id, mongodb_host_id, filesystem_host_id')
            ->get()
            ->flatMap(fn ($s) => array_filter([$s->mysql_host_id, $s->postgres_host_id, $s->mongodb_host_id, $s->filesystem_host_id]))
            ->countBy();

        $hosts->each(fn ($h) => $h->usage_count = (int) ($sourceCounts[$h->id] ?? 0));

        return view('livewire.backup.backup-host-index', [
            'hosts' => $hosts,
        ]);
    }
}
