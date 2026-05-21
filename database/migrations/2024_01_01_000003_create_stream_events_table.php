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
        Schema::create('stream_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->onDelete('cascade');
            $table->string('event_type');
            $table->longText('message');
            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('channel_id');
            $table->index('event_type');
            $table->index('severity');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_events');
    }
};
