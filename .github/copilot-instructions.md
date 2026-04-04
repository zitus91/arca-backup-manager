---
applyTo: '**'
---

# Backup Manager — Project Guidelines

Laravel 12 web app for scheduling, monitoring, and restoring backups of MySQL, MongoDB, and filesystem sources. Real-time UI via Livewire 4 + Laravel Reverb WebSocket. See [README.md](../README.md) for full feature overview.

---

## Build and Test

```bash
# One-shot setup (first time)
composer setup              # install deps, create .env, migrate, build

# Development
composer dev                # starts PHP serve, queue worker, Pail logs, Vite, Reverb concurrently

# Individual
npm run dev                 # Vite dev server
php artisan queue:work      # process async backup/restore jobs
php artisan reverb:start    # WebSocket server

# Testing
composer test               # clears config cache, then php artisan test (Pest 3.8)
```

Test environment uses SQLite in-memory, array cache/session/queue. Feature tests use `RefreshDatabase`.

---

## Architecture

```
Services/Backup/     # MysqlBackupService, MongodbBackupService, FilesystemBackupService
                     # S3StorageService, FtpStorageService, BackupSchedulerService, SshTunnelService
Services/Restore/    # MysqlRestoreService, MongodbRestoreService, FilesystemRestoreService
Jobs/Backup/         # ProcessBackupJob (ShouldQueue)
Jobs/Restore/        # ProcessRestoreJob (ShouldQueue)
Events/Backup/       # BackupJobStarted, BackupJobCompleted — broadcast via Reverb
Livewire/Backup/     # Dashboard, BackupJobForm/Index, BackupLogIndex, StorageDestinationForm/Index
                     # BackupSourceForm/Index, RestoreIndex, AuditLogIndex
Livewire/Admin/      # UserForm, UserIndex, Profile
Models/              # BackupJob, BackupLog, BackupSource, BackupStorageDestination, RestoreLog, AuditLog, User
Trait/HasCache.php   # caching(), forgetCache(), clearModelCache() — used by all models
```

**Request flow**: Livewire action → dispatches queued `ProcessBackupJob`/`ProcessRestoreJob` → service layer → broadcasts events → Livewire `Dashboard` listens with `#[On('echo:...')]`.

**Asset structure** — files grouped by functional area:

```
app/Livewire/{Area}/ComponentName.php
resources/views/livewire/{area}/component-name.blade.php
resources/js/{area}/component-name.js
resources/css/{area}/component-name.css
lang/en/{area-component}.php
lang/it/{area-component}.php
```

---

## Project-Specific Conventions

**Sensitive model fields** use `encrypted:array` cast (`BackupSource.config`, `BackupStorageDestination.config`, `RestoreLog.remote_host_config`). Never log or expose these raw.

**Audit logging**: Use `AuditLog::record()` for all user-initiated actions. All model mutations that touch backup/restore/user entities should be audited.

**Scheduling**: `BackupSchedulerService` handles cron calculations. Schedule types: `manual`, `hourly`, `daily`, `weekly`, `monthly`, `cron` (custom expression).

**Restore safety**: Restores are non-destructive by default (appends `_restored_<timestamp>`). Override mode requires explicit user confirmation via multi-step disclaimer. Preserve this safety pattern.

**Routes**: All backup management routes are under the `/admin/backup/` prefix with auth middleware. See [routes/web.php](../routes/web.php).

**Testing**: Write feature tests in `tests/Feature/Backup/`. Use Pest's `#[Test]` attribute syntax. Mock services, not models. Queue jobs with `Queue::fake()`, events with `Event::fake()`.

---

## Coding Standards

### Livewire First

- Use **Livewire native methods** for all interactivity, state management, and real-time updates.
- **Avoid Alpine.js** unless strictly necessary for purely decorative/visual behavior.
- Use **Reverb** for real-time features: `wire:stream`, `wire:poll`, `dispatch()`, `#[On]` listeners.
- Use `wire:lazy` for heavy components loaded on-demand.
- Use `wire:model.live` only when immediate reactivity is required (default is lazy).
- Use `#[Computed]` for all derived/calculated properties — they are cached automatically. Always add a page loading state where computed props are used.
- Use `rules()` in Livewire components for validation. Never hardcode validation in `render()`.
- Use `#[Layout]`, `#[Computed]`, `#[On]` from `Livewire\Attributes`.
- Notify users via custom toast/modal alerts — never use native browser `alert()`.
- No hover movement animations or box transform effects in the UI.

### UI and Styling

