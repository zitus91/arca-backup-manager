<?php

namespace App\Livewire\Auth;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|min:6')]
    public string $password = '';

    public bool $remember = false;

    public string $errorMessage = '';

    public function login(): void
    {
        $this->errorMessage = '';

        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[Login] Validazione fallita', [
                'email' => $this->email,
                'errors' => $e->errors(),
            ]);
            throw $e;
        }

        try {
            $userExists = \App\Models\User::where('email', $this->email)->exists();

            if (! $userExists) {
                Log::warning('[Login] Utente non trovato', ['email' => $this->email]);
                $this->errorMessage = __('auth.failed');
                $this->addError('email', __('auth.failed'));
                return;
            }

            if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
                Log::warning('[Login] Password errata', ['email' => $this->email]);
                $this->errorMessage = __('auth.failed');
                $this->addError('email', __('auth.failed'));
                return;
            }

            session()->regenerate();

            AuditLog::record('login', 'User logged in: ' . $this->email);

            Log::info('[Login] Login riuscito', ['email' => $this->email]);

            $this->redirectIntended(route('admin.backup.dashboard'), navigate: true);
        } catch (\Throwable $e) {
            Log::error('[Login] Eccezione durante il login', [
                'email' => $this->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->errorMessage = 'Errore durante il login: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
