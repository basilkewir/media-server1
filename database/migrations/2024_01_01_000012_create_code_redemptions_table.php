<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_code_id')->constrained('access_codes')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('redeemed_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('ip_address');
            $table->index('redeemed_at');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_redemptions');
    }
};
