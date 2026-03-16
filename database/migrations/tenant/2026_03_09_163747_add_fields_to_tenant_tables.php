<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_reports', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // daily, weekly
            $table->date('report_date');
            $table->json('data'); // Revenue, orders, AI text
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'report_date']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('timezone')->default('Europe/Moscow');
            $table->json('report_emails')->nullable(); // Array of strings
        });
        
        Schema::table('wishlists', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable();
            $table->boolean('is_public')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_reports');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'report_emails']);
        });
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropColumn(['slug', 'is_public']);
        });
    }
};
