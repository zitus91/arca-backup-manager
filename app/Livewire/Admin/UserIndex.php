<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class UserIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showForm = false;
    public ?int $editId = null;

    public function updatedSearch(): void
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

    #[On('user-saved')]
    public function onUserSaved(): void
    {
        $this->closeForm();
        session()->flash('message', __('users.saved'));
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('error', __('users.cannot_delete_self'));
            return;
        }

        $user = User::findOrFail($id);
        AuditLog::record('deleted', "Deleted user: {$user->email}", $user);
        $user->delete();
        session()->flash('message', __('users.deleted'));
    }

    public function render()
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        return view('livewire.admin.user-index', [
            'users' => $query->latest()->paginate(15),
        ]);
    }
}
