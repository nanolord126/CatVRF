<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create experiment_metrics table for daily metric aggregation.
     *
     * Production-ready with:
     * - Daily aggregation per variant (reduces query load)
     * - Multiple metric types (revenue, orders, conversion, etc.)
     * - Statistical analysis fields (mean, std, confidence intervals)
     * - Proper indexing for time-series queries
     */
    public function up(): void
    {
        Schema::create('experiment_metrics', function (Blueprint $table) {
            $table->id();
            
            // Association
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();
            
            // Time dimension
            $table->date('metric_date')->comment('Date of metric aggregation');
            
            // Metric type and value
            $table->string('metric_type')->comment('revenue, orders, conversion, clv_delta, churn_prob_delta, etc.');
            $table->decimal('metric_value', 15, 2)->comment('Aggregated metric value');
            $table->unsignedInteger('sample_size')->default(0)->comment('Number of users contributing to this metric');
            
            // Statistical analysis fields
            $table->decimal('mean', 15, 2)->nullable()->comment('Mean value per user');
            $table->decimal('std_dev', 15, 2)->nullable()->comment('Standard deviation');
            $table->decimal('confidence_interval_lower', 15, 2)->nullable();
            $table->decimal('confidence_interval_upper', 15, 2)->nullable();
            $table->float('confidence_level')->default(0.95)->comment('Confidence level for intervals');
            
            // Cumulative metrics (for cumulative charts)
            $table->decimal('cumulative_value', 15, 2)->nullable()->comment('Cumulative metric value from start');
            $table->decimal('cumulative_mean', 15, 2)->nullable()->comment('Cumulative mean per user');
            
            $table->timestamps();
            
            // Unique constraint: one record per variant-metric-date
            $table->unique(['experiment_id', 'variant_id', 'metric_type', 'metric_date'], 'unique_metric');
            
            // Indexes for performance
            $table->index(['experiment_id', 'metric_date']);
            $table->index(['variant_id', 'metric_type', 'metric_date']);
            $table->index(['metric_date', 'metric_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_metrics');
    }
};
