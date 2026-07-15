<?php

use App\Models\BackupSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing records: wrap flat config in type key
        if (Schema::hasColumn('backup_sources', 'type')) {
            foreach (BackupSource::all() as $source) {
                $rawType = $source->getAttributes()['type'] ?? null;
                $config = $source->config;

                if ($rawType && is_array($config) && ! isset($config['mysql']) && ! isset($config['mongodb']) && ! isset($config['filesystem'])) {
                    $source->forceFill(['config' => [$rawType => $config]])->saveQuietly();
                }
            }

            Schema::table('backup_sources', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        // Make name unique (package name)
        $indexes = Schema::getIndexes('backup_sources');
        $hasUnique = collect($indexes)->contains(fn ($idx) => $idx['columns'] === ['name'] && $idx['unique']);

        if (! $hasUnique) {
            Schema::table('backup_sources', function (Blueprint $table) {
                $table->unique('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('backup_sources', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->string('type')->default('mysql')->after('name');
        });
    }
};
