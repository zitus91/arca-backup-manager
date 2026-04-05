<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->enum('backup_type', ['full', 'incremental'])->default('full')->after('compression');
            $table->integer('full_backup_every')->nullable()->after('backup_type');
        });

        Schema::table('backup_logs', function (Blueprint $table) {
            $table->foreignId('parent_backup_log_id')
                ->nullable()
                ->after('backup_job_id')
                ->constrained('backup_logs')
                ->nullOnDelete();
            $table->boolean('is_full')->default(true)->after('parent_backup_log_id');
            $table->json('incremental_checkpoint')->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            $table->dropForeign(['parent_backup_log_id']);
            $table->dropColumn(['parent_backup_log_id', 'is_full', 'incremental_checkpoint']);
        });

        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->dropColumn(['backup_type', 'full_backup_every']);
        });
    }
};
