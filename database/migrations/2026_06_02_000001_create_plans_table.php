<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('tier')->default('free'); // free, basic, pro, enterprise
            $table->unsignedBigInteger('storage_quota_bytes')->default(524288000); // 500MB default
            $table->unsignedInteger('max_channels')->default(1);
            $table->unsignedInteger('max_vod_files')->default(10);
            $table->unsignedBigInteger('max_upload_bytes')->default(524288000); // max single upload
            $table->json('features')->nullable();   // ['overlay','ticker','scheduling',...]
            $table->unsignedInteger('price_cents')->default(0); // 0 = free
            $table->string('currency', 3)->default('USD');
            $table->string('billing_interval')->default('month'); // month, year, once
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
