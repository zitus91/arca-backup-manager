<?php

namespace App\Livewire\Backup;

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
    public string $mysql_host = '127.0.0.1';

    public int $mysql_port = 3306;

    public string $mysql_username = 'root';

    public string $mysql_password = '';

    public array $mysql_databases = [];

    public array $mysql_available_databases = [];

    public ?string $mysql_connection_status = null;

    public string $mysql_connection_message = '';

    // MongoDB fields
    public string $mongodb_host = '127.0.0.1';

    public int $mongodb_port = 27017;

    public string $mongodb_username = '';

    public string $mongodb_password = '';

    public array $mongodb_databases = [];

    public array $mongodb_available_databases = [];

    public ?string $mongodb_connection_status = null;

    public string $mongodb_connection_message = '';

    // Filesystem fields
    public array $fs_paths = [''];

    public string $fs_exclude_patterns = '*.log, *.tmp, node_modules, vendor';

    public array $fs_path_statuses = [];

    public array $fs_path_messages = [];

    // Selected remote host (SSH). Null = local / direct connection.
    public ?int $host_id = null;

    // -------------------------------------------------------------------------

    public function mount(?int $sourceId = null): void
    {
        if ($sourceId) {
            $this->sourceId = $sourceId;
            $source = BackupSource::findOrFail($sourceId);
            $this->name = $source->name;
            $this->is_active = $source->is_active;

            $config = $source->config;

            // New multi-source format: config has top-level keys per type
            if (isset($config['mysql'])) {
                $this->enable_mysql = true;
                $this->fillMysql($config['mysql']);
            }
            if (isset($config['mongodb'])) {
                $this->enable_mongodb = true;
                $this->fillMongodb($config['mongodb']);
            }
            if (isset($config['filesystem'])) {
                $this->enable_filesystem = true;
                $this->fillFilesystem($config['filesystem']);
            }

            $this->host_id = $source->host_id;

            // Backward compat: old single-type format (flat config with separate type column)
            if (! $this->enable_mysql && ! $this->enable_mongodb && ! $this->enable_filesystem) {
                $type = $source->getAttributes()['type'] ?? null;
                if ($type === 'mysql') {
                    $this->enable_mysql = true;
                    $this->fillMysql($config);
                } elseif ($type === 'mongodb') {
                    $this->enable_mongodb = true;
                    $this->fillMongodb($config);
                } elseif ($type === 'filesystem') {
                    $this->enable_filesystem = true;
                    $this->fillFilesystem($config);
                }
            }
        }
    }

    private function hostSshConfig(): ?array
    {
        if (! $this->host_id) {
            return null;
        }

        $host = \App\Models\BackupHost::find($this->host_id);

        return $host ? array_merge($host->config, ['enabled' => true]) : null;
    }

    protected function fillMysql(array $config): void
    {
        $this->mysql_host = $config['host'] ?? '127.0.0.1';
        $this->mysql_port = $config['port'] ?? 3306;
        $this->mysql_username = $config['username'] ?? 'root';
        $this->mysql_password = $config['password'] ?? '';
        $this->mysql_databases = $config['databases'] ?? [];

        if (empty($this->mysql_databases) && ! empty($config['database'])) {
            $this->mysql_databases = [$config['database']];
        }
    }

    protected function fillMongodb(array $config): void
    {
        $this->mongodb_host = $config['host'] ?? '127.0.0.1';
        $this->mongodb_port = $config['port'] ?? 27017;
        $this->mongodb_username = $config['username'] ?? '';
        $this->mongodb_password = $config['password'] ?? '';
        $this->mongodb_databases = $config['databases'] ?? [];

        if (empty($this->mongodb_databases) && ! empty($config['database'])) {
            $this->mongodb_databases = [$config['database']];
        }
    }

    protected function fillFilesystem(array $config): void
    {
        // Support both old single-path and new multi-path format
        if (isset($config['paths']) && is_array($config['paths'])) {
            $this->fs_paths = ! empty($config['paths']) ? $config['paths'] : [''];
        } elseif (isset($config['path']) && $config['path'] !== '') {
            $this->fs_paths = [$config['path']];
        } else {
            $this->fs_paths = [''];
        }
        $this->fs_exclude_patterns = is_array($config['exclude_patterns'] ?? null)
            ? implode(', ', $config['exclude_patterns'])
            : '';
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
                'mysql_host' => 'required|string|max:255',
                'mysql_port' => 'required|integer|min:1|max:65535',
                'mysql_username' => 'required|string|max:255',
                'mysql_password' => 'required|string|max:255',
                'mysql_databases' => 'required|array|min:1',
                'mysql_databases.*' => 'string|max:255',
            ]);
        }

        if ($this->enable_mongodb) {
            $rules = array_merge($rules, [
                'mongodb_host' => 'required|string|max:255',
                'mongodb_port' => 'required|integer|min:1|max:65535',
                'mongodb_databases' => 'required|array|min:1',
                'mongodb_databases.*' => 'string|max:255',
            ]);
        }

        if ($this->enable_filesystem) {
            $rules = array_merge($rules, [
                'fs_paths' => 'required|array|min:1',
                'fs_paths.*' => 'required|string|max:500',
                'fs_exclude_patterns' => 'nullable|string',
            ]);
        }

        $rules['host_id'] = 'nullable|exists:backup_hosts,id';

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
            $config['mysql'] = [
                'host' => $this->mysql_host,
                'port' => $this->mysql_port,
                'username' => $this->mysql_username,
                'password' => $this->mysql_password,
                'databases' => $this->mysql_databases,
            ];
        }

        if ($this->enable_mongodb) {
            $config['mongodb'] = [
                'host' => $this->mongodb_host,
                'port' => $this->mongodb_port,
                'username' => $this->mongodb_username,
                'password' => $this->mongodb_password,
                'databases' => $this->mongodb_databases,
            ];
        }

        if ($this->enable_filesystem) {
            $paths = array_values(array_filter(array_map('trim', $this->fs_paths), fn ($p) => $p !== ''));
            $config['filesystem'] = [
                'paths' => $paths,
                'path' => $paths[0] ?? '',  // backward compat
                'exclude_patterns' => $this->fs_exclude_patterns
                    ? array_map('trim', explode(',', $this->fs_exclude_patterns))
                    : [],
            ];
        }

        $data = [
            'name' => $this->name,
            'config' => $config,
            'is_active' => $this->is_active,
            'host_id' => $this->host_id,
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

    public function testMysqlConnection(): void
    {
        $this->mysql_available_databases = [];
        $this->mysql_connection_status = null;
        $this->mysql_connection_message = '';

        try {
            $run = function (string $host, int $port): void {
                $pdo = new \PDO(
                    "mysql:host={$host};port={$port}",
                    $this->mysql_username,
                    $this->mysql_password,
                    [\PDO::ATTR_TIMEOUT => 5, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
                );
                $stmt = $pdo->query('SHOW DATABASES');
                $allDatabases = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                $systemDbs = ['information_schema', 'mysql', 'performance_schema', 'sys'];
                $this->mysql_available_databases = array_values(array_diff($allDatabases, $systemDbs));
            };

            $sshConfig = $this->hostSshConfig();
            if ($sshConfig) {
                app(SshTunnelService::class)->withTunnel(
                    $sshConfig,
                    $this->mysql_host,
                    $this->mysql_port,
                    fn (int $localPort) => $run('127.0.0.1', $localPort)
                );
            } else {
                $run($this->mysql_host, $this->mysql_port);
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

    public function testMongoConnection(): void
    {
        $this->mongodb_available_databases = [];
        $this->mongodb_connection_status = null;
        $this->mongodb_connection_message = '';

        try {
            $run = function (string $host, int $port): void {
                if (extension_loaded('mongodb')) {
                    $uri = 'mongodb://';
                    if ($this->mongodb_username) {
                        $uri .= urlencode($this->mongodb_username).':'.urlencode($this->mongodb_password).'@';
                    }
                    $uri .= "{$host}:{$port}";

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
                    if ($this->mongodb_username) {
                        $auth = sprintf(
                            '-u %s -p %s --authenticationDatabase admin',
                            escapeshellarg($this->mongodb_username),
                            escapeshellarg($this->mongodb_password)
                        );
                    }

                    $cmd = sprintf(
                        'mongosh --host %s --port %d %s --quiet --eval "db.adminCommand(\'listDatabases\').databases.forEach(d => print(d.name))" 2>&1',
                        escapeshellarg($host),
                        $port,
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

            $sshConfig = $this->hostSshConfig();
            if ($sshConfig) {
                app(SshTunnelService::class)->withTunnel(
                    $sshConfig,
                    $this->mongodb_host,
                    $this->mongodb_port,
                    fn (int $localPort) => $run('127.0.0.1', $localPort)
                );
            } else {
                $run($this->mongodb_host, $this->mongodb_port);
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

        $sshConfig = $this->hostSshConfig();
        if ($sshConfig) {
            // Check path on remote host via SSH
            $user = escapeshellarg($sshConfig['user']);
            $host = escapeshellarg($sshConfig['host']);
            $port = (int) $sshConfig['port'];
            $baseOpts = "-o ConnectTimeout=8 -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR -p {$port}";

            $safeRemotePath = '"'.str_replace('"', '\\"', $path).'"';
            $remoteCheckCmd = "[ -d {$safeRemotePath} ] && echo dir || [ -f {$safeRemotePath} ] && echo file || echo notfound";
            $quotedCheck = escapeshellarg($remoteCheckCmd);

            if ($sshConfig['auth_method'] === 'password') {
                $pass = escapeshellarg($sshConfig['password']);
                $cmd = "sshpass -p {$pass} ssh {$baseOpts} {$user}@{$host} {$quotedCheck} 2>&1";
            } else {
                $keyPath = escapeshellarg($sshConfig['key_path']);
                $cmd = "ssh {$baseOpts} -i {$keyPath} {$user}@{$host} {$quotedCheck} 2>&1";
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
            'hosts' => \App\Models\BackupHost::active()->orderBy('name')->get(),
        ]);
    }
}
