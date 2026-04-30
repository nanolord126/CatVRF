<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create experiment_assignments table for tracking user assignments.
     *
     * Production-ready with:
     * - User-seller-experiment unique assignment (prevents leakage)
     * - CLV snapshot at assignment time (for stratification analysis)
     * - Assignment timestamp for cohort analysis
     * - Proper indexing for high-volume queries
     * - GDPR-compliant with soft deletes
     */
    public function up(): void
    {
        Schema::create('experiment_assignments', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenant support
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Assignment context
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
            
            // CLV snapshot at assignment (for stratification)
            $table->decimal('clv_180d_at_assignment', 12, 2)->nullable();
            $table->decimal('clv_365d_at_assignment', 12, 2)->nullable();
            $table->float('churn_prob_at_assignment')->nullable();
            $table->string('clv_segment_at_assignment')->nullable();
            
            // Assignment metadata
            $table->string('assignment_method')->default('hash')->comment('hash, random, stratified');
            $table->unsignedInteger('hash_bucket')->nullable()->comment('Hash bucket for deterministic assignment');
            
            // Tracking
            $table->dateTime('assigned_at')->useCurrent();
            $table->dateTime('first_exposed_at')->nullable()->comment('First time user saw the variant');
            $table->dateTime('last_exposed_at')->nullable()->comment('Last interaction with variant');
            $table->unsignedInteger('exposure_count')->default(0)->comment('Number of times user was exposed');
            
            // Results (post-experiment metrics)
            $table->decimal('revenue_14d', 12, 2)->nullable()->comment('Revenue 14 days after assignment');
            $table->decimal('revenue_30d', 12, 2)->nullable()->comment('Revenue 30 days after assignment');
            $table->unsignedInteger('orders_14d')->nullable()->comment('Orders 14 days after assignment');
            $table->unsignedInteger('orders_30d')->nullable()->comment('Orders 30 days after assignment');
            $table->float('clv_delta')->nullable()->comment('CLV change after experiment');
            $table->float('churn_prob_delta')->nullable()->comment('Churn probability change after experiment');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint: one assignment per buyer-seller-experiment (prevents leakage)
            $table->unique(['tenant_id', 'experiment_id', 'seller_id', 'buyer_id'], 'unique_assignment');
            
            // Indexes for performance
            $table->index(['tenant_id', 'experiment_id', 'assigned_at']);
            $table->index(['seller_id', 'experiment_id', 'assigned_at']);
            $table->index(['buyer_id', 'experiment_id']);
            $table->index(['variant_id', 'assigned_at']);
            $table->index('assigned_at');
            $table->index(['clv_segment_at_assignment', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_assignments');
    }
};
