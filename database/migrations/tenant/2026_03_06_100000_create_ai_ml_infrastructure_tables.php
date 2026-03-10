<?php

namespace database\migrations\tenant;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Implement 2026 BigData Schema: embeddings, telemetry, correlation_id tracking.
     */
    public function up(): void
    {
        // 1. Telemetry and AI Recommendations Logs (Simulated ClickHouse structure)
        Schema::create('ai_behavioral_telemetry', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('event_type'); // 'view', 'click', 'purchase', 'search'
            $table->string('entity_type'); // 'taxi', 'clinics', etc.
            $table->unsignedBigInteger('entity_id');
            $table->jsonb('context'); // {geo: {lat: x, long: y}, persona: 'premium', rfm: 'loyal'}
            $table->timestamp('created_at')->useCurrent();
            
            // Hyper-scoping for fast BigData analysis
            $table->index(['event_type', 'created_at']);
        });

        // 2. Global AI Recommendation Buffers (Vector Cache)
        Schema::create('ai_recommendation_vectors', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            // 2026 Standard: Embeddings stored as JSONB for Typesense/OpenAI synchronization
            $table->jsonb('embedding_vector'); 
            $table->timestamp('refreshed_at')->nullable();
            
            $table->unique(['entity_type', 'entity_id']);
        });

        // 3. AI Assistant Context History (Multi-Tenant + Correlation)
        Schema::create('ai_assistant_convos', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->index();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('user_prompt');
            $table->text('ai_response');
            $table->string('model_version')->default('gpt-4o-2024-08-06');
            $table->jsonb('metadata'); // tokens used, sentiment_score
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_behavioral_telemetry');
        Schema::dropIfExists('ai_recommendation_vectors');
        Schema::dropIfExists('ai_assistant_convos');
    }
};
