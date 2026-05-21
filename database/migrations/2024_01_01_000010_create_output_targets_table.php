<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('output_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->onDelete('cascade');

            // Identity
            $table->string('name');
            $table->string('output_url');
            $table->enum('output_protocol', [
                'rtmp', 'rtmps', 'srt',
                'mpeg_ts_udp', 'mpeg_ts_tcp', 'rtp',
                'hls_push', 'icecast', 'shoutcast', 'file',
            ])->default('rtmp');

            // Trigger: when should this output be active
            $table->enum('trigger', [
                'always',        // live + VOD fallback
                'live_only',     // only when live push is active
                'fallback_only', // only during VOD fallback
                'manual',        // API-controlled only
            ])->default('always');

            // ── Transcoding profile ───────────────────────────────────────
            // All nullable — null or 'copy' = passthrough (zero added latency).
            // Only set these when you explicitly need a different format/bitrate.
            $table->string('video_codec')->nullable()->default('copy');
            $table->string('audio_codec')->nullable()->default('copy');
            $table->unsignedInteger('video_bitrate_kbps')->nullable(); // null = source bitrate
            $table->unsignedInteger('audio_bitrate_kbps')->nullable(); // null = source bitrate
            $table->string('resolution')->nullable();                  // null = source resolution
            $table->unsignedTinyInteger('framerate')->nullable();      // null = source framerate

            // SRT-specific options
            $table->string('srt_passphrase')->nullable();
            $table->unsignedSmallInteger('srt_latency_ms')->default(120);

            // ── Runtime state ─────────────────────────────────────────────
            $table->boolean('is_enabled')->default(true);
            $table->enum('status', [
                'idle', 'connecting', 'connected',
                'reconnecting', 'error', 'stopped',
            ])->default('idle');
            $table->unsignedInteger('pid')->nullable();
            $table->unsignedSmallInteger('reconnect_attempts')->default(0);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('bytes_sent')->default(0);

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'is_enabled']);
            $table->index(['channel_id', 'trigger']);
            $table->index('status');
        });

        Schema::create('output_target_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('output_target_id')
                ->constrained('output_targets')->onDelete('cascade');
            $table->enum('level', ['info', 'warning', 'error'])->default('info');
            $table->string('event');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['output_target_id', 'created_at']);
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('output_target_logs');
        Schema::dropIfExists('output_targets');
    }
};
