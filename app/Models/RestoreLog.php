<?php

namespace App\Models;

use App\Trait\HasCache;
use App\Trait\OwnedByUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestoreLog extends Model
{
    use HasCache, HasFactory, OwnedByUser;

    protected $fillable = [
        'backup_log_id',
        'user_id',
        'restore_type',
        'restore_target',
        'remote_host_config',
        'custom_names',
        'override_existing',
        'selected_items',
        'status',
        'started_at',
        'finished_at',
        'duration_seconds',
        'restored_db_name',
        'restored_path',
        'error_message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_seconds' => 'integer',
            'meta' => 'array',
            'selected_items' => 'array',
            'remote_host_config' => 'encrypted:array',
            'custom_names' => 'array',
            'override_existing' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function backupLog(): BelongsTo
    {
        return $this->belongsTo(BackupLog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration_seconds) {
            return '-';
        }

        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }

    public function getRestoreTypeLabelAttribute(): string
    {
        return match ($this->restore_type) {
            'db_only' => 'Solo Database',
            'files_only' => 'Solo File',
            'full' => 'Completo',
            default => $this->restore_type,
        };
    }
}
