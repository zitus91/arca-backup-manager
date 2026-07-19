<?php

namespace App\Livewire\Backup;

use App\Models\BackupHost;
use App\Models\BackupSource;
use App\Services\Backup\SshTunnelService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BackupSourceForm extends Component
{
    public ?int $sourceId = null;

    public string $name = '';

    public bool $is_active = true;

    // Enabled sections
    public bool $enable_mysql = false;

    public bool $enable_mongodb = false;

    public bool $enable_filesystem = false;

    // MySQL fields
    public ?int $mysql_host_id = null;

    public array $mysql_databases = [];

    public array $mysql_available_databases = [];

    public ?string $mysql_connection_status = null;

    public string $mysql_connection_message = '';

    // MongoDB fields
    public ?int $mongodb_host_id = null;

    public array $mongodb_databases = [];

    public array $mongodb_available_databases = [];

    public ?string $mongodb_connection_status = null;

    public string $mongodb_connection_message = '';

    // Filesystem fields
    public ?int $filesystem_host_id = null;

    public array $fs_paths = [''];

    public string $fs_exclude_patterns = '*.log, *.tmp, node_modules, vendor';

    public array $fs_path_statuses = [];

    public array $fs_path_messages = [];

    // -------------------------------------------------------------------------

    public function mount(?int $sourceId = null): void
    {
        if ($sourceId) {
            $this->sourceId = $sourceId;
            $source = BackupSource::findOrFail($sourceId);
            $this->name = $source->name;
            $this->is_active = $source->is_active;

            $config = $source->config;

            $this->mysql_host_id = $source->mysql_host_id;
            $this->mongodb_host_id = $source->mongodb_host_id;
            $this->filesystem_host_id = $source->filesystem_host_id;

            if ($this->mysql_host_id) {
                $this->enable_mysql = true;
                $this->mysql_databases = $config['mysql']['databases'] ?? [];
            }
            if ($this->mongodb_host_id) {
                $this->enable_mongodb = true;
                $this->mongodb_databases = $config['mongodb']['databases'] ?? [];
            }
            if ($this->filesystem_host_id) {
                $this->enable_filesystem = true;
                $this->fillFilesystem($config['filesystem'] ?? []);
            }
        }
    }

    protected function fillFilesystem(array $config): void
    {
        $this->fs_paths = ! empty($config['paths']) ? $config['paths'] : [''];
        $this->fs_exclude_patterns = is_array($config['exclude_patterns'] ?? null)
            ? implode(', ', $config['exclude_patterns'])
            : ($config['exclude_patterns'] ?? '');
    }

    public function rules(): array
    {
        $rules = [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('backup_sources', 'name')->ignore($this->sourceId),
            ],
            'is_active' => 'boolean',
        ];

        if ($this->enable_mysql) {
            $rules = array_merge($rules, [
                'mysql_host_id' => ['required', Rule::exists('backup_hosts', 'id')->where('user_id', auth()->id())],
                'mysql_databases' => 'required|array|min:1',
                'mysql_databases.*' => 'string|max:255',
            ]);
        }

        if ($this->enable_mongodb) {
            $rules = array_merge($rules, [
                'mongodb_host_id' => ['required', Rule::exists('backup_hosts', 'id')->where('user_id', auth()->id())],
                'mongodb_databases' => 'required|array|min:1',
                'mongodb_databases.*' => 'string|max:255',
            ]);
        }

        if ($this->enable_filesystem) {
            $rules = array_merge($rules, [
                'filesystem_host_id' => ['required', Rule::exists('backup_hosts', 'id')->where('user_id', auth()->id())],
                'fs_paths' => 'required|array|min:1',
                'fs_paths.*' => 'required|string|max:500',
                'fs_exclude_patterns' => 'nullable|string',
            ]);
        }

        return $rules;
    }

    public function save(): void
    {
        if (! $this->enable_mysql && ! $this->enable_mongodb && ! $this->enable_filesystem) {
            $this->addError('enable_sources', __('backup-source.at_least_one_source'));

            return;
        }

        $this->validate();

        $config = [];

        if ($this->enable_mysql) {
            $config['mysql'] = ['databases' => $this->mysql_databases];
        }

        if ($this->enable_mongodb) {
            $config['mongodb'] = ['databases' => $this->mongodb_databases];
        }

        if ($this->enable_filesystem) {
            $config['filesystem'] = [
                'paths' => array_values(array_filter(array_map('trim', $this->fs_paths), fn ($p) => $p !== '')),
                'exclude_patterns' => $this->fs_exclude_patterns
                    ? array_map('trim', explode(',', $this->fs_exclude_patterns))
                    : [],
            ];
        }

        $data = [
            'name' => $this->name,
            'is_active' => $this->is_active,
            'mysql_host_id' => $this->enable_mysql ? $this->mysql_host_id : null,
            'mongodb_host_id' => $this->enable_mongodb ? $this->mongodb_host_id : null,
            'filesystem_host_id' => $this->enable_filesystem ? $this->filesystem_host_id : null,
            'config' => $config,
        ];

        if ($this->sourceId) {
            $source = BackupSource::findOrFail($this->sourceId);
            $oldValues = $source->toArray();
            $source->update($data);
            \App\Models\AuditLog::record('updated', "Updated backup source: {$source->name}", $source, $oldValues, $data);
        } else {
            $source = BackupSource::create($data);
            \App\Models\AuditLog::record('created', "Created backup source: {$source->name}", $source, null, $data);
        }

        $this->dispatch('source-saved');
    }

    // -- MySQL -------------------------------------------------------

    public function toggleSelectAllDatabases(): void
    {
        if (count($this->mysql_databases) === count($this->mysql_available_databases)) {
            $this->mysql_databases = [];
        } else {
            $this->mysql_databases = $this->mysql_available_databases;
        }
    }

    public function loadMysqlDatabases(): void
    {
        $this->mysql_available_databases = [];
        $this->mysql_connection_status = null;
        $this->mysql_connection_message = '';

        $host = $this->mysql_host_id ? BackupHost::find($this->mysql_host_id) : null;

        if (! $host) {
            $this->mysql_connection_status = 'failed';
            $this->mysql_connection_message = __('backup-source.no_host_for_type');

            return;
        }

        $mysql = $host->config['mysql'] ?? [];

        try {
            $run = function (string $connHost, int $connPort) use ($mysql): void {
                $pdo = new \PDO(
                    "mysql:host={$connHost};port={$connPort}",
                    $mysql['user'] ?? 'root',
                    $mysql['password'] ?? '',
                    [\PDO::ATTR_TIMEOUT => 5, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
                );
                $stmt = $pdo->query('SHOW DATABASES');
                $allDatabases = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                $systemDbs = ['information_schema', 'mysql', 'performance_schema', 'sys'];
                $this->mysql_available_databases = array_values(array_diff($allDatabases, $systemDbs));
            };

            $sshConfig = $host->sshConfig();
            if (! empty($sshConfig['enabled'])) {
                app(SshTunnelService::class)->withTunnel(
                    $sshConfig,
                    $mysql['host'] ?? '127.0.0.1',
                    (int) ($mysql['port'] ?? 3306),
                    fn (int $localPort) => $run('127.0.0.1', $localPort)
                );
            } else {
                $run($mysql['host'] ?? '127.0.0.1', (int) ($mysql['port'] ?? 3306));
            }

            $this->mysql_connection_status = 'success';
            $this->mysql_connection_message = __('backup-source.mysql_connection_success');
        } catch (\Throwable $e) {
            $this->mysql_connection_status = 'failed';
            $this->mysql_connection_message = __('backup-source.mysql_connection_failed').': '.$e->getMessage();
        }
    }

    // -- MongoDB -----------------------------------------------------

    public function toggleSelectAllMongoDatabases(): void
    {
        if (count($this->mongodb_databases) === count($this->mongodb_available_databases)) {
            $this->mongodb_databases = [];
        } else {
            $this->mongodb_databases = $this->mongodb_available_databases;
        }
    }

    public function loadMongodbDatabases(): void
    {
        $this->mongodb_available_databases = [];
        $this->mongodb_connection_status = null;
        $this->mongodb_connection_message = '';

        $host = $this->mongodb_host_id ? BackupHost::find($this->mongodb_host_id) : null;

        if (! $host) {
            $this->mongodb_connection_status = 'failed';
            $this->mongodb_connection_message = __('backup-source.no_host_for_type');

            return;
        }

        $mongodb = $host->config['mongodb'] ?? [];

        try {
            $run = function (string $connHost, int $connPort) use ($mongodb): void {
                if (extension_loaded('mongodb')) {
                    $uri = 'mongodb://';
                    if (! empty($mongodb['user'])) {
                        $uri .= urlencode($mongodb['user']).':'.urlencode($mongodb['password'] ?? '').'@';
                    }
                    $uri .= "{$connHost}:{$connPort}";

                    $manager = new \MongoDB\Driver\Manager($uri, [
                        'connectTimeoutMS' => 5000,
                        'serverSelectionTimeoutMS' => 5000,
                    ]);

                    $command = new \MongoDB\Driver\Command(['listDatabases' => 1]);
                    $cursor = $manager->executeCommand('admin', $command);
                    $result = current($cursor->toArray());

                    $systemDbs = ['admin', 'config', 'local'];
                    $databases = [];
                    foreach ($result->databases as $db) {
                        $name = is_object($db) ? $db->name : ($db['name'] ?? '');
                        if (! in_array($name, $systemDbs) && $name !== '') {
                            $databases[] = $name;
                        }
                    }
                    $this->mongodb_available_databases = $databases;
                } else {
                    // Fallback: try mongosh command line
                    $auth = '';
                    if (! empty($mongodb['user'])) {
                        $auth = sprintf(
                            '-u %s -p %s --authenticationDatabase admin',
                            escapeshellarg($mongodb['user']),
                            escapeshellarg($mongodb['password'] ?? '')
                        );
                    }

                    $cmd = sprintf(
                        'mongosh --host %s --port %d %s --quiet --eval "db.adminCommand(\'listDatabases\').databases.forEach(d => print(d.name))" 2>&1',
                        escapeshellarg($connHost),
                        $connPort,
                        $auth
                    );

                    exec($cmd, $output, $exitCode);

                    if ($exitCode !== 0) {
                        throw new \RuntimeException(implode("\n", $output));
                    }

                    $systemDbs = ['admin', 'config', 'local'];
                    $this->mongodb_available_databases = array_values(
                        array_filter(
                            array_map('trim', $output),
                            fn ($db) => ! in_array($db, $systemDbs) && $db !== ''
                        )
                    );
                }
            };

            $sshConfig = $host->sshConfig();
            if (! empty($sshConfig['enabled'])) {
                app(SshTunnelService::class)->withTunnel(
                    $sshConfig,
                    $mongodb['host'] ?? '127.0.0.1',
                    (int) ($mongodb['port'] ?? 27017),
                    fn (int $localPort) => $run('127.0.0.1', $localPort)
                );
            } else {
                $run($mongodb['host'] ?? '127.0.0.1', (int) ($mongodb['port'] ?? 27017));
            }

            $this->mongodb_connection_status = 'success';
            $this->mongodb_connection_message = __('backup-source.mongodb_connection_success');
        } catch (\Throwable $e) {
            $this->mongodb_connection_status = 'failed';
            $this->mongodb_connection_message = __('backup-source.mongodb_connection_failed').': '.$e->getMessage();
        }
    }

    // -- Filesystem --------------------------------------------------

    public function addFsPath(): void
    {
        $this->fs_paths[] = '';
    }

    public function removeFsPath(int $index): void
    {
        unset($this->fs_paths[$index]);
        $this->fs_paths = array_values($this->fs_paths);
        unset($this->fs_path_statuses[$index]);
        unset($this->fs_path_messages[$index]);
        $this->fs_path_statuses = array_values($this->fs_path_statuses);
        $this->fs_path_messages = array_values($this->fs_path_messages);
    }

    public function checkFilesystemPath(int $index): void
    {
        $path = trim($this->fs_paths[$index] ?? '');

        if (empty($path)) {
            $this->fs_path_statuses[$index] = 'failed';
            $this->fs_path_messages[$index] = __('backup-source.fs_path_empty');

            return;
        }

        $host = $this->filesystem_host_id ? BackupHost::find($this->filesystem_host_id) : null;
        $sshConfig = $host?->sshConfig();

        if ($sshConfig && ! empty($sshConfig['enabled'])) {
            // Check path on remote host via SSH
            $user = escapeshellarg($sshConfig['user']);
            $sshHost = escapeshellarg($sshConfig['host']);
            $port = (int) $sshConfig['port'];
            $baseOpts = "-o ConnectTimeout=8 -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR -p {$port}";

            $safeRemotePath = '"'.str_replace('"', '\\"', $path).'"';
            $remoteCheckCmd = "[ -d {$safeRemotePath} ] && echo dir || [ -f {$safeRemotePath} ] && echo file || echo notfound";
            $quotedCheck = escapeshellarg($remoteCheckCmd);

            if ($sshConfig['auth_method'] === 'password') {
                $pass = escapeshellarg($sshConfig['password']);
                $cmd = "sshpass -p {$pass} ssh {$baseOpts} {$user}@{$sshHost} {$quotedCheck} 2>&1";
            } else {
                $keyPath = escapeshellarg($sshConfig['key_path']);
                $cmd = "ssh {$baseOpts} -i {$keyPath} {$user}@{$sshHost} {$quotedCheck} 2>&1";
            }

            exec($cmd, $output, $exitCode);
            $result = trim(implode('', $output));

            if ($exitCode !== 0 || $result === 'notfound' || $result === '') {
                $this->fs_path_statuses[$index] = 'failed';
                $this->fs_path_messages[$index] = __('backup-source.fs_path_not_found');
            } elseif ($result === 'dir') {
                $this->fs_path_statuses[$index] = 'success';
                $this->fs_path_messages[$index] = __('backup-source.fs_path_exists_remote');
            } else {
                $this->fs_path_statuses[$index] = 'success';
                $this->fs_path_messages[$index] = __('backup-source.fs_path_is_file');
            }

            return;
        }

        if (is_dir($path) && is_readable($path)) {
            $items = @scandir($path);
            $count = $items ? count($items) - 2 : 0;
            $this->fs_path_statuses[$index] = 'success';
            $this->fs_path_messages[$index] = __('backup-source.fs_path_exists', ['count' => $count]);
        } elseif (is_file($path) && is_readable($path)) {
            $this->fs_path_statuses[$index] = 'success';
            $this->fs_path_messages[$index] = __('backup-source.fs_path_is_file');
        } else {
            $this->fs_path_statuses[$index] = 'failed';
            $this->fs_path_messages[$index] = __('backup-source.fs_path_not_found');
        }
    }

    public function checkAllFilesystemPaths(): void
    {
        foreach ($this->fs_paths as $index => $path) {
            $this->checkFilesystemPath($index);
        }
    }

    public function render()
    {
        return view('livewire.backup.backup-source-form', [
            'mysqlHosts' => $this->hostsOffering('mysql', $this->mysql_host_id),
            'mongodbHosts' => $this->hostsOffering('mongodb', $this->mongodb_host_id),
            'filesystemHosts' => $this->hostsOffering('filesystem', $this->filesystem_host_id),
        ]);
    }

    private function hostsOffering(string $type, ?int $currentId)
    {
        $hosts = BackupHost::active()->orderBy('name')->get()->filter->offers($type)->values();

        if ($currentId && ! $hosts->contains('id', $currentId)) {
            $current = BackupHost::find($currentId);
            if ($current) {
                $hosts = $hosts->push($current)->sortBy('name')->values();
            }
        }

        return $hosts;
    }
}
