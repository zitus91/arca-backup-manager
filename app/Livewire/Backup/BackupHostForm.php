<?php

namespace App\Livewire\Backup;

use App\Models\AuditLog;
use App\Models\BackupHost;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BackupHostForm extends Component
{
    public ?int $hostId = null;

    public string $name = '';

    public bool $is_active = true;

    public string $ssh_host = '';

    public int $ssh_port = 22;

    public string $ssh_user = '';

    public string $ssh_auth_method = 'key'; // 'key' | 'password'

    public string $ssh_key_path = '';

    public string $ssh_password = '';

    public ?string $ssh_connection_status = null;

    public string $ssh_connection_message = '';

    public array $sshRequirements = [];

    public function mount(?int $hostId = null): void
    {
        if ($hostId) {
            $this->hostId = $hostId;
            $host = BackupHost::findOrFail($hostId);
            $this->name = $host->name;
            $this->is_active = $host->is_active;

            $cfg = $host->config;
            $this->ssh_host = $cfg['host'] ?? '';
            $this->ssh_port = (int) ($cfg['port'] ?? 22);
            $this->ssh_user = $cfg['user'] ?? '';
            $this->ssh_auth_method = $cfg['auth_method'] ?? 'key';
            $this->ssh_key_path = $cfg['key_path'] ?? '';
            $this->ssh_password = $cfg['password'] ?? '';
        }

        $this->checkSshRequirements();
    }

    public function updatedSshAuthMethod(): void
    {
        $this->checkSshRequirements();
        $this->ssh_connection_status = null;
    }

    public function rules(): array
    {
        $rules = [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('backup_hosts', 'name')->ignore($this->hostId),
            ],
            'is_active' => 'boolean',
            'ssh_host' => 'required|string|max:255',
            'ssh_port' => 'required|integer|min:1|max:65535',
            'ssh_user' => 'required|string|max:255',
            'ssh_auth_method' => 'required|in:key,password',
        ];

        if ($this->ssh_auth_method === 'key') {
            $rules['ssh_key_path'] = 'required|string|max:500';
        } else {
            $rules['ssh_password'] = 'required|string|max:255';
        }

        return $rules;
    }

    public function save(): void
    {
        $this->validate();

        $config = [
            'host' => $this->ssh_host,
            'port' => $this->ssh_port,
            'user' => $this->ssh_user,
            'auth_method' => $this->ssh_auth_method,
            'key_path' => $this->ssh_auth_method === 'key' ? $this->ssh_key_path : '',
            'password' => $this->ssh_auth_method === 'password' ? $this->ssh_password : '',
        ];

        $data = [
            'name' => $this->name,
            'config' => $config,
            'is_active' => $this->is_active,
        ];

        if ($this->hostId) {
            $host = BackupHost::findOrFail($this->hostId);
            $old = $host->toArray();
            $host->update($data);
            AuditLog::record('updated', "Updated backup host: {$host->name}", $host, $old, $data);
        } else {
            $host = BackupHost::create($data);
            AuditLog::record('created', "Created backup host: {$host->name}", $host, null, $data);
        }

        $this->dispatch('host-saved');
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

    public function render()
    {
        return view('livewire.backup.backup-host-form');
    }
}