- Use **TailwindCSS + DaisyUI** for all styling. Avoid custom CSS unless strictly necessary.
- Reusable Blade components go in `resources/views/components/` (navbar, footer, sidebar, etc.).
- Layout entry point: `resources/views/layouts/app.blade.php` — wrap pages with `<x-layout>` or `<x-app-layout>`.
- JS and CSS files must be **scoped to the component** that needs them — do not load unnecessary assets globally.
- Follow PSR-12 for all PHP code. Validate generated files with `php -l file.php` before confirming.
- Ensure accessibility: use `aria-*` attributes and semantic roles.

### i18n

Every component must have dedicated PHP language files. No hardcoded strings in Blade or PHP files.

```
lang/en/backup-job-form.php
lang/it/backup-job-form.php
```

```php
// lang/en/backup-job-form.php
return ['title' => 'Create Backup Job', 'save' => 'Save'];

// lang/it/backup-job-form.php
return ['title' => 'Crea Backup Job', 'save' => 'Salva'];
```

```blade
<h2>{{ __('backup-job-form.title') }}</h2>
```

---

## Form Standards (mandatory — never deviate)

### Rule 1 — Input structure

Every input field must follow this exact structure:

```blade
<div class="form-control">
    <label class="label">
        <span class="label-text">Field Name *</span>
    </label>
    <input
        type="text"
        wire:model="fieldName"
        class="input input-bordered @error('fieldName') input-error @enderror"
    />
    @error('fieldName')
        <label class="label">
            <span class="label-text-alt text-error">{{ $message }}</span>
        </label>
    @enderror
</div>
```

Order: Container → Label (top) → Input → Error message (bottom).

### Rule 2 — Never use plain `<textarea>`

Always use the Quill component instead:

```blade
<div class="form-control">
    <label class="label">
        <span class="label-text">Description *</span>
    </label>
    <x-admin.partials.quill
        wire:model="description"
        :value="$description"
        placeholder="Write here..."
        toolbar="basic"
    />
    @error('description')
        <label class="label">
            <span class="label-text-alt text-error">{{ $message }}</span>
        </label>
    @enderror
</div>
```

Toolbar options: `basic` (simple text), `full` (rich content like articles).

---

## Cache

All Eloquent models must use the `HasCache` trait (`app/Trait/HasCache.php`). Never use `Cache::remember()` manually — always use trait methods.

Requires a cache driver with tag support (Redis or Memcached in production).

**Available methods:**

```php
// Simple queries
$job  = BackupJob::cachingById(1);
$jobs = BackupJob::cachingWhere('status', 'active');
$last = BackupJob::cachingLast('created_at', 'desc');

// With relations and conditions
$jobs = BackupJob::caching([
    'where'   => [['status', 'active'], ['retention', 7, '>=']],
    'whereIn' => ['source_id' => [1, 2, 3]],
    'with'    => ['source', 'storageDestination'],
    'orderBy' => ['created_at' => 'desc'],
    'limit'   => 10,
]);

// Cached paginated results
$logs = BackupLog::caching([
    'where'    => [['backup_job_id', $this->jobId]],
    'orderBy'  => ['created_at' => 'desc'],
    'paginate' => ['perPage' => 15, 'route' => 'backup.logs'],
]);

// Cached relation on instance
$logs = $backupJob->cachingRelation('backupLogs', [
    'where'   => [['status', 'failed']],
    'orderBy' => ['created_at' => 'desc'],
]);
```

Cache is invalidated automatically on `saved` and `deleted` model events.

**Livewire example with computed + cache:**

```php
namespace App\Livewire\Backup;

use Livewire\Component;
use App\Models\BackupLog;
use Livewire\Attributes\Computed;

class BackupLogIndex extends Component
{
    public int $jobId;

    #[Computed]
    public function logs()
    {
        return BackupLog::caching([
            'where'    => [['backup_job_id', $this->jobId]],
            'orderBy'  => ['created_at' => 'desc'],
            'paginate' => ['perPage' => 20],
        ]);
    }

    public function render()
    {
        return view('livewire.backup.backup-log-index');
    }
}
```

---

## Output Checklist

Every generated component must include:

1. **Livewire PHP component** (in the correct functional area folder)
2. **Blade view** in the matching area
3. **Dedicated JS and/or CSS** files for the same area
4. **Language files** for `en` and `it`
5. **Cache** via `HasCache` methods where applicable
6. **TailwindCSS + DaisyUI** UI — no custom CSS unless strictly necessary
7. No custom JS if Livewire can handle the same logic
8. No hover movement/transform animations on boxes or cards

