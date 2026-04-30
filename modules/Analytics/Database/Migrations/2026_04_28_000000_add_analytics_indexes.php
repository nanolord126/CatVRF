<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add production-ready indexes to analytics tables.
     * 
     * These indexes improve query performance for common analytics operations:
     * - Event tracking queries
     * - Metrics aggregation
     * - Time-series analysis
     * - RFM calculation
     */
    public function up(): void
    {
        // analytics_events - event tracking
        Schema::table('analytics_events', function (Blueprint $table) {
            // Composite index for tenant + time range queries
            $table->index(['tenant_id', 'occurred_at'], 'idx_events_tenant_occurred');
            
            // Composite index for user + time range queries (RFM calculation)
            $table->index(['user_id', 'occurred_at'], 'idx_events_user_occurred');
            
            // Index for event type filtering
            $table->index('event_type', 'idx_events_type');
            
            // Composite index for entity queries
            $table->index(['entity_type', 'entity_id'], 'idx_events_entity');
            
            // Index for monetary value queries
            $table->index('monetary_value', 'idx_events_monetary');
        });

        // analytics_daily_metrics - daily aggregation
        Schema::table('analytics_daily_metrics', function (Blueprint $table) {
            // Composite index for tenant + date range queries
            $table->index(['tenant_id', 'date'], 'idx_daily_tenant_date');
            
            // Index for date range queries across all tenants
            $table->index('date', 'idx_daily_date');
        });

        // analytics_seller_metrics - seller-specific metrics
        Schema::table('analytics_seller_metrics', function (Blueprint $table) {
            // Composite index for seller queries
            $table->index(['tenant_id', 'seller_id', 'date'], 'idx_seller_tenant_date');
            
            // Composite index for tenant + date range
            $table->index(['tenant_id', 'date'], 'idx_seller_tenant_date_simple');
        });

        // analytics_product_metrics - product-specific metrics
        Schema::table('analytics_product_metrics', function (Blueprint $table) {
            // Composite index for product queries
            $table->index(['tenant_id', 'product_id', 'date'], 'idx_product_tenant_date');
            
            // Index for seller + product queries
            $table->index(['seller_id', 'product_id'], 'idx_product_seller');
        });

        // analytics_user_metrics - user-specific metrics
        Schema::table('analytics_user_metrics', function (Blueprint $table) {
            // Composite index for user queries
            $table->index(['tenant_id', 'user_id', 'date'], 'idx_user_tenant_date');
        });

        // analytics_funnels - funnel analysis
        Schema::table('analytics_funnels', function (Blueprint $table) {
            // Composite index for funnel queries
            $table->index(['tenant_id', 'funnel_name', 'date'], 'idx_funnel_tenant_name_date');
            
            // Index for category filtering
            $table->index('category', 'idx_funnel_category');
            
            // Index for seller filtering
            $table->index('seller_id', 'idx_funnel_seller');
        });

        // analytics_retention_cohorts - retention analysis
        Schema::table('analytics_retention_cohorts', function (Blueprint $table) {
            // Composite index for cohort queries
            $table->index(['tenant_id', 'cohort_type', 'cohort_date'], 'idx_retention_tenant_type_date');
            
            // Index for cohort date range
            $table->index('cohort_date', 'idx_retention_date');
        });
    }

    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex('idx_events_tenant_occurred');
            $table->dropIndex('idx_events_user_occurred');
            $table->dropIndex('idx_events_type');
            $table->dropIndex('idx_events_entity');
            $table->dropIndex('idx_events_monetary');
        });

        Schema::table('analytics_daily_metrics', function (Blueprint $table) {
            $table->dropIndex('idx_daily_tenant_date');
            $table->dropIndex('idx_daily_date');
        });

        Schema::table('analytics_seller_metrics', function (Blueprint $table) {
            $table->dropIndex('idx_seller_tenant_date');
            $table->dropIndex('idx_seller_tenant_date_simple');
        });

        Schema::table('analytics_product_metrics', function (Blueprint $table) {
            $table->dropIndex('idx_product_tenant_date');
            $table->dropIndex('idx_product_seller');
        });

        Schema::table('analytics_user_metrics', function (Blueprint $table) {
            $table->dropIndex('idx_user_tenant_date');
        });

        Schema::table('analytics_funnels', function (Blueprint $table) {
            $table->dropIndex('idx_funnel_tenant_name_date');
            $table->dropIndex('idx_funnel_category');
            $table->dropIndex('idx_funnel_seller');
        });

        Schema::table('analytics_retention_cohorts', function (Blueprint $table) {
            $table->dropIndex('idx_retention_tenant_type_date');
            $table->dropIndex('idx_retention_date');
        });
    }
};
