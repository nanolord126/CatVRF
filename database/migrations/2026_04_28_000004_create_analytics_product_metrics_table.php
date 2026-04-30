<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create analytics_product_metrics table
 *
 * Stores aggregated metrics per product for product analytics.
 * Used for top products, conversion funnels, and inventory optimization.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_product_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->index();
            $table->unsignedInteger('product_id')->index();
            $table->unsignedInteger('seller_id')->nullable()->index();
            $table->date('date')->index();
            
            // Engagement metrics
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('add_to_cart')->default(0);
            $table->unsignedInteger('purchases')->default(0);
            $table->unsignedInteger('unique_viewers')->default(0);
            
            // Financial metrics
            $table->decimal('revenue', 15, 2)->default(0);
            
            // Calculated metrics
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('cart_conversion_rate', 5, 2)->default(0);
            
            // Refund metrics
            $table->unsignedInteger('refunds')->default(0);
            $table->decimal('refund_rate', 5, 2)->default(0);
            
            // Rating
            $table->decimal('avg_rating', 3, 2)->default(0);
            
            $table->timestamp('calculated_at')->index();
            
            $table->unique(['tenant_id', 'product_id', 'date']);
            $table->index(['date', 'product_id']);
            $table->index(['tenant_id', 'date']);
            
            $table->timestamps();
        });

        // Note: DB::statement() requires MySQL 5.7+ or MariaDB 10.2+
        // For PostgreSQL, use COMMENT ON TABLE syntax instead
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_product_metrics');
    }
};
