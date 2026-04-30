<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add promotion fields to marketplace_listings table
 *
 * Integrates ProductListing with advertising core for promoted listings
 * Includes impression/click counters and budget tracking
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            // Promotion configuration
            $table->string('promotion_type', 50)->nullable()->after('is_promoted')->comment('Type: featured, banner, homepage, sidebar, feed_top');
            $table->decimal('promotion_budget', 12, 2)->nullable()->after('promotion_type')->comment('Budget in currency');
            $table->timestamp('promotion_start_at')->nullable()->after('promotion_budget')->index();
            $table->timestamp('promotion_end_at')->nullable()->after('promotion_start_at')->index();
            
            // Promotion counters (integrated with ad core)
            $table->unsignedInteger('promotion_impressions')->default(0)->after('promotion_end_at')->comment('Total ad impressions');
            $table->unsignedInteger('promotion_clicks')->default(0)->after('promotion_impressions')->comment('Total ad clicks');
            $table->decimal('promotion_spend_kopecks', 14, 0)->default(0)->after('promotion_clicks')->comment('Total spend in kopecks');
            $table->unsignedInteger('promotion_priority_boost')->default(0)->after('promotion_spend')->comment('Priority boost for ranking');
            
            // Indexes for promotion queries
            $table->index(['is_promoted', 'promotion_start_at', 'promotion_end_at'], 'idx_promotion_active');
            $table->index(['promotion_type', 'is_promoted'], 'idx_promotion_type');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->dropIndex('idx_promotion_active');
            $table->dropIndex('idx_promotion_type');
            $table->dropColumn([
                'promotion_type',
                'promotion_budget',
                'promotion_start_at',
                'promotion_end_at',
                'promotion_impressions',
                'promotion_clicks',
                'promotion_spend_kopecks',
                'promotion_priority_boost',
            ]);
        });
    }
};
