<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vod_files', function (Blueprint $table) {
            $table->enum('source_type', ['upload', 'youtube'])->default('upload')->after('channel_id');
            $table->string('youtube_url')->nullable()->after('source_type');
        });
    }

    public function down(): void
    {
        Schema::table('vod_files', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'youtube_url']);
        });
    }
};
