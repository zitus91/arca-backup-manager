<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('backup_jobs', 'notification_emails')) {
            Schema::table('backup_jobs', function (Blueprint $table) {
                $table->json('notification_emails')->nullable()->after('notification_email');
            });
        }

        // Migrate existing single email value into the new JSON array column
        DB::table('backup_jobs')->whereNotNull('notification_email')->orderBy('id')->each(function ($row) {
            DB::table('backup_jobs')
                ->where('id', $row->id)
                ->update(['notification_emails' => json_encode([$row->notification_email])]);
        });

        if (Schema::hasColumn('backup_jobs', 'notification_email')) {
            Schema::table('backup_jobs', function (Blueprint $table) {
                $table->dropColumn('notification_email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->string('notification_email')->nullable()->after('notification_emails');
        });

        // Restore first email from array back to the old column
        DB::table('backup_jobs')->whereNotNull('notification_emails')->orderBy('id')->each(function ($row) {
            $emails = json_decode($row->notification_emails, true);
            DB::table('backup_jobs')
                ->where('id', $row->id)
                ->update(['notification_email' => $emails[0] ?? null]);
        });

        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->dropColumn('notification_emails');
        });
    }
};
