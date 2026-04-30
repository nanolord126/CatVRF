<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create bonus_rules table
 * 
 * Stores flexible bonus accrual rules per vertical and rule type.
 * Supports A/B testing via is_active flag and priority ordering.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('business_group_id')->nullable()->index();
            
            // Rule identification
            $table->string('code', 100)->unique()->comment('Rule code: referral, first_purchase, turnover, etc.');
            $table->string('name', 255)->comment('Human-readable rule name');
            $table->string('description')->nullable();
            
            // Rule type and vertical
            $table->string('rule_type', 50)->comment('referral, first_purchase, turnover, loyalty, promo, ai_constructor');
            $table->string('vertical_code', 50)->nullable()->comment('beauty, fashion, food, etc. or null for global');
            
            // Rule configuration (JSON)
            $table->json('config')->comment('Rule-specific configuration: amounts, percentages, tiers');
            
            // Constraints and conditions
            $table->decimal('min_amount', 15, 2)->nullable()->comment('Minimum order amount for rule to apply');
            $table->decimal('max_amount', 15, 2)->nullable()->comment('Maximum bonus amount per transaction');
            $table->integer('max_per_user')->nullable()->comment('Maximum times user can use this rule');
            $table->integer('max_per_day')->nullable()->comment('Maximum times rule can be used per day globally');
            $table->integer('cooldown_hours')->default(0)->comment('Cooldown period between uses');
            
            // Multiplier
            $table->decimal('multiplier', 5, 4)->default(1.0000)->comment('Bonus multiplier for this rule');
            
            // Activation and priority
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0)->comment('Higher priority rules evaluated first');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            // A/B testing
            $table->string('ab_test_variant')->nullable()->comment('A/B test variant identifier');
            $table->decimal('ab_test_traffic_percentage', 5, 2)->nullable()->comment('Traffic percentage for this variant');
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['rule_type', 'vertical_code', 'is_active']);
            $table->index(['tenant_id', 'rule_type', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_rules');
    }
};
