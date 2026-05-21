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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->string('vod_playlist_url')->nullable();
            $table->string('push_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_live')->default(false);
            $table->integer('bitrate_kbps')->nullable();
            $table->string('resolution')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
            $table->index('is_live');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
