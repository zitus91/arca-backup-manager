<?php

namespace App\Models;

use App\Trait\HasCache;
use App\Trait\OwnedByUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupHost extends Model
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

    public function backupSources(): HasMany
    {
        return $this->hasMany(BackupSource::class, 'host_id');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
