<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_storage_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['s3', 'ftp']);
            $table->text('config'); // encrypted JSON
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_storage_destinations');
    }
};
