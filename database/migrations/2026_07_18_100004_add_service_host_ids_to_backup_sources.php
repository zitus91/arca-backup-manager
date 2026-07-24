<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_sources', function (Blueprint $table) {
            foreach (['mysql_host_id', 'mongodb_host_id', 'filesystem_host_id'] as $col) {
                $table->foreignId($col)->nullable()->after('host_id')->constrained('backup_hosts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('backup_sources', function (Blueprint $table) {
            foreach (['mysql_host_id', 'mongodb_host_id', 'filesystem_host_id'] as $col) {
                $table->dropForeign([$col]);
                $table->dropColumn($col);
            }
        });
    }
};
