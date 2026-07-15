<?php

namespace App\Models;

use App\Trait\HasCache;
use App\Trait\OwnedByUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    use HasCache, HasFactory, OwnedByUser;

    protected $fillable = [
        'user_id',
        'backup_job_id',
        'parent_backup_log_id',
        'is_full',
        'status',
        'is_locked',
        'locked_at',
        'locked_by',
        'started_at',
        'finished_at',
        'duration_seconds',
        'file_name',
        'file_size_bytes',
        'storage_path',
        'error_message',
        'meta',
        'incremental_checkpoint',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'locked_at' => 'datetime',
            'duration_seconds' => 'integer',
            'file_size_bytes' => 'integer',
            'is_full' => 'boolean',
            'is_locked' => 'boolean',
            'meta' => 'array',
            'incremental_checkpoint' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function job(): BelongsTo
    {
        return $this->belongsTo(BackupJob::class, 'backup_job_id');
    }

    public function parentBackup(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_backup_log_id');
    }

    public function childBackups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_backup_log_id');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOfJob($query, int $jobId)
    {
        return $query->where('backup_job_id', $jobId);
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('started_at', [$from, $to]);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    // ── Helpers ────────────────────────────────────────────────

    public function getFormattedSizeAttribute(): string
    {
        if (! $this->file_size_bytes) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->file_size_bytes;
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2).' '.$units[$i];
    }

    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration_seconds) {
            return '-';
        }

        if ($this->duration_seconds < 60) {
            return $this->duration_seconds.'s';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return "{$minutes}m {$seconds}s";
    }
}
