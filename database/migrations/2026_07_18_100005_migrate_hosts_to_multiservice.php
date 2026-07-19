<?php

use App\Models\BackupHost;
use App\Models\BackupSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Flatten legacy flat-ssh host rows to nested {ssh: {...}}.
        BackupHost::withoutGlobalScopes()->get()->each(function (BackupHost $h) {
            $cfg = $h->config;
            if (isset($cfg['host']) && ! isset($cfg['ssh']) && ! isset($cfg['mysql']) && ! isset($cfg['mongodb']) && ! isset($cfg['filesystem'])) {
                $h->forceFill(['config' => ['ssh' => array_merge(['enabled' => true], $cfg)]])->saveQuietly();
            }
        });

        // 2) Per-type backfill from legacy inline source creds.
        BackupSource::withoutGlobalScopes()->get()->each(function (BackupSource $source) {
            $config = $source->config;
            $ssh = $this->resolveLegacySsh($source);
            $changed = false;

            foreach (['mysql', 'mongodb'] as $type) {
                $svc = $config[$type] ?? null;
                if (! is_array($svc) || ! isset($svc['host']) || $source->{$type.'_host_id'}) {
                    continue; // no inline creds, or already linked
                }
                $creds = ['host' => $svc['host'], 'port' => $svc['port'] ?? null, 'username' => $svc['username'] ?? ($svc['user'] ?? ''), 'password' => $svc['password'] ?? ''];
                $host = $this->findOrCreateHost($source->user_id, $type, $creds, $ssh);
                $source->{$type.'_host_id'} = $host->id;
                $config[$type] = ['databases' => $svc['databases'] ?? (isset($svc['database']) ? [$svc['database']] : [])];
                $changed = true;
            }

            $fs = $config['filesystem'] ?? null;
            if (is_array($fs) && ! $source->filesystem_host_id && ($ssh['enabled'] ?? false)) {
                $host = $this->findOrCreateHost($source->user_id, 'filesystem', [], $ssh);
                $source->filesystem_host_id = $host->id;
                $config['filesystem'] = ['paths' => $fs['paths'] ?? (isset($fs['path']) ? [$fs['path']] : []), 'exclude_patterns' => $fs['exclude_patterns'] ?? ''];
                $changed = true;
            }

            if ($changed) {
                $source->forceFill(['config' => $config])->saveQuietly();
            }
        });

        // 3) Drop the obsolete single host_id column.
        if (Schema::hasColumn('backup_sources', 'host_id')) {
            Schema::table('backup_sources', function (Blueprint $table) {
                $table->dropForeign(['host_id']);
                $table->dropColumn('host_id');
            });
        }
    }

    private function resolveLegacySsh(BackupSource $source): array
    {
        // Prefer a linked legacy ssh host (already flattened in step 1), else inline config['ssh'].
        if ($source->host_id) {
            $h = BackupHost::withoutGlobalScopes()->find($source->host_id);
            if ($h) {
                return $h->config['ssh'] ?? ['enabled' => false];
            }
        }
        $inline = $source->config['ssh'] ?? null;

        return (is_array($inline) && ! empty($inline['enabled'])) ? $inline : ['enabled' => false];
    }

    private function findOrCreateHost(?int $userId, string $type, array $creds, array $ssh): BackupHost
    {
        $existing = BackupHost::withoutGlobalScopes()->get()->first(function (BackupHost $h) use ($type, $creds, $ssh) {
            $svc = $h->config[$type] ?? null;
            $hostSsh = $h->config['ssh'] ?? ['enabled' => false];
            $sameSsh = ($hostSsh['host'] ?? null) === ($ssh['host'] ?? null) && (int) ($hostSsh['port'] ?? 22) === (int) ($ssh['port'] ?? 22) && ($hostSsh['user'] ?? '') === ($ssh['user'] ?? '');
            if ($type === 'filesystem') {
                return ! empty($h->config['filesystem']['enabled']) && $sameSsh;
            }

            return is_array($svc) && ($svc['host'] ?? null) === ($creds['host'] ?? null) && (int) ($svc['port'] ?? 0) === (int) ($creds['port'] ?? 0) && ($svc['username'] ?? '') === ($creds['username'] ?? '') && $sameSsh;
        });

        if ($existing) {
            return $existing;
        }

        $config = [];
        if (! empty($ssh['enabled'])) {
            $config['ssh'] = array_merge(['enabled' => true], $ssh);
        }
        if ($type === 'filesystem') {
            $config['filesystem'] = ['enabled' => true];
        } else {
            $config[$type] = $creds;
        }

        return BackupHost::create([
            'user_id' => $userId,
            'name' => $this->uniqueName(($ssh['user'] ?? $type).'@'.($ssh['host'] ?? ($creds['host'] ?? 'local')).' ('.$type.')'),
            'config' => $config,
            'is_active' => true,
        ]);
    }

    private function uniqueName(string $base): string
    {
        $name = $base;
        $i = 2;
        while (BackupHost::withoutGlobalScopes()->where('name', $name)->exists()) {
            $name = $base.' ('.$i.')';
            $i++;
        }

        return $name;
    }

    public function down(): void
    {
        // Non-destructive forward migration; no reverse.
    }
};
