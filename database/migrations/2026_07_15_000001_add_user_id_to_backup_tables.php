<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'backup_sources',
        'backup_storage_destinations',
        'backup_jobs',
        'backup_logs',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        // Backfill: existing global rows belong to the first (seeded admin) user.
        // Pre-migration every row was global, so logs share that same owner.
        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        if ($firstUserId) {
            foreach ($this->tables as $table) {
                DB::table($table)->update(['user_id' => $firstUserId]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('user_id');
            });
        }
    }
};
