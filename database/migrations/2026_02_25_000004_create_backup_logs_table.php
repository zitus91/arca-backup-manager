<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_job_id')->constrained('backup_jobs')->cascadeOnDelete();
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'partial']);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size_bytes')->nullable();
            $table->string('storage_path')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['backup_job_id', 'status', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
