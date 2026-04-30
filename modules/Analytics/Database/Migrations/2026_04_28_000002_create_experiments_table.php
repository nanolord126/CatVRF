<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create experiments table for A/B testing CLV-based promotions.
     *
     * Production-ready with:
     * - Multi-tenant support
     * - Seller-scoped experiments (null = platform-wide)
     * - CLV filter configuration in JSON
     * - Proper indexing for performance
     * - Status tracking (draft, running, finished, paused)
     */
    public function up(): void
    {
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenant support
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            
            // Seller scope (null = platform-wide experiment)
            $table->foreignId('seller_id')->nullable()->constrained()->nullOnDelete();
            
            // Experiment identification
            $table->string('key')->unique()->comment('Unique experiment key for assignment');
            $table->string('name')->comment('Human-readable experiment name');
            $table->text('description')->nullable();
            
            // Target segment configuration
            $table->string('target_segment')->comment('Target CLV segment: champions, at_risk, high_clv, etc.');
            $table->json('clv_filters')->nullable()->comment('CLV filter conditions: clv_min, churn_prob_max, etc.');
            
            // Traffic configuration
            $table->unsignedInteger('traffic_percent')->default(100)->comment('Percentage of target segment in experiment');
            
            // Timing
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->dateTime('scheduled_start_at')->nullable();
            $table->dateTime('scheduled_end_at')->nullable();
            
            // Status and metadata
            $table->string('status')->default('draft')->comment('draft, running, finished, paused');
            $table->string('primary_metric')->default('revenue_14d')->comment('Primary success metric');
            $table->json('secondary_metrics')->nullable()->comment('Secondary metrics for analysis');
            
            // Results
            $table->json('results')->nullable()->comment('Experiment results: lift, p_value, winner');
            $table->string('winning_variant_id')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable()->comment('Additional experiment metadata');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index(['status', 'started_at']);
            $table->index('target_segment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiments');
    }
};
