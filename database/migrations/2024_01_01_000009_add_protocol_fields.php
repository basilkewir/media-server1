<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            // Outbound RTMP push target — used during VOD fallback to push to another server
            $table->string('rtmp_push_url')->nullable()->after('push_url');
        });

        Schema::table('streams', function (Blueprint $table) {
            // Detected ingest protocol: RTMP, HLS, RTSP, SRT, UDP, etc.
            $table->string('input_protocol')->nullable()->after('stream_type');
            $table->index('input_protocol');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('rtmp_push_url');
        });

        Schema::table('streams', function (Blueprint $table) {
            $table->dropColumn('input_protocol');
        });
    }
};
