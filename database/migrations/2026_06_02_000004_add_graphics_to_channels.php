<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            // Logo overlay
            $table->string('logo_path')->nullable()->after('resolution');
            $table->string('logo_position', 20)->default('top-right')->nullable(); // top-left, top-right, bottom-left, bottom-right
            $table->unsignedInteger('logo_opacity')->default(80);  // 0-100
            $table->unsignedInteger('logo_width')->default(150);   // px
            $table->unsignedInteger('logo_height')->default(0);    // 0 = auto from width

            // Watermark overlay
            $table->string('watermark_path')->nullable();
            $table->string('watermark_position', 20)->default('bottom-right')->nullable();
            $table->unsignedInteger('watermark_opacity')->default(40);

            // Scrolling ticker
            $table->text('ticker_text')->nullable();
            $table->string('ticker_position', 10)->default('bottom'); // top, bottom
            $table->string('ticker_text_color', 7)->default('#ffffff');
            $table->string('ticker_bg_color', 7)->default('#000000');
            $table->unsignedInteger('ticker_font_size')->default(24);
            $table->unsignedInteger('ticker_speed_ms')->default(120); // milliseconds per pixel scroll
            $table->boolean('ticker_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path','logo_position','logo_opacity','logo_width','logo_height',
                'watermark_path','watermark_position','watermark_opacity',
                'ticker_text','ticker_position','ticker_text_color','ticker_bg_color',
                'ticker_font_size','ticker_speed_ms','ticker_enabled',
            ]);
        });
    }
};
