# WORKFLOW_STATE

## Project Identity
- Project name: backup-manager
- Repository: git@github.com:zitus91/backup-manager.git (worktree: personal-backup-manager/backup-manager)
- Branch: main
- App type: Laravel app (self-hosted backup manager with Livewire admin dashboard)
- Primary stack: Laravel 12 + Livewire 4
- Secondary stack: Laravel Reverb (WebSockets), queued jobs (database/redis), Pest tests
- Styling stack: Tailwind CSS 4 + DaisyUI 5
- Runtime(s): PHP 8.2+ (current 8.4), Artisan, Queue workers, Scheduler
- Package manager(s): Composer, npm
- Database: SQLite (default/dev) or MySQL; MongoDB support for sources
- Cache / queue: Laravel cache + jobs (database driver default)
- Realtime: Laravel Reverb + Echo
- Test stack: Pest 3 + pest-plugin-laravel
- Deployment target: Docker Compose (php-fpm + nginx + redis + worker + scheduler + Reverb)

## Current Task
- Request: rifai l'analisi, trova i bug e migliora la grafica con audit di impeccable
- Business goal: Redo full project analysis. Discover bugs (functional, logical, UX, code quality) in addition to vulnerabilities. Perform impeccable design system audit on the UI and improve graphics/components following strict high-end standards (OKLCH, anti-slop, hierarchy, deliberate design).
- User-visible outcome: Updated analysis report with bugs list. Concrete UI improvements applied to dashboard and key screens per impeccable rules. Better visual quality, consistency, and reduced generic/AI-pattern feel.
- Priority: High
- Deadline / urgency: Active task

## Clarifications
- Confirmed scope: Full project study (code, deps, config, Docker, CLI execution patterns, auth, file handling). Focus on vulnerabilities + practical recommendations. Axios usage clarification included.
- Out of scope: Immediate full implementation of every recommendation (unless user requests follow-up tasks); production deployment changes without review.
- Open questions: Which recommendations should be implemented first? Any specific environment constraints (e.g. must keep certain binaries)?
- Assumptions: Project is self-hosted/personal use. Admin users are trusted. External CLIs (mysqldump, rsync, sshpass, etc.) are required for functionality.
- Constraints: Must preserve existing backup/restore functionality. Prefer safe patterns (Process facade + escaping already used in many places).

## Stack Detection
- Framework / version: Laravel 12
- Build tool: Vite 7 + npm
- Routing style: Laravel routes + Livewire components (no traditional controllers for most UI)
- State management: Livewire properties + events (Reverb for realtime)
- Form / validation approach: Livewire component properties + rules() methods + server-side validation
- API layer: None (internal Livewire + queued jobs)
- Auth pattern: Laravel session auth + Livewire (guest middleware on login, auth on admin routes). Rate limiting on login.
- i18n pattern: Laravel lang files (en/it) scoped per component
- UI component library: Livewire 4 + Blade + Tailwind 4 + DaisyUI 5
- Existing architectural conventions: Services for backup/restore logic, Jobs for orchestration, encrypted casts on sensitive model configs, streaming for large files, audit logging on actions.

## Acceptance Criteria
- [ ] Full project re-analysis completed (code, jobs, services, config, UI)
- [ ] Bugs identified and documented (functional, logical, UX, quality) beyond security vulns
- [ ] Dependency vulnerabilities re-audited and listed
- [ ] Impeccable audit performed: current UI evaluated against OKLCH, anti-slop, hierarchy, layout bans
- [ ] Graphics/UI improved on key screens (dashboard + at least 2-3 others) following impeccable rules
- [ ] Changes respect Livewire + DaisyUI + Tailwind 4, use deliberate color strategy
- [ ] WORKFLOW_STATE.md updated with findings and UI notes
- [ ] Tests/lint pass after changes

## Routing Matrix
- Backend owner: laravel-expert
- Frontend owner: n/a (primarily Livewire/Blade)
- UI owner: frontend-styling-expert (primary for impeccable audit + graphics improvements)
- Database owner: db-expert (n/a)
- DevOps owner: devops (Docker, deps)
- Security owner: security-reviewer
- Testing owner: tester
- Review owner: reviewer
- Lint owner: linter
- Commit owner: commit-message
- SEO owner: n/a

## Current Status
- Status: Analysis + UI audit in progress
- Current phase: Planner + UI audit (impeccable)
- Active agent: planner / frontend-styling-expert
- Next agent: frontend-styling-expert (impeccable audit + implementation) then security-reviewer / laravel-expert for bugs
- Required agents for this task: planner, frontend-styling-expert, laravel-expert, security-reviewer, tester, reviewer, linter, commit-message
- Optional agents for this task: architect
- Blockers: None
- Gate blockers: 

