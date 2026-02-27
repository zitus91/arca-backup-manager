<?php

namespace App\Models;

use App\Trait\HasCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    use HasCache, HasFactory;

    protected $fillable = [
        'backup_job_id',
        'status',
        'started_at',
        'finished_at',
        'duration_seconds',
        'file_name',
        'file_size_bytes',
        'storage_path',
        'error_message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_seconds' => 'integer',
            'file_size_bytes' => 'integer',
            'meta' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function job(): BelongsTo
    {
        return $this->belongsTo(BackupJob::class, 'backup_job_id');
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

        return round($size, 2) . ' ' . $units[$i];
    }

    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration_seconds) {
            return '-';
        }

        if ($this->duration_seconds < 60) {
            return $this->duration_seconds . 's';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return "{$minutes}m {$seconds}s";
    }
}
