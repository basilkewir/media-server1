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
        Schema::create('srt_streams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('stream_id')->unique();
            $table->integer('srt_port')->unique();
            $table->string('rtmp_stream')->unique();
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('bitrate')->default(1500); // kbps
            $table->string('resolution')->default('720p');
            $table->string('codec_video')->default('h264');
            $table->string('codec_audio')->default('aac');
            $table->enum('status', ['pending', 'connected', 'disconnected', 'error'])->default('pending');
            $table->timestamp('last_connected_at')->nullable();
            $table->longText('error_log')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('enabled');
            $table->index('status');
            $table->index('stream_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('srt_streams');
    }
};
