<?php

namespace App\Livewire\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            // === DEBUG: Stato ambiente ===
            Log::debug('[Login][DEBUG] Ambiente', [
                'app_env' => config('app.env'),
                'app_url' => config('app.url'),
                'session_driver' => config('session.driver'),
                'session_domain' => config('session.domain'),
                'session_secure' => config('session.secure'),
                'session_same_site' => config('session.same_site'),
                'request_url' => request()->url(),
                'request_secure' => request()->isSecure(),
            ]);

            // === DEBUG: Ricerca utente ===
            $user = User::where('email', $this->email)->first();

            if (! $user) {
                Log::warning('[Login] Utente non trovato nel DB', ['email' => $this->email]);
                $this->errorMessage = __('auth.failed') . ' (utente non trovato)';
                $this->addError('email', __('auth.failed'));
                return;
            }

            // === DEBUG: Verifica password ===
            $passwordCheck = Hash::check($this->password, $user->password);
            Log::debug('[Login][DEBUG] Verifica password', [
                'email' => $this->email,
                'user_id' => $user->id,
                'password_hash_starts' => substr($user->password, 0, 20) . '...',
                'password_hash_length' => strlen($user->password),
                'hash_check_result' => $passwordCheck,
                'hash_info' => password_get_info($user->password),
            ]);

            if (! $passwordCheck) {
                Log::warning('[Login] Hash::check fallito — password errata o doppio hash', ['email' => $this->email]);
                $this->errorMessage = __('auth.failed') . ' (password non corrisponde)';
                $this->addError('email', __('auth.failed'));
                return;
            }

            // === DEBUG: Auth::attempt ===
            $attemptResult = Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember);
            Log::debug('[Login][DEBUG] Auth::attempt', [
                'result' => $attemptResult,
                'guard' => config('auth.defaults.guard'),
                'provider' => config('auth.defaults.provider', config('auth.guards.web.provider')),
            ]);

            if (! $attemptResult) {
                Log::warning('[Login] Auth::attempt fallito nonostante Hash::check OK', ['email' => $this->email]);
                $this->errorMessage = __('auth.failed') . ' (auth attempt fallito)';
                $this->addError('email', __('auth.failed'));
                return;
            }

            session()->regenerate();

            // === DEBUG: Post-login ===
            Log::debug('[Login][DEBUG] Post-login', [
                'auth_check' => Auth::check(),
                'auth_id' => Auth::id(),
                'session_id' => session()->getId(),
                'intended_url' => session()->get('url.intended', route('admin.backup.dashboard')),
            ]);

            AuditLog::record('login', 'User logged in: ' . $this->email);

            Log::info('[Login] Login riuscito', ['email' => $this->email]);

            $this->redirectIntended(route('admin.backup.dashboard'), navigate: false);
        } catch (\Throwable $e) {
            Log::error('[Login] Eccezione durante il login', [
                'email' => $this->email,
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
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
