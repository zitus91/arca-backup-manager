<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restore_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('restore_type'); // db_only, files_only, full
            $table->string('status')->default('pending'); // pending, running, success, failed

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            $table->string('restored_db_name')->nullable();
            $table->string('restored_path')->nullable();

            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restore_logs');
    }
};
