<?php

namespace App\Livewire\Backup;

use App\Jobs\Restore\ProcessRestoreJob;
use App\Models\BackupJob;
use App\Models\BackupLog;
use App\Models\BackupSource;
use App\Models\RestoreLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class RestoreIndex extends Component
{
    use WithPagination;

    // Filters
    public string $filterJobId = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';

    // Restore modal
    public bool $showRestoreModal = false;
    public ?int $selectedBackupLogId = null;
    public string $restoreType = 'full'; // full, db_only, files_only
    public array $selectedBackupInfo = [];

    // Granular selection
    public array $selectedDatabases = [];
    public array $selectedPaths = [];

    // Custom names (editable target names)
    public array $customDbNames = [];  // [['original' => 'db', 'target' => 'db_restored_...', 'type' => 'mysql'], ...]
    public array $customPaths = [];    // [['original' => '/path', 'target' => '/path_restored_...'], ...]

    // Restore target
    public string $restoreTarget = 'same_host';
    public ?int $knownSourceId = null;
    public array $remoteConfig = [];

    // Override existing
    public bool $overrideExisting = false;

    // Detail modal
    public bool $showDetail = false;
    public ?int $detailRestoreLogId = null;

    // Confirmation
    public bool $showConfirmation = false;

    // Running state
    public bool $isRestoring = false;

    public function updatedFilterJobId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Open the restore modal for a specific backup log.
     */
    public function openRestoreModal(int $backupLogId): void
    {
        $log = BackupLog::with(['job.source', 'job.destination'])->find($backupLogId);

        if (! $log || $log->status !== 'success' || ! $log->storage_path) {
            $this->dispatch('notify', type: 'error', message: __('restore.backup_not_available'));
            return;
        }

        $this->selectedBackupLogId = $backupLogId;
        $this->restoreType = 'full';

        // Build info about what can be restored
        $sourceConfig = $log->job->source->config ?? [];
        $this->selectedBackupInfo = [
            'job_name' => $log->job->name,
            'source_name' => $log->job->source->name,
            'destination_name' => $log->job->destination->name,
            'file_name' => $log->file_name,
            'backup_date' => $log->started_at->format('d/m/Y H:i'),
            'file_size' => $log->formatted_size,
            'has_mysql' => isset($sourceConfig['mysql']),
            'has_mongodb' => isset($sourceConfig['mongodb']),
            'has_filesystem' => isset($sourceConfig['filesystem']),
            'mysql_databases' => isset($sourceConfig['mysql'])
                ? ($sourceConfig['mysql']['databases'] ?? (isset($sourceConfig['mysql']['database']) ? [$sourceConfig['mysql']['database']] : []))
                : [],
            'mongodb_databases' => isset($sourceConfig['mongodb'])
                ? ($sourceConfig['mongodb']['databases'] ?? (isset($sourceConfig['mongodb']['database']) ? [$sourceConfig['mongodb']['database']] : []))
                : [],
            'filesystem_paths' => isset($sourceConfig['filesystem'])
                ? ($sourceConfig['filesystem']['paths'] ?? (isset($sourceConfig['filesystem']['path']) ? [$sourceConfig['filesystem']['path']] : []))
                : [],
        ];

        // Determine available restore types
        $hasDb = $this->selectedBackupInfo['has_mysql'] || $this->selectedBackupInfo['has_mongodb'];
        $hasFs = $this->selectedBackupInfo['has_filesystem'];

        // If only one type, force that
        if ($hasDb && ! $hasFs) {
            $this->restoreType = 'db_only';
        } elseif (! $hasDb && $hasFs) {
            $this->restoreType = 'files_only';
        }

        // Pre-select all databases and paths
        $this->selectedDatabases = array_merge(
            $this->selectedBackupInfo['mysql_databases'] ?? [],
            $this->selectedBackupInfo['mongodb_databases'] ?? [],
        );
        $this->selectedPaths = $this->selectedBackupInfo['filesystem_paths'] ?? [];

        // Initialize custom names with defaults
        $timestamp = now()->format('Ymd_His');
        $this->customDbNames = [];
        $this->customPaths = [];

        foreach ($this->selectedBackupInfo['mysql_databases'] ?? [] as $db) {
            $this->customDbNames[] = [
                'original' => $db,
                'target' => $db . '_restored_' . $timestamp,
                'type' => 'mysql',
            ];
        }

        foreach ($this->selectedBackupInfo['mongodb_databases'] ?? [] as $db) {
            $this->customDbNames[] = [
                'original' => $db,
                'target' => $db . '_restored_' . $timestamp,
                'type' => 'mongodb',
            ];
        }

        foreach ($this->selectedBackupInfo['filesystem_paths'] ?? [] as $path) {
            $this->customPaths[] = [
                'original' => $path,
                'target' => rtrim($path, '/') . '_restored_' . $timestamp,
            ];
        }

        // Initialize restore target and remote config
        $this->restoreTarget = 'same_host';
        $this->knownSourceId = null;
        $this->overrideExisting = false;
        $this->remoteConfig = [
            'mysql' => ['host' => '', 'port' => '3306', 'username' => '', 'password' => ''],
            'mongodb' => ['host' => '', 'port' => '27017', 'username' => '', 'password' => '', 'auth_database' => 'admin'],
            'filesystem' => ['ssh_host' => '', 'ssh_port' => '22', 'ssh_user' => '', 'ssh_key_path' => ''],
        ];

        $this->showRestoreModal = true;
        $this->showConfirmation = false;
    }

    /**
     * Close the restore modal.
     */
    public function closeRestoreModal(): void
    {
        $this->showRestoreModal = false;
        $this->selectedBackupLogId = null;
        $this->selectedBackupInfo = [];
        $this->selectedDatabases = [];
        $this->selectedPaths = [];
        $this->customDbNames = [];
        $this->customPaths = [];
        $this->restoreTarget = 'same_host';
        $this->knownSourceId = null;
        $this->remoteConfig = [];
        $this->overrideExisting = false;
        $this->showConfirmation = false;
    }

    /**
     * Show the confirmation step.
     */
    public function confirmRestore(): void
    {
        // Validate at least one item is selected
        $hasSelectedDb = ! empty($this->selectedDatabases) && in_array($this->restoreType, ['full', 'db_only']);
        $hasSelectedFs = ! empty($this->selectedPaths) && in_array($this->restoreType, ['full', 'files_only']);

        if (! $hasSelectedDb && ! $hasSelectedFs) {
            $this->dispatch('notify', type: 'error', message: __('restore.no_items_selected'));

            return;
        }

        // Validate remote config if remote host is selected
        if (in_array($this->restoreTarget, ['remote_host', 'known_host'])) {
            if ($hasSelectedDb) {
                $hasMysql = ($this->selectedBackupInfo['has_mysql'] ?? false);
                $hasMongo = ($this->selectedBackupInfo['has_mongodb'] ?? false);

                if ($hasMysql && empty($this->remoteConfig['mysql']['host'])) {
                    $this->dispatch('notify', type: 'error', message: __('restore.remote_mysql_required'));

                    return;
                }

                if ($hasMongo && empty($this->remoteConfig['mongodb']['host'])) {
                    $this->dispatch('notify', type: 'error', message: __('restore.remote_mongodb_required'));

                    return;
                }
            }

            if ($hasSelectedFs && empty($this->remoteConfig['filesystem']['ssh_host'])) {
                $this->dispatch('notify', type: 'error', message: __('restore.remote_filesystem_required'));

                return;
            }
        }

        // Validate custom names are not empty
        foreach ($this->customDbNames as $item) {
            if (in_array($item['original'], $this->selectedDatabases) && empty(trim($item['target']))) {
                $this->dispatch('notify', type: 'error', message: __('restore.custom_name_empty'));

                return;
            }
        }

        foreach ($this->customPaths as $item) {
            if (in_array($item['original'], $this->selectedPaths) && empty(trim($item['target']))) {
                $this->dispatch('notify', type: 'error', message: __('restore.custom_name_empty'));

                return;
            }
        }

        $this->showConfirmation = true;
    }

    /**
     * Reset custom names to defaults.
     */
    public function resetCustomNames(): void
    {
        $timestamp = now()->format('Ymd_His');

        foreach ($this->customDbNames as $index => $item) {
            $this->customDbNames[$index]['target'] = $item['original'] . '_restored_' . $timestamp;
        }

        foreach ($this->customPaths as $index => $item) {
            $this->customPaths[$index]['target'] = rtrim($item['original'], '/') . '_restored_' . $timestamp;
        }
    }

    /**
     * Execute the restore.
     */
    public function executeRestore(): void
    {
        if (! $this->selectedBackupLogId) {
            return;
        }

        $this->isRestoring = true;

        // Build selected_items based on restore type
        $selectedItems = [];

        if (in_array($this->restoreType, ['full', 'db_only'])) {
            // Separate mysql and mongodb databases
            $allMysql = $this->selectedBackupInfo['mysql_databases'] ?? [];
            $allMongo = $this->selectedBackupInfo['mongodb_databases'] ?? [];

            $selectedItems['mysql_databases'] = array_values(array_intersect($this->selectedDatabases, $allMysql));
            $selectedItems['mongodb_databases'] = array_values(array_intersect($this->selectedDatabases, $allMongo));
        }

        if (in_array($this->restoreType, ['full', 'files_only'])) {
            $selectedItems['filesystem_paths'] = array_values($this->selectedPaths);
        }

        // Build custom names mapping
        $customNames = ['databases' => [], 'paths' => []];

        foreach ($this->customDbNames as $item) {
            if (in_array($item['original'], $this->selectedDatabases)) {
                $customNames['databases'][$item['original']] = $item['target'];
            }
        }

        foreach ($this->customPaths as $item) {
            if (in_array($item['original'], $this->selectedPaths)) {
                $customNames['paths'][$item['original']] = $item['target'];
            }
        }

        // Build remote host config
        $remoteHostConfig = null;
        if (in_array($this->restoreTarget, ['remote_host', 'known_host'])) {
            $remoteHostConfig = [];

            if ($this->selectedBackupInfo['has_mysql'] ?? false) {
                $remoteHostConfig['mysql'] = $this->remoteConfig['mysql'];
            }
            if ($this->selectedBackupInfo['has_mongodb'] ?? false) {
                $remoteHostConfig['mongodb'] = $this->remoteConfig['mongodb'];
            }
            if ($this->selectedBackupInfo['has_filesystem'] ?? false) {
                $remoteHostConfig['filesystem'] = $this->remoteConfig['filesystem'];
            }
        }

        $effectiveTarget = in_array($this->restoreTarget, ['remote_host', 'known_host'])
            ? 'remote_host'
            : $this->restoreTarget;

        $restoreLog = RestoreLog::create([
            'backup_log_id' => $this->selectedBackupLogId,
            'user_id' => auth()->id(),
            'restore_type' => $this->restoreType,
            'restore_target' => $effectiveTarget,
            'remote_host_config' => $remoteHostConfig,
            'custom_names' => $customNames,
            'override_existing' => $this->overrideExisting,
            'selected_items' => $selectedItems,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        ProcessRestoreJob::dispatch($restoreLog->id);

        $this->closeRestoreModal();
        $this->isRestoring = false;

        $this->dispatch('notify', type: 'info', message: __('restore.restore_started'));
    }

    /**
     * When a known source is selected, pre-fill remoteConfig from its credentials.
     */
    public function updatedKnownSourceId(?int $value): void
    {
        if (! $value) {
            return;
        }

        $source = BackupSource::find($value);
        if (! $source) {
            return;
        }

        $cfg = $source->config ?? [];

        if (isset($cfg['mysql'])) {
            $this->remoteConfig['mysql'] = [
                'host'     => $cfg['mysql']['host'] ?? '',
                'port'     => (string) ($cfg['mysql']['port'] ?? 3306),
                'username' => $cfg['mysql']['username'] ?? '',
                'password' => $cfg['mysql']['password'] ?? '',
            ];
        }

        if (isset($cfg['mongodb'])) {
            $this->remoteConfig['mongodb'] = [
                'host'          => $cfg['mongodb']['host'] ?? '',
                'port'          => (string) ($cfg['mongodb']['port'] ?? 27017),
                'username'      => $cfg['mongodb']['username'] ?? '',
                'password'      => $cfg['mongodb']['password'] ?? '',
                'auth_database' => 'admin',
            ];
        }

        if (isset($cfg['filesystem'])) {
            $ssh = $cfg['filesystem']['ssh'] ?? [];
            $this->remoteConfig['filesystem'] = [
                'ssh_host'     => $ssh['host'] ?? '',
                'ssh_port'     => (string) ($ssh['port'] ?? 22),
                'ssh_user'     => $ssh['user'] ?? '',
                'ssh_key_path' => $ssh['key_path'] ?? '',
            ];
        }
    }

    /**
     * Reset known source selection when restore target changes.
     */
    public function updatedRestoreTarget(string $value): void
    {
        if ($value !== 'known_host') {
            $this->knownSourceId = null;
        }
    }

    /**
     * Open restore log detail.
     */
    public function openDetail(int $restoreLogId): void
    {
        $this->detailRestoreLogId = $restoreLogId;
        $this->showDetail = true;
    }

    /**
     * Close restore log detail.
     */
    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailRestoreLogId = null;
    }

    #[On('echo:backup-jobs,.restore.completed')]
    public function onRestoreCompleted(): void
    {
        // Re-renders automatically
    }

    #[On('echo:backup-jobs,.restore.started')]
    public function onRestoreStarted(): void
    {
        // Re-renders automatically
    }

    public function render()
    {
        // Available backups (successful with storage_path)
        $backupsQuery = BackupLog::with(['job.source', 'job.destination'])
            ->where('status', 'success')
            ->whereNotNull('storage_path')
            ->latest('started_at');

        if ($this->filterJobId) {
            $backupsQuery->where('backup_job_id', (int) $this->filterJobId);
        }

        if ($this->filterDateFrom) {
            $backupsQuery->where('started_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $backupsQuery->where('started_at', '<=', $this->filterDateTo . ' 23:59:59');
        }

        // Restore history
        $restoreLogs = RestoreLog::with(['backupLog.job.source', 'backupLog.job.destination', 'user'])
            ->latest()
            ->limit(20)
            ->get();

        $detailLog = null;
        if ($this->detailRestoreLogId) {
            $detailLog = RestoreLog::with(['backupLog.job.source', 'backupLog.job.destination', 'user'])
                ->find($this->detailRestoreLogId);
        }

        return view('livewire.backup.restore-index', [
            'backups' => $backupsQuery->paginate(15),
            'jobs' => BackupJob::all(),
            'restoreLogs' => $restoreLogs,
            'detailLog' => $detailLog,
            'backupSources' => BackupSource::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
