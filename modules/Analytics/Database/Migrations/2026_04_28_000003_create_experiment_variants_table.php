<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create experiment_variants table for A/B test variants.
     *
     * Production-ready with:
     * - Variant configuration (discount, message, etc.)
     * - Traffic allocation per variant
     * - Is control flag for statistical baseline
     * - Proper indexing
     */
    public function up(): void
    {
        Schema::create('experiment_variants', function (Blueprint $table) {
            $table->id();
            
            // Association with experiment
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            
            // Variant identification
            $table->string('key')->comment('Variant identifier: A, B, C, control');
            $table->string('name')->comment('Human-readable variant name');
            
            // Variant configuration (discount, message, etc.)
            $table->json('configuration')->comment('Variant configuration: discount, message, coupon_code, etc.');
            
            // Traffic allocation
            $table->unsignedInteger('traffic_allocation')->default(0)->comment('Traffic percentage for this variant');
            $table->boolean('is_control')->default(false)->comment('Is this the control variant?');
            
            // Results tracking
            $table->unsignedInteger('sample_size')->default(0)->comment('Number of users assigned to this variant');
            $table->json('metrics')->nullable()->comment('Aggregated metrics for this variant');
            
            $table->timestamps();
            
            // Unique constraint: one key per experiment
            $table->unique(['experiment_id', 'key']);
            
            // Indexes
            $table->index(['experiment_id', 'is_control']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_variants');
    }
};
