<?php

use App\Models\BackupHost;
use App\Models\BackupSource;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Iterate all sources without global scopes (migration runs unauthenticated).
        BackupSource::withoutGlobalScopes()->whereNull('host_id')->get()->each(function (BackupSource $source) {
            $ssh = $source->config['ssh'] ?? [];

            if (empty($ssh['enabled'])) {
                return;
            }

            $host = $ssh['host'] ?? '';
            $port = (int) ($ssh['port'] ?? 22);
            $user = $ssh['user'] ?? '';

            if ($host === '') {
                return;
            }

            // Dedup by (host, port, user) among already-created hosts.
            $existing = BackupHost::withoutGlobalScopes()->get()->first(function (BackupHost $h) use ($host, $port, $user) {
                return ($h->config['host'] ?? null) === $host
                    && (int) ($h->config['port'] ?? 22) === $port
                    && ($h->config['user'] ?? '') === $user;
            });

            if (! $existing) {
                $existing = BackupHost::create([
                    'user_id' => $source->user_id,
                    'name' => $this->uniqueName("{$user}@{$host}:{$port}"),
                    'config' => [
                        'host' => $host,
                        'port' => $port,
                        'user' => $user,
                        'auth_method' => $ssh['auth_method'] ?? 'key',
                        'key_path' => $ssh['key_path'] ?? '',
                        'password' => $ssh['password'] ?? '',
                    ],
                    'is_active' => true,
                ]);
            }

            $source->forceFill(['host_id' => $existing->id])->saveQuietly();
        });
    }

    private function uniqueName(string $base): string
    {
        $name = $base;
        $i = 2;
        while (BackupHost::withoutGlobalScopes()->where('name', $name)->exists()) {
            $name = "{$base} ({$i})";
            $i++;
        }

        return $name;
    }

    public function down(): void
    {
        // Non-destructive back-fill; nothing to reverse.
    }
};
