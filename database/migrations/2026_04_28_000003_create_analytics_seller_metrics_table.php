<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create analytics_seller_metrics table
 *
 * Stores aggregated metrics per seller for seller analytics dashboard.
 * Multi-tenant: each tenant can see only their sellers' data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_seller_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->index();
            $table->unsignedInteger('seller_id')->index();
            $table->date('date')->index();
            
            // Order metrics
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('orders_revenue', 15, 2)->default(0);
            $table->decimal('orders_aov', 15, 2)->default(0);
            
            // Product metrics
            $table->unsignedInteger('products_viewed')->default(0);
            $table->unsignedInteger('products_sold')->default(0);
            
            // Customer metrics
            $table->unsignedInteger('unique_customers')->default(0);
            
            // Calculated metrics
            $table->decimal('conversion_rate', 5, 2)->default(0);
            
            // Refund metrics
            $table->unsignedInteger('refunds_count')->default(0);
            $table->decimal('refunds_amount', 15, 2)->default(0);
            
            // Seller rating
            $table->decimal('seller_rating', 3, 2)->default(0);
            
            $table->timestamp('calculated_at')->index();
            
            $table->unique(['tenant_id', 'seller_id', 'date']);
            $table->index(['date', 'seller_id']);
            $table->index(['tenant_id', 'date']);
            
            $table->timestamps();
        });

        // Note: DB::statement() requires MySQL 5.7+ or MariaDB 10.2+
        // For PostgreSQL, use COMMENT ON TABLE syntax instead
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_seller_metrics');
    }
};
