<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restore_logs', function (Blueprint $table) {
            $table->string('restore_target')->default('same_host')->after('restore_type');
            $table->json('remote_host_config')->nullable()->after('restore_target');
            $table->json('custom_names')->nullable()->after('remote_host_config');
            $table->boolean('override_existing')->default(false)->after('custom_names');
        });
    }

    public function down(): void
    {
        Schema::table('restore_logs', function (Blueprint $table) {
            $table->dropColumn(['restore_target', 'remote_host_config', 'custom_names', 'override_existing']);
        });
    }
};
