<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Laravel recreates the table internally when ->change() is called on SQLite,
            // which is the only way to update a CHECK constraint on SQLite.
            Schema::table('backup_logs', function (Blueprint $table) {
                $table->enum('status', ['pending', 'running', 'success', 'failed', 'partial', 'cancelled'])->change();
            });
        } else {
            DB::statement("ALTER TABLE backup_logs MODIFY COLUMN status ENUM('pending','running','success','failed','partial','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        DB::table('backup_logs')->where('status', 'cancelled')->update(['status' => 'failed']);

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('backup_logs', function (Blueprint $table) {
                $table->enum('status', ['pending', 'running', 'success', 'failed', 'partial'])->change();
            });
        } else {
            DB::statement("ALTER TABLE backup_logs MODIFY COLUMN status ENUM('pending','running','success','failed','partial') NOT NULL DEFAULT 'pending'");
        }
    }
};
