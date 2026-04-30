<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create daily_quests table
 *
 * Defines daily quests for CatFloat gamified daily loop.
 * Quests reset daily at 00:00 and provide bonuses, hold reduction, and multipliers.
 * Supports sponsored quests from brands and cross-vertical challenges.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_quests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            
            // Quest definition
            $table->string('quest_key', 100)->unique()->comment('Unique quest identifier');
            $table->string('title')->comment('Quest title displayed to user');
            $table->text('description')->nullable()->comment('Quest description');
            
            // Quest type and configuration
            $table->string('quest_type', 50)->comment('product_view, ar_tryon, review, purchase, cross_vertical');
            $table->string('vertical', 50)->nullable()->comment('Required vertical (or null for any)');
            $table->json('requirements')->nullable()->comment('Quest requirements (count, conditions, etc.)');
            
            // Rewards
            $table->integer('bonus_reward')->default(0)->comment('Instant bonus reward (kopecks)');
            $table->integer('hold_days_reduction')->default(0)->comment('Hold days reduced on completion');
            $table->decimal('bonus_multiplier', 3, 2)->default(1.00)->comment('Bonus multiplier for tomorrow');
            $table->integer('loyalty_points')->default(0)->comment('Loyalty points awarded');
            
            // Difficulty and priority
            $table->string('difficulty', 20)->default('easy')->comment('easy, medium, hard, legendary');
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->boolean('is_active')->default(true)->comment('Is quest currently active');
            
            // Sponsorship
            $table->boolean('is_sponsored')->default(false)->comment('Is this a sponsored quest');
            $table->uuid('sponsor_id')->nullable()->comment('Brand or sponsor ID');
            $table->string('sponsor_name', 100)->nullable()->comment('Sponsor display name');
            $table->json('sponsor_config')->nullable()->comment('Sponsor-specific configuration');
            
            // Availability
            $table->date('available_from')->nullable()->comment('Quest available from date');
            $table->date('available_until')->nullable()->comment('Quest available until date');
            $table->string('recurrence', 20)->default('daily')->comment('daily, weekly, one_time');
            
            // Target audience
            $table->json('target_tiers')->nullable()->comment('Target user tiers (null = all)');
            $table->json('target_verticals')->nullable()->comment('Target verticals (null = all)');
            $table->integer('max_completions')->default(1)->comment('Max completions per user per day');
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_active', 'available_from', 'available_until']);
            $table->index(['quest_type', 'vertical']);
            $table->index(['difficulty', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_quests');
    }
};
