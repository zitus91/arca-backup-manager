# Per-User Resource Ownership — Design

**Date:** 2026-07-15
**Status:** Approved (pending spec review)

## Goal

Give every backup resource an owner. Each authenticated user sees and manages
only the resources they created. No teams, no sharing, no read/write levels —
that scope was explicitly dropped.

## Context

Today the backup entities are global: any logged-in user sees and edits all
`BackupJob`, `BackupSource`, and `BackupStorageDestination` rows. There is no
`user_id` on them. `restore_logs` and `audit_logs` already carry `user_id`.
Users are provisioned externally (no in-app registration or user management);
the seeder creates a single admin.

## Design

### Ownership columns

Add a nullable `user_id` foreign key (`constrained()->nullOnDelete()`, indexed)
to:

- `backup_sources`
- `backup_storage_destinations`
- `backup_jobs`
- `backup_logs` — **denormalized** copy of the parent job's owner, set at
  creation time. This keeps log scoping a plain `where('user_id', …)` identical
  to the other tables, avoiding a `whereHas('job')` join in the dashboard's
  count/sum queries.

`restore_logs` and `audit_logs` keep their existing `user_id`.

### Backfill (in the same migration)

1. `user_id = <first user id>` on all existing `backup_sources`,
   `backup_storage_destinations`, `backup_jobs`.
2. `backup_logs.user_id` = the owner of its parent `backup_job` (run after jobs
   are backfilled).

Existing global data therefore lands with the seeded admin and stays visible to
them; other users start with an empty workspace.

### Enforcement — global scope active only when authenticated

A single `OwnedByUserScope` (Eloquent global scope) applied via an
`OwnedByUser` trait to: `BackupSource`, `BackupStorageDestination`,
`BackupJob`, `BackupLog`, `RestoreLog`, `AuditLog`.

Behavior:

- **Web/Livewire context** (`Auth::check()` true): adds
  `where('<table>.user_id', Auth::id())`. Every index, form, show page, log
  download, dashboard stat, and route-model-binding lookup is filtered in one
  place — no per-component `where()` to forget or get wrong.
- **Console + queue context** (`Auth::check()` false — scheduler,
  `backup:recover-stale-jobs`, `ProcessBackupJob`, `ProcessRestoreJob`): the
  scope is a no-op, so background work keeps seeing every user's rows. This is
  the critical gotcha a naive `Auth::id()` filter would break.

### Auto-assign owner on create

The `OwnedByUser` trait hooks the model `creating` event: if `Auth::check()`
and `user_id` is empty, set `user_id = Auth::id()`.

The scheduler creates the pending `BackupLog` in **console** context (no auth),
so the trait won't populate it — `routes/console.php` must set
`user_id = $job->user_id` explicitly when it creates the log. Same for any
`RestoreLog`/`BackupLog` rows created outside a web request.

### Show / download / route-model-binding

With the global scope active during a web request, route-model binding
(`BackupJob $job`, `BackupLog $log`) runs `findOrFail` through the scope, so a
non-owner automatically gets a 404 when guessing another user's ID. This covers
`BackupJobShow` and `BackupLogDownloadController` with no extra policy code. No
separate authorization layer is added.

## Non-goals / out of scope

- Teams, sharing, read vs. read-write permissions.
- In-app user management or registration.
- An admin "see everything" view — the admin is just another owner (they happen
  to own the backfilled legacy rows).

## Edge cases

- **New user, no resources:** sees an empty dashboard and empty indexes. Correct.
- **User deleted externally:** `nullOnDelete` orphans their rows (`user_id` null);
  the scope (`where user_id = current`) then hides them from everyone. Acceptable —
  orphaned rows are inert, not shown, not auto-deleted.
- **Broadcasting/echo:** dashboard listeners re-query through the scope during a
  web request, so realtime refreshes stay owner-scoped.

## Testing

- Unit: `OwnedByUserScope` filters when authenticated, no-ops in console.
- Feature: user A cannot see / show / download user B's job, source,
  destination, or log (404); a new user's dashboard is empty; the scheduler
  (console) still dispatches due jobs across all owners; creating a resource
  stamps the current user; the pending log created by the scheduler carries the
  job's owner.
- Migration: backfill assigns legacy rows to the first user and logs to their
  job's owner.
