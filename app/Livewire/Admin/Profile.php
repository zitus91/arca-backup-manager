<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $locale = '';

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public bool $profileSaved = false;

    public bool $passwordChanged = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->locale = $user->locale ?? app()->getLocale();
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.Auth::id(),
            'locale' => 'required|in:it,en',
        ]);

        $user = Auth::user();
        $oldValues = ['name' => $user->name, 'email' => $user->email, 'locale' => $user->locale];

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
        ]);

        session()->put('locale', $this->locale);
        app()->setLocale($this->locale);

        AuditLog::record('updated', 'Updated own profile', $user, $oldValues, [
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->profileSaved = true;
        $this->dispatch('profile-updated');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', __('users.current_password_wrong'));

            return;
        }

        $user->update([
            'password' => bcrypt($this->new_password),
        ]);

        AuditLog::record('updated', 'Changed own password', $user);

        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';
        $this->passwordChanged = true;
    }

    public function render()
    {
        return view('livewire.admin.profile');
    }
}
