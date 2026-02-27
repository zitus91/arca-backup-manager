<?php

use App\Http\Controllers\Backup\BackupLogDownloadController;
use App\Livewire\Auth\Login;
use App\Livewire\Backup\BackupJobIndex;
use App\Livewire\Backup\BackupLogIndex;
use App\Livewire\Backup\BackupSourceIndex;
use App\Livewire\Backup\Dashboard;
use App\Livewire\Backup\RestoreIndex;
use App\Livewire\Backup\StorageDestinationIndex;
use App\Livewire\Backup\AuditLogIndex;
use App\Livewire\Admin\UserIndex;
use App\Livewire\Admin\Profile;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// === DEBUG: rotta diagnostica (rimuovere dopo fix) ===
Route::get('/debug-login', function () {
    return response()->json([
        'app_env' => config('app.env'),
        'app_url' => config('app.url'),
        'session_driver' => config('session.driver'),
        'session_domain' => config('session.domain'),
        'session_secure' => config('session.secure'),
        'session_same_site' => config('session.same_site'),
        'session_cookie' => config('session.cookie'),
        'request_url' => request()->url(),
        'request_secure' => request()->isSecure(),
        'request_ip' => request()->ip(),
        'request_scheme' => request()->getScheme(),
        'trusted_proxies' => request()->getTrustedProxies(),
        'x_forwarded_proto' => request()->header('X-Forwarded-Proto'),
        'x_forwarded_for' => request()->header('X-Forwarded-For'),
        'user_count' => \App\Models\User::count(),
        'sessions_table_exists' => \Illuminate\Support\Facades\Schema::hasTable('sessions'),
        'auth_check' => auth()->check(),
        'php_version' => PHP_VERSION,
    ]);
});

// === DEBUG: test login diretto senza Livewire (rimuovere dopo fix) ===
Route::get('/debug-test-login', function () {
    $email = 'admin@backup.local';
    $password = 'password';
    $results = [];

    // 1. Verifica utente
    $user = \App\Models\User::where('email', $email)->first();
    $results['user_found'] = (bool) $user;
    if ($user) {
        $results['user_id'] = $user->id;
        $results['password_hash_length'] = strlen($user->password);
        $results['password_hash_starts'] = substr($user->password, 0, 20);
        $results['hash_check'] = \Illuminate\Support\Facades\Hash::check($password, $user->password);
        $results['hash_info'] = password_get_info($user->password);
    }

    // 2. Prova Auth::attempt
    $results['auth_attempt'] = Auth::attempt(['email' => $email, 'password' => $password]);
    $results['auth_check_after'] = Auth::check();
    $results['auth_id_after'] = Auth::id();
    $results['session_id'] = session()->getId();

    // 3. Logout per non sporcare
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    // 4. Info livewire
    $results['livewire_js_exists'] = file_exists(public_path('vendor/livewire/livewire.js'));
    $results['livewire_config'] = config('livewire.asset_url');
    $results['vite_manifest_exists'] = file_exists(public_path('build/manifest.json'));
    $results['hot_file_exists'] = file_exists(public_path('hot'));

    return response()->json($results);
});

Route::get('/', function () {
    return redirect()->route('admin.backup.dashboard');
});

// Authentication
Route::get('/login', Login::class)->middleware('guest')->name('login');

Route::post('/logout', function () {
    AuditLog::record('logout', 'User logged out: ' . auth()->user()->email);
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Admin Backup Manager routes (protected)
Route::prefix('admin/backup')->name('admin.backup.')->middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/jobs', BackupJobIndex::class)->name('jobs');
    Route::get('/sources', BackupSourceIndex::class)->name('sources');
    Route::get('/destinations', StorageDestinationIndex::class)->name('destinations');
    Route::get('/logs', BackupLogIndex::class)->name('logs');
    Route::get('/logs/{log}/download', BackupLogDownloadController::class)->name('logs.download');
    Route::get('/restore', RestoreIndex::class)->name('restore');
    Route::get('/audit', AuditLogIndex::class)->name('audit');
    Route::get('/users', UserIndex::class)->name('users');
    Route::get('/profile', Profile::class)->name('profile');
});
