<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo('model');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeOfModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    // ── Helpers ────────────────────────────────────────────────

    public function getModelLabelAttribute(): string
    {
        if (! $this->model_type) {
            return '-';
        }

        return class_basename($this->model_type) . ' #' . $this->model_id;
    }

    /**
     * Create an audit log entry.
     */
    public static function record(
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): static {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? $model->getMorphClass() : null,
            'model_id' => $model?->getKey(),
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
