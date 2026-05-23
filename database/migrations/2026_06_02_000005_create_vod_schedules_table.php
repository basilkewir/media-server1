<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vod_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vod_file_id')->constrained('vod_files')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamp('play_at');
            $table->timestamp('end_at')->nullable();
            $table->boolean('is_repeating')->default(false);
            $table->json('repeat_days')->nullable();               // [1,3,5] = Mon,Wed,Fri
            $table->boolean('override_default_playlist')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('channel_id');
            $table->index('play_at');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vod_schedules');
    }
};
