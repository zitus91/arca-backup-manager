<?php

namespace App\Models;

use App\Trait\HasCache;
use App\Trait\OwnedByUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // ── Accessors ────────────────────────────────────────────

    public function offers(string $type): bool
    {
        $svc = $this->config[$type] ?? null;

        if ($type === 'filesystem') {
            return is_array($svc) && ! empty($svc['enabled']);
        }

        return is_array($svc);
    }

    public function sshConfig(): array
    {
        return $this->config['ssh'] ?? ['enabled' => false];
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
