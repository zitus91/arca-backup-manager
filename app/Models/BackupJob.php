<?php

namespace App\Models;

use App\Trait\HasCache;
use App\Trait\OwnedByUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BackupJob extends Model
{
    use HasCache, HasFactory, OwnedByUser;

    protected $fillable = [
        'user_id',
        'name',
        'backup_source_id',
        'backup_storage_destination_id',
        'schedule_type',
        'schedule_cron',
        'schedule_time',
        'schedule_day_of_week',
        'schedule_day_of_month',
        'retention_count',
        'compression',
        'backup_type',
        'full_backup_every',
        'notify_on_success',
        'notify_on_failure',
        'notification_emails',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'schedule_time' => 'string',
            'schedule_day_of_week' => 'integer',
            'schedule_day_of_month' => 'integer',
            'retention_count' => 'integer',
            'full_backup_every' => 'integer',
            'notify_on_success' => 'boolean',
            'notify_on_failure' => 'boolean',
            'notification_emails' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(BackupSource::class, 'backup_source_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(BackupStorageDestination::class, 'backup_storage_destination_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BackupLog::class);
    }

    public function latestLog(): HasOne
    {
        return $this->hasOne(BackupLog::class)->latestOfMany();
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->whereDoesntHave('logs', function ($q) {
                $q->whereIn('status', ['pending', 'running']);
            });
    }

    public function scopeOfScheduleType($query, string $type)
    {
        return $query->where('schedule_type', $type);
    }
}
