<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create locked_bonus_batches table
 *
 * Tracks individual bonus batches with vesting curves for CatFloat.
 * Each bonus award creates a separate batch with its own hold period and vesting schedule.
 * Supports linear, accelerated, and custom vesting curves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locked_bonus_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uuid')->unique()->comment('Public UUID');
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('business_group_id')->nullable()->index();
            
            // User and wallet association
            $table->uuid('user_id')->index()->comment('User who owns this batch');
            $table->uuid('bonus_wallet_id')->index()->comment('Associated bonus wallet');
            $table->uuid('bonus_id')->nullable()->index()->comment('Link to original bonus record');
            
            // Amounts (in kopecks)
            $table->integer('total_amount')->comment('Total bonus amount in this batch');
            $table->integer('locked_amount')->default(0)->comment('Currently locked amount');
            $table->integer('unlocked_amount')->default(0)->comment('Already unlocked amount');
            $table->integer('claimed_yield')->default(0)->comment('Float yield claimed by user');
            
            // Vesting configuration
            $table->string('vesting_curve_type', 20)->default('linear')->comment('linear, accelerated, custom');
            $table->integer('base_hold_days')->default(15)->comment('Base hold period in days');
            $table->integer('actual_hold_days')->default(15)->comment('Actual hold days after accelerators');
            $table->integer('days_elapsed')->default(0)->comment('Days since award');
            $table->json('vesting_schedule')->nullable()->comment('Custom vesting schedule if not linear');
            
            // Accelerators
            $table->integer('activity_acceleration_days')->default(0)->comment('Days reduced by activity');
            $table->integer('streak_acceleration_days')->default(0)->comment('Days reduced by streaks');
            $table->integer('tier_acceleration_days')->default(0)->comment('Days reduced by user tier');
            
            // Status
            $table->string('status', 20)->default('locked')->comment('locked, vesting, unlocked, sold');
            $table->timestamp('locked_at')->comment('When the batch was locked');
            $table->timestamp('unlocked_at')->nullable()->comment('When the batch fully unlocked');
            $table->timestamp('fully_available_at')->nullable()->comment('When all funds become available');
            
            // Marketplace
            $table->boolean('is_for_sale')->default(false)->comment('Is this batch listed for sale');
            $table->integer('sale_discount_percent')->default(0)->comment('Discount if sold (15-30%)');
            $table->uuid('sold_to_user_id')->nullable()->comment('User who bought this batch');
            $table->timestamp('sold_at')->nullable()->comment('When the batch was sold');
            
            // Source tracking
            $table->string('source_type', 50)->nullable()->comment('payment, referral, quest, etc.');
            $table->uuid('source_id')->nullable()->comment('Source entity ID');
            $table->string('vertical', 50)->nullable()->comment('Vertical where bonus was earned');
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['status', 'fully_available_at']);
            $table->index(['is_for_sale', 'sale_discount_percent']);
            $table->index(['locked_at', 'actual_hold_days']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locked_bonus_batches');
    }
};
