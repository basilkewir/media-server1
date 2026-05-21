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
        Schema::create('relay_broadcast_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('relay_broadcast_id')->constrained('relay_broadcasts')->onDelete('cascade');
            $table->string('event_type');
            $table->text('message');
            $table->integer('listeners_count')->nullable();
            $table->float('bitrate_kbps')->nullable();
            $table->enum('status', ['success', 'warning', 'error'])->default('success');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('relay_broadcast_id');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relay_broadcast_logs');
    }
};
