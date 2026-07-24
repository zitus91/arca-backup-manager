<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_sources', function (Blueprint $table) {
            $table->foreignId('postgres_host_id')->nullable()->after('mysql_host_id')->constrained('backup_hosts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('backup_sources', function (Blueprint $table) {
            $table->dropForeign(['postgres_host_id']);
            $table->dropColumn('postgres_host_id');
        });
    }
};
