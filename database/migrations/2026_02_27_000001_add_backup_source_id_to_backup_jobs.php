<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            //            $table->foreignId('backup_source_id')
            //                ->after('name')
            //                ->constrained('backup_sources')
            //                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->dropForeign(['backup_source_id']);
            $table->dropColumn('backup_source_id');
        });
    }
};
