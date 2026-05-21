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
        // Add Icecast and Relay columns to channels table
        Schema::table('channels', function (Blueprint $table) {
            $table->boolean('is_icecast_enabled')->default(false)->after('is_live');
            $table->boolean('is_relay_enabled')->default(false)->after('is_icecast_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['is_icecast_enabled', 'is_relay_enabled']);
        });
    }
};
