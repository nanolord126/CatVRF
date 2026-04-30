<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create daily_activity_logs table
 *
 * Tracks daily user activities for CatFloat streaks and multipliers.
 * Records login, quests completed, actions performed, and calculates activity scores.
 * Drives the gamified daily loop and streak multiplier system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            
            // User and date
            $table->uuid('user_id')->index()->comment('User who performed activities');
            $table->date('activity_date')->index()->comment('Date of activity (YYYY-MM-DD)');
            
            // Login tracking
            $table->boolean('has_logged_in')->default(false)->comment('User logged in on this day');
            $table->timestamp('first_login_at')->nullable()->comment('First login timestamp');
            $table->timestamp('last_login_at')->nullable()->comment('Last login timestamp');
            $table->integer('login_count')->default(0)->comment('Number of login sessions');
            
            // Activity counts
            $table->integer('product_views')->default(0)->comment('Products viewed');
            $table->integer('ar_try_ons')->default(0)->comment('AR try-ons performed');
            $table->integer('reviews_submitted')->default(0)->comment('Reviews submitted');
            $table->integer('cross_vertical_visits')->default(0)->comment('Cross-vertical transitions');
            $table->integer('purchases_made')->default(0)->comment('Purchases completed');
            $table->integer('quests_completed')->default(0)->comment('Daily quests completed');
            
            // Activity score
            $table->integer('activity_score')->default(0)->comment('Calculated activity score (0-100)');
            $table->boolean('meets_daily_threshold')->default(false)->comment('Meets 3+ action threshold');
            
            // Streak tracking
            $table->integer('current_streak_days')->default(0)->comment('Current consecutive days');
            $table->integer('longest_streak_days')->default(0)->comment('Longest streak ever');
            $table->integer('total_active_days')->default(0)->comment('Total active days ever');
            
            // Multiplier
            $table->decimal('streak_multiplier', 3, 2)->default(1.00)->comment('Current streak multiplier (x1.0-x4.0)');
            $table->decimal('bonus_multiplier', 3, 2)->default(1.00)->comment('Total bonus multiplier applied');
            
            // Rewards
            $table->integer('hold_days_reduced')->default(0)->comment('Hold days reduced by activity');
            $table->integer('bonus_points_earned')->default(0)->comment('Loyalty points earned');
            $table->integer('instant_bonus_awarded')->default(0)->comment('Instant bonus awarded (kopecks)');
            
            // Vertical breakdown
            $table->json('vertical_activity')->nullable()->comment('Activity breakdown by vertical');
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->unique(['user_id', 'activity_date']);
            $table->index(['activity_date', 'has_logged_in']);
            $table->index(['current_streak_days', 'activity_date']);
            $table->index(['activity_score', 'meets_daily_threshold']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_activity_logs');
    }
};
