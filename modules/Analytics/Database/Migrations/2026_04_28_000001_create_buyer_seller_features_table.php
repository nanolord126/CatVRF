<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create buyer_seller_features table for CLV prediction feature store.
     * 
     * This table stores aggregated features for buyer-seller pairs,
     * used as input for ML models to predict Customer Lifetime Value.
     * 
     * Production-ready: indexed queries, multi-tenant support, soft deletes.
     */
    public function up(): void
    {
        Schema::create('buyer_seller_features', function (Blueprint $table) {
            // Primary key and multi-tenant
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->index();
            
            // Buyer-Seller relationship
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade')->index();
            
            // Unique constraint for buyer-seller-tenant combination
            $table->unique(['tenant_id', 'buyer_id', 'seller_id'], 'unique_buyer_seller_tenant');
            
            // RFM scores (1-5 scale)
            $table->tinyInteger('r_score')->nullable()->comment('Recency score');
            $table->tinyInteger('f_score')->nullable()->comment('Frequency score');
            $table->tinyInteger('m_score')->nullable()->comment('Monetary score');
            
            // Recency metrics
            $table->integer('recency_days')->nullable()->comment('Days since last purchase');
            $table->timestamp('last_purchase_at')->nullable()->comment('Last purchase timestamp');
            
            // Frequency metrics
            $table->integer('frequency_90d')->default(0)->comment('Orders in last 90 days');
            $table->integer('frequency_180d')->default(0)->comment('Orders in last 180 days');
            $table->integer('frequency_365d')->default(0)->comment('Orders in last 365 days');
            
            // Monetary metrics
            $table->decimal('monetary_90d', 12, 2)->default(0)->comment('Total spent in last 90 days');
            $table->decimal('monetary_180d', 12, 2)->default(0)->comment('Total spent in last 180 days');
            $table->decimal('monetary_365d', 12, 2)->default(0)->comment('Total spent in last 365 days');
            $table->decimal('avg_order_value', 12, 2)->default(0)->comment('Average order value');
            
            // Lifetime metrics
            $table->timestamp('first_purchase_at')->nullable()->comment('First purchase timestamp');
            $table->integer('days_since_first_purchase')->nullable()->comment('Days since first purchase');
            $table->integer('total_orders_all_time')->default(0)->comment('Total orders all time');
            $table->decimal('total_monetary_all_time', 14, 2)->default(0)->comment('Total spent all time');
            
            // Behavioral metrics
            $table->decimal('return_rate', 5, 2)->default(0)->comment('Return rate percentage');
            $table->decimal('review_score', 3, 2)->nullable()->comment('Average review score (1-5)');
            $table->integer('total_reviews')->default(0)->comment('Total number of reviews');
            
            // Traffic sources (percentages, should sum to 100)
            $table->decimal('traffic_search_pct', 5, 2)->default(0)->comment('Traffic from search percentage');
            $table->decimal('traffic_recommendation_pct', 5, 2)->default(0)->comment('Traffic from recommendations percentage');
            $table->decimal('traffic_direct_pct', 5, 2)->default(0)->comment('Direct traffic percentage');
            $table->decimal('traffic_other_pct', 5, 2)->default(0)->comment('Other traffic sources percentage');
            
            // Category and geography
            $table->string('last_category', 100)->nullable()->comment('Last purchased category');
            $table->string('geo_region', 100)->nullable()->comment('Geographic region');
            $table->string('geo_city', 100)->nullable()->comment('Geographic city');
            
            // ML Prediction results
            $table->decimal('predicted_clv_180d', 14, 2)->nullable()->comment('Predicted CLV for next 180 days');
            $table->decimal('predicted_clv_365d', 14, 2)->nullable()->comment('Predicted CLV for next 365 days');
            $table->decimal('churn_probability', 5, 4)->nullable()->comment('Probability of churn (0-1)');
            $table->decimal('prediction_confidence', 5, 4)->nullable()->comment('Prediction confidence (0-1)');
            $table->string('clv_segment', 20)->nullable()->comment('CLV segment (low, medium, high, vip)');
            $table->string('model_version', 50)->nullable()->comment('ML model version used');
            
            // Training labels (for historical data)
            $table->decimal('actual_monetary_180d', 14, 2)->nullable()->comment('Actual monetary 180 days later (label)');
            $table->decimal('actual_monetary_365d', 14, 2)->nullable()->comment('Actual monetary 365 days later (label)');
            $table->boolean('churned_180d')->nullable()->comment('Whether buyer churned in 180 days (label)');
            
            // Raw features (JSON for flexibility)
            $table->json('features_raw')->nullable()->comment('Raw features as JSON');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for common queries
            $table->index(['tenant_id', 'seller_id'], 'idx_features_seller_tenant');
            $table->index(['tenant_id', 'buyer_id'], 'idx_features_buyer_tenant');
            $table->index(['seller_id', 'predicted_clv_180d'], 'idx_features_seller_clv');
            $table->index('clv_segment', 'idx_features_segment');
            $table->index('churn_probability', 'idx_features_churn');
            $table->index('updated_at', 'idx_features_updated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_seller_features');
    }
};
