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
        Schema::create('relay_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('hostname');
            $table->integer('port');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->enum('server_type', ['icecast', 'rtmp', 'shoutcast'])->default('icecast');
            $table->boolean('is_active')->default(true);
            $table->integer('max_listeners')->default(100);
            $table->string('location')->nullable();
            $table->integer('bandwidth_kbps')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('hostname');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relay_servers');
    }
};
