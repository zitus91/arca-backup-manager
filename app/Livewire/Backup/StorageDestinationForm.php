<?php

namespace App\Livewire\Backup;

use App\Models\AuditLog;
use App\Models\BackupStorageDestination;
use App\Services\Backup\FtpStorageService;
use App\Services\Backup\S3StorageService;
use Livewire\Component;

class StorageDestinationForm extends Component
{
    public ?int $destinationId = null;

    public string $name = '';
    public string $type = 's3';
    public bool $is_active = true;

    // S3 fields
    public string $s3_bucket = '';
    public string $s3_region = '';
    public string $s3_access_key = '';
    public string $s3_secret_key = '';
    public string $s3_endpoint = '';
    public ?string $s3_connection_status = null;
    public string $s3_connection_message = '';

    // FTP fields
    public string $ftp_host = '';
    public int $ftp_port = 21;
    public string $ftp_username = '';
    public string $ftp_password = '';
    public string $ftp_root_path = '/';
    public bool $ftp_passive = true;
    public bool $ftp_ssl = true;
    public ?string $ftp_connection_status = null;
    public string $ftp_connection_message = '';

    // Local fields
    public string $local_path = '';
    public ?string $local_path_status = null;
    public string $local_path_message = '';

    public function mount(?int $destinationId = null): void
    {
        if ($destinationId) {
            $this->destinationId = $destinationId;
            $destination = BackupStorageDestination::findOrFail($destinationId);
            $this->name = $destination->name;
            $this->type = $destination->type;
            $this->is_active = $destination->is_active;

            $config = $destination->config;

            match ($destination->type) {
                's3' => $this->fillS3($config),
                'ftp' => $this->fillFtp($config),
                'local' => $this->fillLocal($config),
                default => null,
            };
        }
    }

    protected function fillS3(array $cfg): void
    {
        $this->s3_bucket = $cfg['bucket'] ?? '';
        $this->s3_region = $cfg['region'] ?? '';
        $this->s3_access_key = $cfg['access_key'] ?? '';
        $this->s3_secret_key = $cfg['secret_key'] ?? '';
        $this->s3_endpoint = $cfg['endpoint'] ?? '';
    }

    protected function fillFtp(array $cfg): void
    {
        $this->ftp_host = $cfg['host'] ?? '';
        $this->ftp_port = $cfg['port'] ?? 21;
        $this->ftp_username = $cfg['username'] ?? '';
        $this->ftp_password = $cfg['password'] ?? '';
        $this->ftp_root_path = $cfg['root_path'] ?? '/';
        $this->ftp_passive = $cfg['passive'] ?? true;
        $this->ftp_ssl = $cfg['ssl'] ?? true;
    }

    protected function fillLocal(array $cfg): void
    {
        $this->local_path = $cfg['path'] ?? '';
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:s3,ftp,local',
            'is_active' => 'boolean',
        ];

        match ($this->type) {
            's3' => $rules = array_merge($rules, [
                's3_bucket' => 'required|string|max:255',
                's3_region' => 'nullable|string|max:255',
                's3_access_key' => 'required|string|max:255',
                's3_secret_key' => 'required|string|max:255',
                's3_endpoint' => 'nullable|string|max:500',
            ]),
            'ftp' => $rules = array_merge($rules, [
                'ftp_host' => 'required|string|max:255',
                'ftp_port' => 'required|integer|min:1|max:65535',
                'ftp_username' => 'required|string|max:255',
                'ftp_password' => 'required|string|max:255',
                'ftp_root_path' => 'required|string|max:500',
                'ftp_passive' => 'boolean',
                'ftp_ssl' => 'boolean',
            ]),
            'local' => $rules = array_merge($rules, [
                'local_path' => 'required|string|max:500',
            ]),
            default => null,
        };

