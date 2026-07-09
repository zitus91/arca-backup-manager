Project: self-hosted backup manager (MySQL/Mongo/FS sources -> Local/S3/FTP dests). Key flows: Livewire UI -> create BackupJob/Log -> dispatch queued Process*Job -> services execute CLIs + upload + retention + events via Reverb -> dashboard reactive.
Core models (encrypted config): BackupSource, BackupStorageDestination, RestoreLog (remote_host_config).
Jobs orchestrate: incremental/full, chains, notifications, stale recovery (artisan cmd).
Services split: *BackupService (dump/buildCommand with escapeshellarg + Process), *RestoreService (similar + override/drop), *StorageService (stream), SshTunnelService.
UI: many Livewire (Dashboard, *Index/*Form, RestoreIndex, Audit), Blade layouts.
Routes: all /admin/backup/* under auth; login guest only. No public API.
AuditLog: append-only record of actions (IP/UA/old/new).
Invariants: creds never plaintext in DB; restores non-destructive by default; streaming for large files; retention auto-prunes.
Entry points: artisan, composer setup/dev/test, docker-compose (includes debug sqlite-web).