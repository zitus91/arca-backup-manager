<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't enforce ENUM constraints, so no change needed.
        // For MySQL/MariaDB, extend the ENUM to include 'cancelled'.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE backup_logs MODIFY COLUMN status ENUM('pending','running','success','failed','partial','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // First convert any 'cancelled' rows back to 'failed' to avoid data loss
            DB::table('backup_logs')->where('status', 'cancelled')->update(['status' => 'failed']);
            DB::statement("ALTER TABLE backup_logs MODIFY COLUMN status ENUM('pending','running','success','failed','partial') NOT NULL DEFAULT 'pending'");
        }
    }
};
