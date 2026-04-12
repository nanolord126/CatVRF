<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anonymized behavior events table (PostgreSQL fallback).
     * Primary storage is ClickHouse `anonymized_behavior` table.
     * This table stores recent 30 days for real-time Filament dashboards.
     */
    public function up(): void
    {
        Schema::create('anonymized_behavior', function (Blueprint $table): void {
            $table->id();
            $table->string('anonymized_user_id', 64)->index()->comment('SHA256 hash of user_id + salt');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('vertical', 50)->index();
            $table->string('action', 50)->index()->comment('view, add_to_cart, purchase, ar_try_on, ai_constructor, search, etc.');
            $table->unsignedInteger('session_duration')->default(0)->comment('Seconds');
            $table->string('device_type', 20)->nullable()->comment('mobile, desktop, tablet');
            $table->unsignedBigInteger('city_hash')->nullable()->comment('CRC32 hashed city for GDPR');
            $table->unsignedTinyInteger('behavior_cluster')->nullable()->comment('1-50 clusters from K-Means');
            $table->json('taste_vector')->nullable()->comment('Array of Float32 embeddings');
            $table->string('referrer_type', 30)->nullable()->comment('direct, search, social, email, ad');
            $table->string('correlation_id')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('event_timestamp')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'vertical', 'event_timestamp']);
            $table->index(['anonymized_user_id', 'event_timestamp']);
            $table->index(['action', 'vertical']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anonymized_behavior');
    }
};
