<?php

namespace App\Livewire\Backup;

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class AuditLogIndex extends Component
{
    use WithPagination;

    public string $filterAction = '';
    public string $filterUserId = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';

    public function updatedFilterAction(): void
    {
        $this->resetPage();
    }

    public function updatedFilterUserId(): void
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

    public function render()
    {
        $query = AuditLog::with('user')->latest('created_at');

        if ($this->filterAction) {
            $query->ofAction($this->filterAction);
        }

        if ($this->filterUserId) {
            $query->where('user_id', (int) $this->filterUserId);
        }

        if ($this->filterDateFrom) {
            $query->where('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->where('created_at', '<=', $this->filterDateTo . ' 23:59:59');
        }

        return view('livewire.backup.audit-log-index', [
            'logs' => $query->paginate(25),
            'users' => User::all(),
            'actions' => AuditLog::select('action')->distinct()->pluck('action'),
        ]);
    }
}