        return $rules;
    }

    // ── S3 Connection Test ─────────────────────────────────────

    public function testS3Connection(): void
    {
        $this->s3_connection_status = null;
        $this->s3_connection_message = '';

        $config = [
            'bucket' => $this->s3_bucket,
            'region' => $this->s3_region,
            'access_key' => $this->s3_access_key,
            'secret_key' => $this->s3_secret_key,
            'endpoint' => $this->s3_endpoint ?: null,
        ];

        try {
            app(S3StorageService::class)->testConnection($config);

            $this->s3_connection_status = 'success';
            $this->s3_connection_message = __('backup-storage-destination.s3_test_success');
        } catch (\Throwable $e) {
            $this->s3_connection_status = 'failed';
            $this->s3_connection_message = $e->getMessage();
        }
    }

    // ── FTP Connection Test ────────────────────────────────────

    public function testFtpConnection(): void
    {
        $this->ftp_connection_status = null;
        $this->ftp_connection_message = '';

        $config = [
            'host' => $this->ftp_host,
            'port' => $this->ftp_port,
            'username' => $this->ftp_username,
            'password' => $this->ftp_password,
            'root_path' => $this->ftp_root_path,
            'passive' => $this->ftp_passive,
            'ssl' => $this->ftp_ssl,
        ];

        try {
            $success = app(FtpStorageService::class)->testConnection($config);

            if ($success) {
                $this->ftp_connection_status = 'success';
                $this->ftp_connection_message = __('backup-storage-destination.ftp_test_success');
            } else {
                $this->ftp_connection_status = 'failed';
                $this->ftp_connection_message = __('backup-storage-destination.ftp_test_failed');
            }
        } catch (\Throwable $e) {
            $this->ftp_connection_status = 'failed';
            $this->ftp_connection_message = $e->getMessage();
        }
    }

    // ── Local Path Check ───────────────────────────────────────

    public function checkLocalPath(): void
    {
        $this->local_path_status = null;
        $this->local_path_message = '';

        $path = trim($this->local_path);

        if (empty($path)) {
            $this->local_path_status = 'failed';
            $this->local_path_message = __('backup-storage-destination.local_path_empty');
            return;
        }

        if (is_dir($path)) {
            if (is_writable($path)) {
                $freeBytes = disk_free_space($path);
                $freeFormatted = $freeBytes !== false ? $this->formatBytes($freeBytes) : '?';
                $this->local_path_status = 'success';
                $this->local_path_message = __('backup-storage-destination.local_path_exists', ['free' => $freeFormatted]);
            } else {
                $this->local_path_status = 'failed';
                $this->local_path_message = __('backup-storage-destination.local_path_not_writable');
            }
        } else {
            $this->local_path_status = 'failed';
            $this->local_path_message = __('backup-storage-destination.local_path_not_found');
        }
    }

    protected function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    // ── Save ───────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate();

        $config = match ($this->type) {
            's3' => [
                'bucket' => $this->s3_bucket,
                'region' => $this->s3_region,
                'access_key' => $this->s3_access_key,
                'secret_key' => $this->s3_secret_key,
                'endpoint' => $this->s3_endpoint ?: null,
            ],
            'ftp' => [
                'host' => $this->ftp_host,
                'port' => $this->ftp_port,
                'username' => $this->ftp_username,
                'password' => $this->ftp_password,
                'root_path' => $this->ftp_root_path,
                'passive' => $this->ftp_passive,
                'ssl' => $this->ftp_ssl,
            ],
            'local' => [
                'path' => $this->local_path,
            ],
        };

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'config' => $config,
            'is_active' => $this->is_active,
        ];

        if ($this->destinationId) {
            $dest = BackupStorageDestination::findOrFail($this->destinationId);
            $oldValues = $dest->toArray();
            $dest->update($data);
            AuditLog::record('updated', "Updated storage destination: {$dest->name}", $dest, $oldValues, $data);
        } else {
            $dest = BackupStorageDestination::create($data);
            AuditLog::record('created', "Created storage destination: {$dest->name}", $dest, null, $data);
        }

        $this->dispatch('destination-saved');
    }

    public function render()
    {
        return view('livewire.backup.storage-destination-form');
    }
}
