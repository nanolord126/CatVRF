<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create bonus_campaigns table
 * 
 * Manages time-limited bonus campaigns (Black Friday, New Year, etc.)
 * with custom rules and multipliers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('business_group_id')->nullable()->index();
            
            // Campaign identification
            $table->string('code', 100)->unique()->comment('Campaign code');
            $table->string('name', 255)->comment('Campaign name');
            $table->text('description')->nullable();
            
            // Campaign type and scope
            $table->string('type', 50)->comment('multiplier, fixed_amount, percentage, tiered');
            $table->string('vertical_code', 50)->nullable()->comment('Vertical-specific or null for global');
            $table->string('target_segment')->nullable()->comment('b2c, b2b, new_users, vip, etc.');
            
            // Campaign configuration
            $table->json('config')->comment('Campaign-specific configuration');
            $table->decimal('multiplier', 5, 4)->nullable()->comment('Bonus multiplier for this campaign');
            $table->decimal('fixed_amount', 15, 2)->nullable()->comment('Fixed bonus amount');
            $table->decimal('percentage', 5, 2)->nullable()->comment('Percentage bonus');
            
            // Campaign period
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->index();
            
            // Budget and limits
            $table->decimal('total_budget', 18, 2)->nullable()->comment('Total campaign budget');
            $table->decimal('spent_budget', 18, 2)->default(0)->comment('Budget already spent');
            $table->integer('max_participants')->nullable()->comment('Maximum number of participants');
            $table->integer('current_participants')->default(0)->comment('Current participant count');
            
            // Activation
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0)->comment('Campaign priority for overlapping periods');
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['vertical_code', 'is_active', 'starts_at', 'ends_at']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_campaigns');
    }
};
