<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restore_logs', function (Blueprint $table) {
            $table->json('selected_items')->nullable()->after('restore_type');
        });
    }

    public function down(): void
    {
        Schema::table('restore_logs', function (Blueprint $table) {
            $table->dropColumn('selected_items');
        });
    }
};
