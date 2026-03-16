<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: AI consumer analytics tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // e.g., 'purchase', 'view_product', 'search', 'taxi_cancel'
            $table->string('entity_type')->nullable(); // e.g., 'App\Models\Food\Product'
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('payload')->nullable(); // Additional data like search keywords, coordinates, cart items
            $table->uuid('correlation_id')->index();
            $table->timestamp('created_at')->useCurrent();
        });

        // 2. AI Generated Insights (LTV, Churn, RFM Scores)
        Schema::create('ai_predictive_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->float('ltv_prediction')->default(0); // Lifetime Value
            $table->float('churn_probability')->default(0); // 0 to 1
            $table->string('rfm_segment')->nullable(); // e.g., 'Champions', 'At Risk', 'Loyalists'
            $table->json('recommendations')->nullable(); // AI specific advice
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            
            $table->unique(['user_id', 'calculated_at']);
        });

        // 3. Dynamic Personalized Offers
        Schema::create('personalized_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('discount_type')->default('percentage'); // fixed, percentage, free_service
            $table->decimal('discount_value', 10, 2);
            $table->string('target_vertical'); // e.g., 'taxi', 'food', 'clinic'
            $table->timestamp('valid_from');
            $table->timestamp('valid_to');
            $table->boolean('is_redeemed')->default(false);
            $table->uuid('insight_id')->nullable(); // Link to a specific AI insight
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personalized_offers');
        Schema::dropIfExists('ai_predictive_insights');
        Schema::dropIfExists('consumer_behavior_logs');
    }
};
