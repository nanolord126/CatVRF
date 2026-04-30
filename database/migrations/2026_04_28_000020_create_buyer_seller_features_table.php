<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feature store table for CLV (Customer Lifetime Value) prediction.
     * 
     * This table stores aggregated features for buyer-seller pairs,
     * updated daily via CalculateBuyerFeaturesJob.
     * Used as training data and inference input for ML models.
     * 
     * Production considerations:
     * - Indexed on buyer_id, seller_id, updated_at for fast queries
     * - Partitioned by tenant_id in production (PostgreSQL)
     * - Contains both historical features and future targets for training
     */
    public function up(): void
    {
        Schema::create('buyer_seller_features', function (Blueprint $table) {
            $table->id();
            
            // Core identifiers
            $table->unsignedBigInteger('buyer_id')->index();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('tenant_id')->index();
            
            // RFM scores (1-5 scale)
            $table->tinyInteger('r_score')->nullable()->comment('Recency score 1-5');
            $table->tinyInteger('f_score')->nullable()->comment('Frequency score 1-5');
            $table->tinyInteger('m_score')->nullable()->comment('Monetary score 1-5');
            
            // Recency metrics
            $table->unsignedInteger('recency_days')->nullable()->comment('Days since last purchase');
            $table->timestamp('last_purchase_at')->nullable()->index();
            
            // Frequency metrics
            $table->unsignedInteger('frequency_90d')->default(0)->comment('Orders in last 90 days');
            $table->unsignedInteger('frequency_180d')->default(0)->comment('Orders in last 180 days');
            $table->unsignedInteger('frequency_365d')->default(0)->comment('Orders in last 365 days');
            
            // Monetary metrics
            $table->decimal('monetary_90d', 12, 2)->default(0)->comment('Total spent in last 90 days');
            $table->decimal('monetary_180d', 12, 2)->default(0)->comment('Total spent in last 180 days');
            $table->decimal('monetary_365d', 12, 2)->default(0)->comment('Total spent in last 365 days');
            $table->decimal('avg_order_value', 12, 2)->default(0)->comment('Average order value');
            
            // Lifetime metrics
            $table->timestamp('first_purchase_at')->nullable();
            $table->unsignedInteger('days_since_first_purchase')->nullable();
            $table->unsignedInteger('total_orders_all_time')->default(0);
            $table->decimal('total_monetary_all_time', 14, 2)->default(0);
            
            // Behavioral metrics
            $table->decimal('return_rate', 5, 2)->default(0)->comment('Return rate percentage');
            $table->decimal('review_score', 3, 2)->nullable()->comment('Average review score 1-5');
            $table->unsignedInteger('total_reviews')->default(0);
            
            // Traffic sources (percentages)
            $table->decimal('traffic_search_pct', 5, 2)->default(0)->comment('Search traffic %');
            $table->decimal('traffic_recommendation_pct', 5, 2)->default(0)->comment('Recommendation traffic %');
            $table->decimal('traffic_direct_pct', 5, 2)->default(0)->comment('Direct traffic %');
            $table->decimal('traffic_other_pct', 5, 2)->default(0)->comment('Other traffic %');
            
            // Category and geography
            $table->string('last_category', 100)->nullable()->index();
            $table->string('geo_region', 100)->nullable();
            $table->string('geo_city', 100)->nullable();
            
            // ML prediction outputs (updated after model inference)
            $table->decimal('predicted_clv_180d', 14, 2)->nullable()->comment('Predicted CLV for next 180 days');
            $table->decimal('predicted_clv_365d', 14, 2)->nullable()->comment('Predicted CLV for next 365 days');
            $table->decimal('churn_probability', 5, 4)->nullable()->comment('Churn probability 0-1');
            $table->decimal('prediction_confidence', 5, 4)->nullable()->comment('Model confidence 0-1');
            $table->string('clv_segment', 50)->nullable()->index()->comment('CLV segment: low/medium/high/vip');
            
            // Training targets (only populated for historical data)
            $table->decimal('actual_monetary_180d', 14, 2)->nullable()->comment('Actual future 180d monetary (label)');
            $table->decimal('actual_monetary_365d', 14, 2)->nullable()->comment('Actual future 365d monetary (label)');
            $table->boolean('churned_180d')->nullable()->comment('Did buyer churn within 180d (label)');
            
            // Metadata
            $table->string('model_version', 50)->nullable()->comment('ML model version used for prediction');
            $table->json('features_raw')->nullable()->comment('Raw features array for ML inference');
            $table->timestamps();
            $table->softDeletes();
            
            // Composite indexes for common queries
            $table->index(['seller_id', 'buyer_id']);
            $table->index(['tenant_id', 'seller_id']);
            $table->index(['tenant_id', 'buyer_id']);
            $table->index(['seller_id', 'predicted_clv_180d'], 'idx_seller_clv');
            $table->index(['seller_id', 'churn_probability'], 'idx_seller_churn');
            $table->index(['updated_at'], 'idx_updated_at');
            
            // Foreign keys (will be added if users/sellers tables exist)
            // $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_seller_features');
    }
};
