<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create analytics_hourly_metrics table
 *
 * Stores aggregated hourly metrics for real-time analytics.
 * Can be migrated to ClickHouse for high-performance queries.
 * Data retention: 48-72 hours in MySQL, longer in ClickHouse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_hourly_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->index();
            $table->timestamp('hour')->index();
            
            // Order metrics
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('orders_revenue', 15, 2)->default(0);
            
            // User metrics
            $table->unsignedInteger('users_active')->default(0);
            
            // Product metrics
            $table->unsignedInteger('products_viewed')->default(0);
            $table->unsignedInteger('products_added_to_cart')->default(0);
            
            // Session metrics
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('page_views')->default(0);
            
            // Financial metrics
            $table->decimal('gmv', 15, 2)->default(0);
            
            $table->timestamp('calculated_at')->index();
            
            $table->unique(['tenant_id', 'hour']);
            $table->index(['hour', 'tenant_id']);
            
            $table->timestamps();
        });

        // Add partitioning hint for ClickHouse migration
        // Note: DB::statement() requires MySQL 5.7+ or MariaDB 10.2+
        // For PostgreSQL, use COMMENT ON TABLE syntax instead
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_hourly_metrics');
    }
};
