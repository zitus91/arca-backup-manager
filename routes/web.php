<?php

use App\Http\Controllers\Backup\BackupLogDownloadController;
use App\Livewire\Admin\Profile;
use App\Livewire\Admin\SystemDashboard;
use App\Livewire\Admin\UserIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Backup\AuditLogIndex;
use App\Livewire\Backup\BackupJobIndex;
use App\Livewire\Backup\BackupJobShow;
use App\Livewire\Backup\BackupLogIndex;
use App\Livewire\Backup\BackupSourceIndex;
use App\Livewire\Backup\Dashboard;
use App\Livewire\Backup\RestoreIndex;
use App\Livewire\Backup\StorageDestinationIndex;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.backup.dashboard');
});

// Authentication
Route::get('/login', Login::class)->middleware('guest')->name('login');

Route::post('/logout', function () {
    AuditLog::record('logout', 'User logged out: '.auth()->user()->email);
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Admin Backup Manager routes (protected)
Route::prefix('admin/backup')->name('admin.backup.')->middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/jobs', BackupJobIndex::class)->name('jobs');
    Route::get('/jobs/{job}', BackupJobShow::class)->name('jobs.show');
    Route::get('/sources', BackupSourceIndex::class)->name('sources');
    Route::get('/destinations', StorageDestinationIndex::class)->name('destinations');
    Route::get('/logs', BackupLogIndex::class)->name('logs');
    Route::get('/logs/{log}/download', BackupLogDownloadController::class)->name('logs.download');
    Route::get('/restore', RestoreIndex::class)->name('restore');
    Route::get('/audit', AuditLogIndex::class)->name('audit');
    Route::get('/profile', Profile::class)->name('profile');

    Route::post('/locale', function () {
        $locale = request('locale');
        if (in_array($locale, ['it', 'en'], true)) {
            session()->put('locale', $locale);
            auth()->user()->update(['locale' => $locale]);
        }

        return back();
    })->name('locale');

    // Admin-only
    Route::middleware('admin')->group(function () {
        Route::get('/system', SystemDashboard::class)->name('system');
        Route::get('/users', UserIndex::class)->name('users');
    });
});
