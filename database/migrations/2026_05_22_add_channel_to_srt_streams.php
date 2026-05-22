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
        Schema::table('srt_streams', function (Blueprint $table) {
            // Add channel_id foreign key to link SRT stream to a Channel
            $table->foreignId('channel_id')
                ->nullable()
                ->constrained('channels')
                ->onDelete('set null')
                ->after('id');
            
            // Add flag to enable VOD fallback for this SRT stream
            $table->boolean('vod_fallback_enabled')->default(false)->after('enabled');
            
            // Index for quick lookups
            $table->index('channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('srt_streams', function (Blueprint $table) {
            $table->dropForeignIdFor('channels');
            $table->dropColumn('vod_fallback_enabled');
            $table->dropIndex(['channel_id']);
        });
    }
};
