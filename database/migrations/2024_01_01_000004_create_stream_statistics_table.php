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
        Schema::create('stream_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('streams')->onDelete('cascade');
            $table->integer('viewers');
            $table->float('bitrate_kbps')->nullable();
            $table->float('framerate')->nullable();
            $table->boolean('is_healthy')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('stream_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_statistics');
    }
};
