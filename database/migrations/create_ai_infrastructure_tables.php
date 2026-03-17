<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026 AI Infrastructure: Telemetry & Vector Cache for CatVRF Ecosystem.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Behavioral Telemetry for Recommendations
        Schema::create('ai_user_telemetry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // view, click, purchase, lead
            $table->string('entity_type'); // App\Models\Service, etc.
            $table->unsignedBigInteger('entity_id');
            $table->string('category')->nullable();
            $table->json('payload')->nullable(); // Context: search_query, lat, lng, device
            $table->string('correlation_id')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'event_type', 'created_at']);
        });

        // 2. Vector Cache / Model-level Metadata for AI Search
        // Not all models have names/descriptions in one place
        Schema::create('ai_vector_cache', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('embeddings')->nullable(); // Compressed bit representation or direct if needed
            $table->timestamp('refreshed_at')->useCurrent();
            $table->unique(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_user_telemetry');
        Schema::dropIfExists('ai_vector_cache');
    }
};