## Phase Gate
- [ ] Planner completed by `planner`
- [ ] Architecture completed by `architect` (if boundaries/contracts/schema are affected) -- n/a for review
- [ ] Backend implementation completed by `laravel-expert` or `node-expert` (if backend changes exist) -- pending remediation
- [ ] Frontend implementation completed by `react-expert` or `angular-expert` (if frontend changes exist) -- n/a
- [ ] Styling/UI notes completed by `frontend-styling-expert` (if UI/styling changes exist) -- n/a
- [ ] Database notes completed by `db-expert` (if schema/query/index changes exist) -- n/a
- [ ] DevOps notes completed by `devops` (if env/build/deploy/runtime changes exist)
- [ ] Security review completed by `security-reviewer` (if auth/data/contracts/secrets/logging risk exists)
- [ ] SEO notes completed by `seo-expert` (if SEO/content/indexability impact exists) -- n/a
- [ ] Review completed by `reviewer`
- [ ] Testing completed by `tester`
- [ ] Lint/static analysis completed by `linter`
- [ ] Commit message drafted by `commit-message`

## Files To Change
- (Analysis phase - recommendations may lead to changes in:)
- package.json / package-lock.json (axios + other deps)
- Dockerfile
- docker-compose.yml
- app/Services/Backup/* (potential hardening of command building)
- app/Services/Backup/FtpStorageService.php (driver selection / disk creation)
- app/Services/Backup/SshTunnelService.php (host key handling)
- resources/js/bootstrap.js (potential axios cleanup)
- .github/workflows/ (new - add CI for audits)
- Various config files and docs as needed

## Planner
- Responsible agent: `planner`
- Summary: User requested a full project study focused on vulnerabilities. Prior session performed dependency audits (npm), code review for shell execution patterns, credential storage, Docker config, auth, and file handling. Axios was investigated separately.
- Proposed approach: Document findings in this WORKFLOW_STATE.md. Prioritize recommendations (deps first, then code patterns, Docker, process improvements, CI). Offer to execute remediation in subsequent specialist phases.
- Affected layers: Dependencies (frontend), execution services (backup/restore), infrastructure (Docker), configuration.
- Risks: Changing CLI command construction could break compatibility with external tools. Docker changes may affect existing deployments.
- Validation plan: Re-run `npm audit` / `composer audit` after changes. Run full test suite. Manual verification of backup/restore flows for affected paths.
- Delegation plan: After planner, route to security-reviewer for formal findings, then laravel-expert + devops for fixes.
- Handoff to next agent: security-reviewer (to formalize the vulnerability list and risk ratings).

## Architecture Notes
- Responsible agent: `architect`
- Module / domain boundaries: Backup services are well separated (one per engine + storage). Execution logic lives in Services + Jobs.
- Data model impact: None for the study itself. Encrypted casts already used on sensitive config.
- API / route impact: None.
- Security / auth impact: Primary area of review (credential exposure on CLI, SSH verification, exposed debug tools).
- Performance / scalability notes: Streaming already used for uploads/downloads. Not a focus of this task.
- Migration / rollback notes: N/A.
- Open architectural risks: Reliance on external CLIs with known password-in-argv issues. No built-in at-rest encryption for backup archives themselves.
- Handoff to next agent:

## Backend Implementation Notes
- Responsible agent: `laravel-expert`
- Bugs found & fixed during analysis:
  - Raw `exec()` for multi-type tar packaging in ProcessBackupJob (replaced with Process facade + timeout for consistency and safety).
  - Raw `exec` in SshTunnelService (open, close, findPid) replaced with Process (full consistency, better error output).
  - FtpStorageService::createDisk: now uses Storage::build() instead of mutating global 'backup_ftp_temp' config (prevents collision on concurrent different FTP dests).
  - Mongo authDatabase in buildCommand: now uses $config['auth_database'] ?? 'admin' instead of hardcoded (consistent with restore).
  - Multiple silent @mkdir and potential tmp leaks on cancel (noted, partial mitigation via Process).
  - Dashboard polling + heavy computed queries without additional caching.
- Files changed: app/Jobs/Backup/ProcessBackupJob.php, app/Services/Backup/FtpStorageService.php, app/Services/Backup/MongodbBackupService.php, app/Services/Backup/SshTunnelService.php, resources/css/app.css, resources/css/backup/dashboard.css, resources/views/livewire/backup/dashboard.blade.php, resources/views/livewire/backup/backup-job-index.blade.php, tests/Feature/Backup/BackupJobTest.php
- Key decisions: Followed impeccable for UI. Prioritized layout anti-repetition over full theme rewrite. Used Storage::build for isolation.
- Handoff to next agent: tester (verify dashboard + jobs still work), reviewer

## Frontend Implementation Notes
- Responsible agent: `react-expert` or `angular-expert`
- Area touched: Minimal (possible cleanup of unused axios in bootstrap.js)
- Files changed:
- Commands executed:
- State / routing impact:
- UX / behavior changes:
- Accessibility considerations:
- Backward compatibility notes:
- Follow-up work:
- Handoff to next agent:

## Styling / UI Notes
- Responsible agent: `frontend-styling-expert`
- Impeccable skill: Loaded and applied (see SKILL.md rules: OKLCH only, no #000/#fff, no repeated card grids, no hero-metric cards, strong hierarchy, deliberate non-generic design).
- Styling stack detected: Tailwind 4 + DaisyUI 5
- Components/screens affected: Dashboard (primary), general card patterns across backup views
- Changes:
  - Introduced OKLCH committed theme tokens in app.css (cool slate neutrals + teal success accent for "data integrity" register).
  - Refactored dashboard stats from uniform 5-card grid into prominent success-rate visual + compact scannable strip (broke "hero metric card grid").
  - Replaced jobs health repeated card grid with clean status-accented list (better rhythm, scannability).
  - Reduced micro-typography spam, improved contrast hierarchy, consistent spacing.
  - Poll interval relaxed to 30s.
- Design debt noticed: Heavy generic DaisyUI "card + border-base-content/5" everywhere. Login/profile still have gradients (banned).
- Register chosen: Quiet precise server-room monitoring at night (calm trustworthy).
- Responsive considerations: Maintained, improved on mobile with strip layout.
- Handoff to next agent: reviewer (UI changes) + linter

## Database Notes
- Responsible agent: `db-expert`
- Engine / version: N/A for this task
- Schema impact:
- Migrations required:
- Index / query notes:
- Integrity / transaction notes:
- Rollback notes:
- Performance risks:
- Handoff to next agent:

## DevOps Notes
- Responsible agent: `devops`
- Env / config impact: Dockerfile base image, Node version, exposed sqlite-web service.
- Build / packaging impact: npm + composer audit integration.
- Runtime / infra impact: Container security (non-root, healthchecks).
- Secrets / credential handling: .env mounting (currently ro - good), APP_KEY protection.
- Logging / observability:
- Deployment / rollback notes:
- Operational risks: Debug tool (sqlite-web) exposed in default compose.
- Handoff to next agent:

## SEO Notes
- Responsible agent: `seo-expert`
- SEO surface affected: N/A
- Indexability / crawl notes:
- Metadata / structure notes:
- Content / intent notes:
- Internal linking notes:
- Priority recommendations:
- Handoff to next agent:

## Security Review
- Responsible agent: `security-reviewer`
- Targeted audit on restore chain / retention / auth:
  - Restore chain (ProcessRestoreJob::buildRestoreChain): solid - follows parent_backup_log_id for full + incrs up to target; handles full-only case. No obvious bugs, but relies on correct parent set during backup.
  - Retention (applyRetention / applyIncrementalRetention): good separation for incremental (keeps N full chains + their incrs); skips locked; sets storage_path=null after delete. Potential improvement: wrap deletes in transaction or add logging for audit.
  - Auth: basic Laravel session + Livewire; rate limiting only on login (5/min IP+email); remote restore creds encrypted in RestoreLog. No other obvious holes, but no 2FA or IP restrictions.
  - Overall: no new criticals beyond prior (deps, CLI exposure).
- Findings: (To be detailed from prior analysis)
  - Dependency: axios (multiple high severity: SSRF, prototype pollution), shell-quote (critical, transitive), vite/ws (high, mostly dev).
  - Code: CLI passwords visible in process list; disabled SSH host key verification; FTP driver switch logic; path construction in downloads without canonicalization.
  - Infra: sqlite-web always running and exposed; outdated base images in Dockerfile; no CI for automated audits.
- Affected surface: Dependency tree, backup/restore execution paths, Docker compose, credential transmission to external tools.
- Severity: Mix of high (deps) + medium (patterns) + low (defense-in-depth).
- Required remediation: Bump axios and audit-fix frontend; consider credential file approach for dumps; tighten SSH options; conditionalize debug services; add CI with audit steps.
- Waiver reason (if skipped):
- Handoff to next agent:

## Review
- Responsible agent: `reviewer`
- Critical findings:
- Warning findings:
- Info findings:
- Required fixes:
- Handoff to next agent:

## Testing
- Responsible agent: `tester`
- Test strategy: Existing Pest suite (mostly feature tests with mocks for external CLIs). Add tests for new hardening if implemented.
- Tests added/updated:
- Commands run: `composer test`
- Result summary:
- Gaps / not tested: Real external tool behavior (by design).
- Handoff to next agent:

## Lint / Static Analysis
- Responsible agent: `linter`
- Tools used: Laravel Pint (assumed), php -l, npm audit.
- Commands run:
- Findings:
- Auto-fixed items:
- Remaining issues:
- Handoff to next agent:

## Final Commit Message Draft
- Responsible agent: `commit-message`
- Type:
- Scope:
- Subject:
- Body:

## Tool Usage
- Serena: Used for symbolic navigation and file overviews during study.
- Graphify: Not used in this session.
- Context7: MCP connection failed (auth required).
- Bazel: Not applicable.
- Other MCPs: Headroom available for certain file types.
- Notes: Heavy use of grep + targeted reads + npm/composer audit + file inspection. Serena initial_instructions followed.

## Final Status
- Ready to merge: no (this is an analysis + recommendation phase)
- Remaining blockers: Need user decision on which recommendations to implement.
- Suggested next step: Review the vulnerability list and recommendations. Decide on remediation scope. Run `/using-superpowers` if not already done. Route to appropriate specialist (e.g. security-reviewer or devops) for next concrete changes.
