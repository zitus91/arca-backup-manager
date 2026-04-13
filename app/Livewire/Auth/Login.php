<?php

namespace App\Livewire\Auth;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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

        $throttleKey = 'login:' . str()->lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]));
            return;
        }

        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            RateLimiter::hit($throttleKey, 60);
            throw $e;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);

            Log::warning('[Login] Failed attempt', ['email' => $this->email, 'ip' => request()->ip()]);

            $this->addError('email', __('auth.failed'));
            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        AuditLog::record('login', 'User logged in: ' . $this->email);

        Log::info('[Login] Successful login', ['email' => $this->email]);

        $this->redirectIntended(route('admin.backup.dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

