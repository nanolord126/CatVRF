<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create analytics_daily_metrics table
 *
 * Stores aggregated daily metrics for the marketplace.
 * This is the main historical analytics table for KPIs and trends.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->index();
            $table->date('date')->index();
            
            // Order metrics
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('orders_revenue', 15, 2)->default(0);
            $table->decimal('orders_aov', 15, 2)->default(0); // Average Order Value
            
            // User metrics
            $table->unsignedInteger('users_active')->default(0);
            $table->unsignedInteger('users_new')->default(0);
            
            // Product metrics
            $table->unsignedInteger('products_viewed')->default(0);
            $table->unsignedInteger('products_added_to_cart')->default(0);
            
            // Seller metrics
            $table->unsignedInteger('sellers_active')->default(0);
            
            // Session metrics
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('page_views')->default(0);
            
            // Financial metrics
            $table->decimal('gmv', 15, 2)->default(0); // Gross Merchandise Value
            $table->unsignedInteger('refunds_count')->default(0);
            $table->decimal('refunds_amount', 15, 2)->default(0);
            
            // Calculated metrics
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('cart_abandonment_rate', 5, 2)->default(0);
            
            $table->timestamp('calculated_at')->index();
            
            $table->unique(['tenant_id', 'date']);
            $table->index(['date', 'tenant_id']);
            
            $table->timestamps();
        });

        // Add comment for GDPR/audit
        // Note: DB::statement() requires MySQL 5.7+ or MariaDB 10.2+
        // For PostgreSQL, use COMMENT ON TABLE syntax instead
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_metrics');
    }
};
