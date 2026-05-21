<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->onDelete('cascade');
            $table->enum('status', ['active', 'paused', 'completed', 'fallback', 'error'])->default('active');
            $table->enum('stream_type', ['live', 'vod'])->default('live');
            $table->text('source_url');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->integer('bitrate_kbps')->nullable();
            $table->string('resolution')->nullable();
            $table->integer('viewers')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('channel_id');
            $table->index('status');
            $table->index('stream_type');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
