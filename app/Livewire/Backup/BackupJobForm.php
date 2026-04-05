<?php

namespace App\Livewire\Backup;

use App\Mail\BackupTestMail;
use App\Models\AuditLog;
use App\Models\BackupJob;
use App\Models\BackupSource;
use App\Models\BackupStorageDestination;
use App\Models\User;
use App\Services\Backup\BackupSchedulerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
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
    public string $backup_type = 'full';
    public string $full_backup_every = '';
    public bool $notify_on_success = false;
    public bool $notify_on_failure = true;
    /** @var string[] */
    public array $notification_emails = [];
    public string $newEmail = '';
    public bool $is_active = true;

    public string $cronPreview = '';

    /** 'idle' | 'sending' | 'success' | 'error' */
    public string $testEmailState = 'idle';

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
            $this->backup_type = $job->backup_type ?? 'full';
            $this->full_backup_every = $job->full_backup_every !== null ? (string) $job->full_backup_every : '';
            $this->notify_on_success = $job->notify_on_success;
            $this->notify_on_failure = $job->notify_on_failure;
            $this->notification_emails = $job->notification_emails ?? [];
            $this->is_active = $job->is_active;
        } else {
            // Default: pre-fill with the logged-in user's email
            $this->notification_emails = [Auth::user()->email];
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
            'backup_type' => 'required|in:full,incremental',
            'notify_on_success' => 'boolean',
            'notify_on_failure' => 'boolean',
            'notification_emails' => 'nullable|array',
            'notification_emails.*' => 'email|max:255',
            'newEmail' => 'nullable|email|max:255',
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

        if ($this->backup_type === 'incremental' && $this->full_backup_every !== '') {
            $rules['full_backup_every'] = 'required|integer|min:1|max:365';
        }

        if ($this->notify_on_success || $this->notify_on_failure) {
            $rules['notification_emails'] = 'required|array|min:1';
            $rules['notification_emails.*'] = 'email|max:255';
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
            'backup_type' => $this->backup_type,
            'full_backup_every' => $this->backup_type === 'incremental' && $this->full_backup_every !== '' ? (int) $this->full_backup_every : null,
            'notify_on_success' => $this->notify_on_success,
            'notify_on_failure' => $this->notify_on_failure,
            'notification_emails' => ($this->notify_on_success || $this->notify_on_failure) ? array_values($this->notification_emails) : null,
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

    public function sendTestEmail(): void
    {
        $this->validate([
            'notification_emails' => 'required|array|min:1',
            'notification_emails.*' => 'email|max:255',
        ]);

        $this->testEmailState = 'sending';

        try {
            $jobName = $this->name ?: __('backup-job.test_job_name_fallback');

            foreach ($this->notification_emails as $email) {
                Mail::to($email)->send(new BackupTestMail($jobName, $email));
            }

            $this->testEmailState = 'success';
            $this->dispatch('test-email-result', status: 'success', message: __('backup-job.test_email_sent'));
        } catch (\Throwable $e) {
            $this->testEmailState = 'error';
            $this->dispatch('test-email-result', status: 'error', message: __('backup-job.test_email_failed'));
        }
    }

    public function addEmail(): void
    {
        $this->validateOnly('newEmail', [
            'newEmail' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($this->newEmail));

        if (! in_array($email, array_map('strtolower', $this->notification_emails))) {
            $this->notification_emails[] = $email;
        }

        $this->newEmail = '';
        $this->testEmailState = 'idle';
    }

    public function removeEmail(int $index): void
    {
        unset($this->notification_emails[$index]);
        $this->notification_emails = array_values($this->notification_emails);
        $this->testEmailState = 'idle';
    }

    #[Computed]
    public function registeredUsers(): array
    {
        return User::orderBy('name')->get(['name', 'email'])->toArray();
    }

    public function render()
    {
        return view('livewire.backup.backup-job-form', [
            'sources' => BackupSource::active()->get(),
            'destinations' => BackupStorageDestination::active()->get(),
        ]);
    }
}
