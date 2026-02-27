<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support altering enum columns, so we change to string
        Schema::table('backup_storage_destinations', function (Blueprint $table) {
            $table->string('type', 20)->change();
        });
    }

    public function down(): void
    {
        Schema::table('backup_storage_destinations', function (Blueprint $table) {
            $table->string('type', 20)->change();
        });
    }
};
