<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('backup_source_id')->constrained('backup_sources')->cascadeOnDelete();
            $table->foreignId('backup_storage_destination_id')->constrained('backup_storage_destinations')->cascadeOnDelete();
            $table->enum('schedule_type', ['manual', 'hourly', 'daily', 'weekly', 'monthly', 'custom']);
            $table->string('schedule_cron')->nullable();
            $table->time('schedule_time')->nullable();
            $table->tinyInteger('schedule_day_of_week')->nullable();
            $table->tinyInteger('schedule_day_of_month')->nullable();
            $table->integer('retention_count')->default(7);
            $table->enum('compression', ['none', 'gzip', 'zip'])->default('gzip');
            $table->boolean('notify_on_success')->default(false);
            $table->boolean('notify_on_failure')->default(true);
            $table->string('notification_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_jobs');
    }
};
