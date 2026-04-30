<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_ranking_scores', function (Blueprint $table) {
            $table->uuid('listing_uuid')->primary();
            
            // Overall score
            $table->decimal('overall_score', 5, 4)->default(0);
            
            // Individual factor scores
            $table->decimal('popularity_score', 5, 4)->default(0);
            $table->decimal('conversion_score', 5, 4)->default(0);
            $table->decimal('recency_score', 5, 4)->default(0);
            $table->decimal('rating_score', 5, 4)->default(0);
            $table->decimal('price_score', 5, 4)->default(0);
            $table->decimal('availability_score', 5, 4)->default(0);
            $table->decimal('promoted_score', 5, 4)->default(0);
            $table->decimal('ml_score', 5, 4)->default(0);
            $table->decimal('personalization_score', 5, 4)->default(0);
            
            // Factors stored as JSON for detailed analysis
            $table->json('factors')->nullable();
            
            // Algorithm version for tracking changes
            $table->string('algorithm_version', 20)->default('1.0.0');
            
            // Timestamps
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamp('valid_until')->nullable();
            
            // Indexes
            $table->index('overall_score');
            $table->index('valid_until');
            $table->index('calculated_at');
            
            // Foreign key to listings
            $table->foreign('listing_uuid')
                  ->references('uuid')
                  ->on('marketplace_listings')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_ranking_scores');
    }
};
