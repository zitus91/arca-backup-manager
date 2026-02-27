<?php

namespace App\Livewire\Backup;

use App\Models\AuditLog;
use App\Models\BackupJob;
use App\Models\BackupSource;
use App\Models\BackupStorageDestination;
use App\Services\Backup\BackupSchedulerService;
use Livewire\Component;

class BackupJobForm extends Component
{
    public ?int $jobId = null;

    public string $name = '';
    public string $backup_source_id = '';
    public string $backup_storage_destination_id = '';
    public string $schedule_type = 'manual';
    public string $schedule_cron = '';
    public string $schedule_time = '03:00';
    public string $schedule_day_of_week = '';
    public string $schedule_day_of_month = '';
    public int $retention_count = 7;
    public string $compression = 'gzip';
    public bool $notify_on_success = false;
    public bool $notify_on_failure = true;
    public string $notification_email = '';
    public bool $is_active = true;

    public string $cronPreview = '';

    public function mount(?int $jobId = null): void
    {
        if ($jobId) {
            $this->jobId = $jobId;
            $job = BackupJob::findOrFail($jobId);
            $this->name = $job->name;
            $this->backup_source_id = (string) $job->backup_source_id;
            $this->backup_storage_destination_id = (string) $job->backup_storage_destination_id;
            $this->schedule_type = $job->schedule_type;
            $this->schedule_cron = $job->schedule_cron ?? '';
            $this->schedule_time = $job->schedule_time ?? '03:00';
            $this->schedule_day_of_week = $job->schedule_day_of_week !== null ? (string) $job->schedule_day_of_week : '';
            $this->schedule_day_of_month = $job->schedule_day_of_month !== null ? (string) $job->schedule_day_of_month : '';
            $this->retention_count = $job->retention_count;
            $this->compression = $job->compression;
            $this->notify_on_success = $job->notify_on_success;
            $this->notify_on_failure = $job->notify_on_failure;
            $this->notification_email = $job->notification_email ?? '';
            $this->is_active = $job->is_active;
        }
    }

    public function updatedScheduleCron(): void
    {
        if ($this->schedule_cron) {
            $this->cronPreview = app(BackupSchedulerService::class)->describeCron($this->schedule_cron);
        }
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'backup_source_id' => 'required|exists:backup_sources,id',
            'backup_storage_destination_id' => 'required|exists:backup_storage_destinations,id',
            'schedule_type' => 'required|in:manual,hourly,daily,weekly,monthly,custom',
            'retention_count' => 'required|integer|min:1|max:365',
            'compression' => 'required|in:none,gzip,zip',
            'notify_on_success' => 'boolean',
            'notify_on_failure' => 'boolean',
            'notification_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ];

        if (in_array($this->schedule_type, ['daily', 'weekly', 'monthly'])) {
            $rules['schedule_time'] = 'required|date_format:H:i';
        }

        if ($this->schedule_type === 'weekly') {
            $rules['schedule_day_of_week'] = 'required|integer|min:0|max:6';
        }

        if ($this->schedule_type === 'monthly') {
            $rules['schedule_day_of_month'] = 'required|integer|min:1|max:31';
        }

        if ($this->schedule_type === 'custom') {
            $rules['schedule_cron'] = 'required|string|max:100';
        }

        if ($this->notify_on_success || $this->notify_on_failure) {
            $rules['notification_email'] = 'required|email|max:255';
        }

        return $rules;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'backup_source_id' => $this->backup_source_id,
            'backup_storage_destination_id' => $this->backup_storage_destination_id,
            'schedule_type' => $this->schedule_type,
            'schedule_cron' => $this->schedule_type === 'custom' ? $this->schedule_cron : null,
            'schedule_time' => in_array($this->schedule_type, ['daily', 'weekly', 'monthly']) ? $this->schedule_time : null,
            'schedule_day_of_week' => $this->schedule_type === 'weekly' ? (int) $this->schedule_day_of_week : null,
            'schedule_day_of_month' => $this->schedule_type === 'monthly' ? (int) $this->schedule_day_of_month : null,
            'retention_count' => $this->retention_count,
            'compression' => $this->compression,
            'notify_on_success' => $this->notify_on_success,
            'notify_on_failure' => $this->notify_on_failure,
            'notification_email' => ($this->notify_on_success || $this->notify_on_failure) ? $this->notification_email : null,
            'is_active' => $this->is_active,
        ];

        if ($this->jobId) {
            $job = BackupJob::findOrFail($this->jobId);
            $oldValues = $job->toArray();
            $job->update($data);
            AuditLog::record('updated', "Updated backup job: {$job->name}", $job, $oldValues, $data);
        } else {
            $job = BackupJob::create($data);
            AuditLog::record('created', "Created backup job: {$job->name}", $job, null, $data);
        }

        // Calculate and set next_run_at
        $scheduler = app(BackupSchedulerService::class);
        $nextRun = $scheduler->calculateNextRun($job);
        $job->update(['next_run_at' => $nextRun]);

        $this->dispatch('job-saved');
    }

    public function render()
    {
        return view('livewire.backup.backup-job-form', [
            'sources' => BackupSource::active()->get(),
            'destinations' => BackupStorageDestination::active()->get(),
        ]);
    }
}
