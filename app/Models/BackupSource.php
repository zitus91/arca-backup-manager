<?php

namespace App\Models;

use App\Trait\HasCache;
use App\Trait\OwnedByUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupSource extends Model
{
    use HasCache, HasFactory, OwnedByUser;

    protected $fillable = [
        'user_id',
        'name',
        'config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function backupJobs(): HasMany
    {
        return $this->hasMany(BackupJob::class);
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getEnabledTypesAttribute(): array
    {
        return array_values(array_intersect(
            array_keys($this->config ?? []),
            ['mysql', 'mongodb', 'filesystem']
        ));
    }

    public function hasType(string $type): bool
    {
        return isset($this->config[$type]);
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
