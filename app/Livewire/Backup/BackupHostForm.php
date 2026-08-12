<?php

namespace App\Livewire\Backup;

use App\Models\AuditLog;
use App\Models\BackupHost;
use App\Services\Backup\MysqlClient;
use App\Services\Backup\SshTunnelService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BackupHostForm extends Component
{
    public ?int $hostId = null;

    public string $name = '';

    public bool $is_active = true;

    // SSH (optional)
    public bool $enable_ssh = false;

    public string $ssh_host = '';

    public int $ssh_port = 22;

    public string $ssh_user = '';

    public string $ssh_auth_method = 'key'; // 'key' | 'password'

    public string $ssh_key_path = '';

    public string $ssh_password = '';

    public ?string $ssh_connection_status = null;

    public string $ssh_connection_message = '';

    public array $sshRequirements = [];

    // MySQL service
    public bool $enable_mysql = false;

    public string $mysql_host = '127.0.0.1';

    public int $mysql_port = 3306;

    public string $mysql_user = 'root';

    public string $mysql_password = '';

    public array $mysql_available_databases = [];

    public ?string $mysql_connection_status = null;

    public string $mysql_connection_message = '';

    public bool $mysql_use_ssh = true;

    public bool $mysql_ssl = false;

    // PostgreSQL service
    public bool $enable_postgres = false;

    public string $postgres_host = '127.0.0.1';

    public int $postgres_port = 5432;

    public string $postgres_user = 'postgres';

    public string $postgres_password = '';

    public array $postgres_available_databases = [];

    public ?string $postgres_connection_status = null;

    public string $postgres_connection_message = '';

    public bool $postgres_use_ssh = true;

    // MongoDB service
    public bool $enable_mongodb = false;

    public string $mongodb_host = '127.0.0.1';

    public int $mongodb_port = 27017;

    public string $mongodb_user = '';

    public string $mongodb_password = '';

    public array $mongodb_available_databases = [];

    public ?string $mongodb_connection_status = null;

    public string $mongodb_connection_message = '';

    public bool $mongodb_use_ssh = true;

    // Filesystem capability
    public bool $enable_filesystem = false;

    public string $fs_transport = 'ssh'; // 'ssh' | 'ftp'

    public string $fs_ftp_host = '';

    public int $fs_ftp_port = 21;

    public string $fs_ftp_username = '';

    public string $fs_ftp_password = '';

    public string $fs_ftp_root_path = '/';

    public bool $fs_ftp_passive = true;

    public bool $fs_ftp_ssl = false;

    public ?string $fs_ftp_connection_status = null;

    public string $fs_ftp_connection_message = '';

    public function mount(?int $hostId = null): void
    {
        if ($hostId) {
            $this->hostId = $hostId;
            $host = BackupHost::findOrFail($hostId);
            $this->name = $host->name;
            $this->is_active = $host->is_active;

            $cfg = $host->config;

            $this->enable_ssh = isset($cfg['ssh']);
            if ($this->enable_ssh) {
                $ssh = $cfg['ssh'];
                $this->ssh_host = $ssh['host'] ?? '';
                $this->ssh_port = (int) ($ssh['port'] ?? 22);
                $this->ssh_user = $ssh['user'] ?? '';
                $this->ssh_auth_method = $ssh['auth_method'] ?? 'key';
                $this->ssh_key_path = $ssh['key_path'] ?? '';
                $this->ssh_password = $ssh['password'] ?? '';
            }

            $this->enable_mysql = isset($cfg['mysql']);
            if ($this->enable_mysql) {
                $mysql = $cfg['mysql'];
                $this->mysql_host = $mysql['host'] ?? '127.0.0.1';
                $this->mysql_port = (int) ($mysql['port'] ?? 3306);
                $this->mysql_user = $mysql['username'] ?? ($mysql['user'] ?? 'root');
                $this->mysql_password = $mysql['password'] ?? '';
                $this->mysql_use_ssh = $mysql['use_ssh'] ?? true;
                $this->mysql_ssl = $mysql['ssl'] ?? false;
            }

            $this->enable_postgres = isset($cfg['postgres']);
            if ($this->enable_postgres) {
                $postgres = $cfg['postgres'];
                $this->postgres_host = $postgres['host'] ?? '127.0.0.1';
                $this->postgres_port = (int) ($postgres['port'] ?? 5432);
                $this->postgres_user = $postgres['username'] ?? ($postgres['user'] ?? 'postgres');
                $this->postgres_password = $postgres['password'] ?? '';
                $this->postgres_use_ssh = $postgres['use_ssh'] ?? true;
            }

            $this->enable_mongodb = isset($cfg['mongodb']);
            if ($this->enable_mongodb) {
                $mongodb = $cfg['mongodb'];
                $this->mongodb_host = $mongodb['host'] ?? '127.0.0.1';
                $this->mongodb_port = (int) ($mongodb['port'] ?? 27017);
                $this->mongodb_user = $mongodb['username'] ?? ($mongodb['user'] ?? '');
                $this->mongodb_password = $mongodb['password'] ?? '';
                $this->mongodb_use_ssh = $mongodb['use_ssh'] ?? true;
            }

            $this->enable_filesystem = ! empty($cfg['filesystem']['enabled']);
            if ($this->enable_filesystem) {
                $fsCfg = $cfg['filesystem'];
                $this->fs_transport = $fsCfg['transport'] ?? 'ssh';
                if ($this->fs_transport === 'ftp') {
                    $ftp = $fsCfg['ftp'] ?? [];
                    $this->fs_ftp_host = $ftp['host'] ?? '';
                    $this->fs_ftp_port = (int) ($ftp['port'] ?? 21);
                    $this->fs_ftp_username = $ftp['username'] ?? '';
                    $this->fs_ftp_password = $ftp['password'] ?? '';
                    $this->fs_ftp_root_path = $ftp['root_path'] ?? '/';
                    $this->fs_ftp_passive = $ftp['passive'] ?? true;
                    $this->fs_ftp_ssl = $ftp['ssl'] ?? false;
                }
            }
        }

        $this->checkSshRequirements();
    }

    public function updatedSshAuthMethod(): void
    {
        $this->checkSshRequirements();
        $this->ssh_connection_status = null;
    }

    public function updatedEnableSsh(): void
    {
        $this->ssh_connection_status = null;
        $this->ssh_connection_message = '';
    }

    public function updatedFsTransport(): void
    {
        $this->fs_ftp_connection_status = null;
        $this->fs_ftp_connection_message = '';
    }

    public function rules(): array
    {
        $rules = [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('backup_hosts', 'name')->ignore($this->hostId),
            ],
            'is_active' => 'boolean',
        ];

        if ($this->enable_ssh) {
            $rules['ssh_host'] = 'required|string|max:255';
            $rules['ssh_port'] = 'required|integer|min:1|max:65535';
            $rules['ssh_user'] = 'required|string|max:255';
            $rules['ssh_auth_method'] = 'required|in:key,password';

            if ($this->ssh_auth_method === 'key') {
                $rules['ssh_key_path'] = 'required|string|max:500';
            } else {
                $rules['ssh_password'] = 'required|string|max:255';
            }
        }

        if ($this->enable_mysql) {
            $rules['mysql_host'] = 'required|string|max:255';
            $rules['mysql_port'] = 'required|integer|min:1|max:65535';
            $rules['mysql_user'] = 'required|string|max:255';
        }

        if ($this->enable_postgres) {
            $rules['postgres_host'] = 'required|string|max:255';
            $rules['postgres_port'] = 'required|integer|min:1|max:65535';
            $rules['postgres_user'] = 'required|string|max:255';
        }

        if ($this->enable_mongodb) {
            $rules['mongodb_host'] = 'required|string|max:255';
            $rules['mongodb_port'] = 'required|integer|min:1|max:65535';
        }

        if ($this->enable_filesystem && $this->fs_transport === 'ftp') {
            $rules['fs_ftp_host'] = 'required|string|max:255';
            $rules['fs_ftp_port'] = 'required|integer|min:1|max:65535';
            $rules['fs_ftp_username'] = 'required|string|max:255';
            $rules['fs_ftp_password'] = 'nullable|string|max:255';
            $rules['fs_ftp_root_path'] = 'string|max:500';
        }

        return $rules;
    }

    public function save(): void
    {
        if (! $this->enable_ssh && ! $this->enable_mysql && ! $this->enable_postgres && ! $this->enable_mongodb && ! $this->enable_filesystem) {
            $this->addError('enable_services', __('backup-host.at_least_one_service'));

            return;
        }

        $this->validate();

        $config = [];

        if ($this->enable_ssh) {
            $config['ssh'] = [
                'enabled' => true,
                'host' => $this->ssh_host,
                'port' => $this->ssh_port,
                'user' => $this->ssh_user,
                'auth_method' => $this->ssh_auth_method,
                'key_path' => $this->ssh_auth_method === 'key' ? $this->ssh_key_path : '',
                'password' => $this->ssh_auth_method === 'password' ? $this->ssh_password : '',
            ];
        }

        if ($this->enable_mysql) {
            $config['mysql'] = [
                'host' => $this->mysql_host,
                'port' => $this->mysql_port,
                'username' => $this->mysql_user,
                'password' => $this->mysql_password,
                'use_ssh' => $this->mysql_use_ssh,
                'ssl' => $this->mysql_ssl,
            ];
        }

        if ($this->enable_postgres) {
            $config['postgres'] = [
                'host' => $this->postgres_host,
                'port' => $this->postgres_port,
                'username' => $this->postgres_user,
                'password' => $this->postgres_password,
                'use_ssh' => $this->postgres_use_ssh,
            ];
        }

        if ($this->enable_mongodb) {
            $config['mongodb'] = [
                'host' => $this->mongodb_host,
                'port' => $this->mongodb_port,
                'username' => $this->mongodb_user,
                'password' => $this->mongodb_password,
                'use_ssh' => $this->mongodb_use_ssh,
            ];
        }

        if ($this->enable_filesystem) {
            $fs = ['enabled' => true, 'transport' => $this->fs_transport];
            if ($this->fs_transport === 'ftp') {
                $fs['ftp'] = [
                    'host' => $this->fs_ftp_host,
                    'port' => $this->fs_ftp_port,
                    'username' => $this->fs_ftp_username,
                    'password' => $this->fs_ftp_password,
                    'root_path' => $this->fs_ftp_root_path,
                    'passive' => $this->fs_ftp_passive,
                    'ssl' => $this->fs_ftp_ssl,
                ];
            }
            $config['filesystem'] = $fs;
        }

        $data = [
            'name' => $this->name,
            'config' => $config,
            'is_active' => $this->is_active,
        ];

        if ($this->hostId) {
            $host = BackupHost::findOrFail($this->hostId);
            $old = $host->toArray();
            $host->update($data);
            AuditLog::record('updated', "Updated backup host: {$host->name}", $host, $this->redactSecrets($old), $this->redactSecrets($data));
        } else {
            $host = BackupHost::create($data);
            AuditLog::record('created', "Created backup host: {$host->name}", $host, null, $this->redactSecrets($data));
        }

        $this->dispatch('host-saved');
    }

    private function redactSecrets(array $data): array
    {
        foreach (['password', 'key_path'] as $key) {
            if (! empty($data['config']['ssh'][$key] ?? null)) {
                $data['config']['ssh'][$key] = '••••••';
            }
        }

        foreach (['mysql', 'postgres', 'mongodb'] as $service) {
            if (! empty($data['config'][$service]['password'] ?? null)) {
                $data['config'][$service]['password'] = '••••••';
            }
        }

        if (! empty($data['config']['filesystem']['ftp']['password'] ?? null)) {
            $data['config']['filesystem']['ftp']['password'] = '••••••';
        }

        return $data;
    }

    public function testFtpConnection(): void
    {
        $this->fs_ftp_connection_status = null;
        $this->fs_ftp_connection_message = '';

        $ok = app(\App\Services\Backup\FtpStorageService::class)->testConnection([
            'host' => $this->fs_ftp_host,
            'port' => $this->fs_ftp_port,
            'username' => $this->fs_ftp_username,
            'password' => $this->fs_ftp_password,
            'root_path' => $this->fs_ftp_root_path,
            'passive' => $this->fs_ftp_passive,
            'ssl' => $this->fs_ftp_ssl,
        ]);

        $this->fs_ftp_connection_status = $ok ? 'success' : 'failed';
        $this->fs_ftp_connection_message = $ok ? __('backup-host.connection_success') : __('backup-host.connection_failed');
    }

    public function checkSshRequirements(): void
    {
        $ssh = trim((string) shell_exec('which ssh 2>/dev/null'));
        $sshpass = trim((string) shell_exec('which sshpass 2>/dev/null'));

        $os = PHP_OS_FAMILY;
        if ($os === 'Darwin') {
            $installSsh = 'brew install openssh';
            $installSshpass = 'brew install hudochenkov/sshpass/sshpass';
        } elseif (is_file('/etc/alpine-release')) {
            $installSsh = 'apk add openssh-client';
            $installSshpass = 'apk add sshpass';
        } else {
            $installSsh = 'sudo apt install openssh-client';
            $installSshpass = 'sudo apt install sshpass';
        }

        $this->sshRequirements = [
            'ssh' => [
                'ok' => $ssh !== '',
                'path' => $ssh ?: null,
                'install' => $installSsh,
            ],
            'sshpass' => [
                'ok' => $sshpass !== '',
                'path' => $sshpass ?: null,
                'install' => $installSshpass,
                'needed' => $this->ssh_auth_method === 'password',
            ],
        ];
    }

    public function testSshConnection(): void
    {
        $this->ssh_connection_status = null;
        $this->ssh_connection_message = '';

        try {
            $user = escapeshellarg($this->ssh_user);
            $host = escapeshellarg($this->ssh_host);
            $port = (int) $this->ssh_port;

            $baseOpts = "-o ConnectTimeout=8 -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR -p {$port}";

            if ($this->ssh_auth_method === 'password') {
                $pass = escapeshellarg($this->ssh_password);
                $cmd = "sshpass -p {$pass} ssh {$baseOpts} {$user}@{$host} exit 2>&1";
            } else {
                $keyPath = escapeshellarg($this->ssh_key_path);
                $cmd = "ssh {$baseOpts} -i {$keyPath} {$user}@{$host} exit 2>&1";
            }

            exec($cmd, $output, $exitCode);

            if ($exitCode === 0) {
                $this->ssh_connection_status = 'success';
                $this->ssh_connection_message = __('backup-host.ssh_test_success');
            } else {
                $detail = implode(' ', $output);
                $this->ssh_connection_status = 'failed';
                $this->ssh_connection_message = __('backup-host.ssh_test_failed').': '.$detail;
            }
        } catch (\Throwable $e) {
            $this->ssh_connection_status = 'failed';
            $this->ssh_connection_message = __('backup-host.ssh_test_failed').': '.$e->getMessage();
        }
    }

    /**
     * SSH config array for tunneling test connections, built from this form's own ssh_* props.
     */
    private function sshConfigArrayForTest(): ?array
    {
        if (! $this->enable_ssh) {
            return null;
        }

        return [
            'enabled' => true,
            'host' => $this->ssh_host,
            'port' => $this->ssh_port,
            'user' => $this->ssh_user,
            'auth_method' => $this->ssh_auth_method,
            'key_path' => $this->ssh_key_path,
            'password' => $this->ssh_password,
        ];
    }

    public function testMysqlConnection(): void
    {
        $this->mysql_available_databases = [];
        $this->mysql_connection_status = null;
        $this->mysql_connection_message = '';

        try {
            $run = function (string $host, int $port): void {
                $this->mysql_available_databases = MysqlClient::listDatabases([
                    'host' => $host,
                    'port' => $port,
                    'username' => $this->mysql_user,
                    'password' => $this->mysql_password,
                    'ssl' => $this->mysql_ssl,
                ]);
            };

            $sshConfig = $this->mysql_use_ssh ? $this->sshConfigArrayForTest() : null;
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
            $this->mysql_connection_message = __('backup-host.connection_success');
        } catch (\Throwable $e) {
            $this->mysql_connection_status = 'failed';
            $this->mysql_connection_message = __('backup-host.connection_failed').': '.$e->getMessage();
        }
    }

    public function testPostgresConnection(): void
    {
        $this->postgres_available_databases = [];
        $this->postgres_connection_status = null;
        $this->postgres_connection_message = '';

        try {
            $run = function (string $host, int $port): void {
                $pdo = new \PDO(
                    "pgsql:host={$host};port={$port};dbname=postgres",
                    $this->postgres_user,
                    $this->postgres_password,
                    [\PDO::ATTR_TIMEOUT => 5, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
                );
                $stmt = $pdo->query('SELECT datname FROM pg_database WHERE datistemplate = false');
                $allDatabases = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                $systemDbs = ['postgres'];
                $this->postgres_available_databases = array_values(array_diff($allDatabases, $systemDbs));
            };

            $sshConfig = $this->postgres_use_ssh ? $this->sshConfigArrayForTest() : null;
            if ($sshConfig) {
                app(SshTunnelService::class)->withTunnel(
                    $sshConfig,
                    $this->postgres_host,
                    $this->postgres_port,
                    fn (int $localPort) => $run('127.0.0.1', $localPort)
                );
            } else {
                $run($this->postgres_host, $this->postgres_port);
            }

            $this->postgres_connection_status = 'success';
            $this->postgres_connection_message = __('backup-host.connection_success');
        } catch (\Throwable $e) {
            $this->postgres_connection_status = 'failed';
            $this->postgres_connection_message = __('backup-host.connection_failed').': '.$e->getMessage();
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
                    if ($this->mongodb_user) {
                        $uri .= urlencode($this->mongodb_user).':'.urlencode($this->mongodb_password).'@';
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
                    if ($this->mongodb_user) {
                        $auth = sprintf(
                            '-u %s -p %s --authenticationDatabase admin',
                            escapeshellarg($this->mongodb_user),
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

            $sshConfig = $this->mongodb_use_ssh ? $this->sshConfigArrayForTest() : null;
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
            $this->mongodb_connection_message = __('backup-host.connection_success');
        } catch (\Throwable $e) {
            $this->mongodb_connection_status = 'failed';
            $this->mongodb_connection_message = __('backup-host.connection_failed').': '.$e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.backup.backup-host-form');
    }
}
