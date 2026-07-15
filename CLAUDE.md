# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer dev                 # full dev stack: serve + queue worker + pail logs + vite + reverb (all required for real functionality)
composer test                # config:clear + php artisan test (Pest)
php artisan test --filter=BackupJobTest        # single test class
vendor/bin/pest tests/Feature/Backup/BackupJobTest.php   # single test file
vendor/bin/pint              # code style (Laravel Pint)
npm run build                # vite production build
composer setup               # first-time setup (install, .env, key, migrate, npm build)
```

Docker: `docker-compose.yml` (dev) / `docker-compose.prod.yml` + `scripts/deploy.sh` (prod). Containers: php-fpm, nginx, a dedicated `laravel-scheduler` (`schedule:work`), `laravel-worker` (`queue:work --timeout=3600`), and `reverb`.

## Stack

Laravel 12, Livewire 4 (no controllers except `BackupLogDownloadController`), Pest, Tailwind 4 + DaisyUI, SQLite by default, `database` queue driver, Laravel Reverb for WebSockets. UI strings are translated (`lang/` — it + en); never hardcode UI text in Blade/Livewire.

## Architecture

Self-hosted backup manager for MySQL, MongoDB, and filesystem sources. The whole pipeline is asynchronous:

1. **Scheduling** — `routes/console.php` runs every minute: finds due `BackupJob`s, creates a pending `BackupLog`, dispatches `ProcessBackupJob`, and immediately advances `next_run_at` via `BackupSchedulerService` (prevents double dispatch). A second schedule (`backup:recover-stale-jobs`, every 5 min) marks jobs stuck in running/pending as failed after worker crashes.
2. **Execution** — `App\Jobs\Backup\ProcessBackupJob` orchestrates the engine services in `app/Services/Backup/`: `MysqlBackupService` (mysqldump), `MongodbBackupService` (mongodump), `FilesystemBackupService` (tar/gzip/zip). These shell out via `Process`; `SshTunnelService` optionally wraps any source in an SSH tunnel. Output is uploaded through `S3StorageService` / `FtpStorageService` (local is direct). Retention pruning happens after each successful run.
3. **Incrementals** — each engine supports incremental dumps (MySQL via `information_schema` checkpoints, MongoDB via ObjectId timestamps, filesystem via `find -newer`); the full/incremental parent-child chain is tracked in `backup_logs` and resolved in order at restore time.
4. **Restore** — `App\Jobs\Restore\ProcessRestoreJob` + `app/Services/Restore/*`. Non-destructive by default (restores under `_restored_<timestamp>` names); override/drop mode requires explicit confirmation in the UI.
5. **Real-time UI** — jobs broadcast `BackupJobStarted/Completed` and `RestoreJobStarted/Completed` events over Reverb; Livewire components in `app/Livewire/Backup/` (dashboard, job/source/destination CRUD, logs, restore) listen via Echo. Channels in `routes/channels.php`.

Cross-cutting details:

- **Secrets at rest**: `BackupSource.config` and `BackupStorageDestination.config` use the `encrypted:array` cast. Keep any new credential-bearing fields encrypted the same way.
- **Audit**: user-facing actions write `AuditLog` rows — follow this pattern for new mutating features.
- **Jobs use `tries=1`** and long timeouts by design (a retried backup would duplicate work); the stale-job recovery command is the safety net, not retries.

## Testing notes

Feature tests live in `tests/Feature/Backup/` and cover Livewire components and the job pipeline (shell commands are faked with `Process::fake()`); unit tests in `tests/Unit/Backup/` cover scheduler math and dump command building. Follow this split for new work.
