<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class UserForm extends Component
{
    public ?int $userId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(?int $userId = null): void
    {
        if ($userId) {
            $this->userId = $userId;
            $user = User::findOrFail($userId);
            $this->name = $user->name;
            $this->email = $user->email;
        }
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
        ];

        if ($this->userId) {
            // Password optional on edit
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            // Password required on create
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $oldValues = $user->only(['name', 'email']);
            $user->update($data);
            AuditLog::record('updated', "Updated user: {$user->email}", $user, $oldValues, $data);
        } else {
            $user = User::create($data);
            AuditLog::record('created', "Created user: {$user->email}", $user, null, ['name' => $data['name'], 'email' => $data['email']]);
        }

        $this->dispatch('user-saved');
    }

    public function render()
    {
        return view('livewire.admin.user-form');
    }
}
