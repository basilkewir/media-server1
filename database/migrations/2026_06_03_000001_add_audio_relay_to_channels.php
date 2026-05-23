<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->boolean('audio_relay_enabled')->default(false)->after('is_relay_enabled');
            $table->string('audio_relay_playlist_url')->nullable()->after('audio_relay_enabled');
            $table->string('audio_source_url')->nullable()->after('audio_relay_playlist_url');
            $table->boolean('audio_fallback_enabled')->default(false)->after('audio_source_url');
            $table->string('audio_relay_target_url')->nullable()->after('audio_fallback_enabled');
            $table->string('audio_relay_protocol', 20)->default('icecast')->after('audio_relay_target_url');
        });

        // Add audio fallback trigger to output_targets
        DB::statement("ALTER TABLE output_targets MODIFY COLUMN `trigger` ENUM('always','live_only','fallback_only','manual','fallback_audio') NOT NULL DEFAULT 'always'");
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn([
                'audio_relay_enabled','audio_relay_playlist_url','audio_source_url',
                'audio_fallback_enabled','audio_relay_target_url','audio_relay_protocol',
            ]);
        });

        DB::statement("ALTER TABLE output_targets MODIFY COLUMN `trigger` ENUM('always','live_only','fallback_only','manual') NOT NULL DEFAULT 'always'");
    }
};
