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
        Schema::create('relay_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->onDelete('cascade');
            $table->foreignId('relay_server_id')->constrained('relay_servers')->onDelete('cascade');
            $table->enum('status', ['connecting', 'connected', 'disconnected', 'failed', 'server_offline', 'process_died', 'stopped'])->default('connecting');
            $table->string('relay_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('bitrate_kbps')->nullable();
            $table->integer('listeners')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('channel_id');
            $table->index('relay_server_id');
            $table->index('status');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relay_broadcasts');
    }
};
