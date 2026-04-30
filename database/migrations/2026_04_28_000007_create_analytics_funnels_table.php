<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create analytics_funnels table
 *
 * Stores pre-calculated funnel data for common conversion funnels.
 * Funnels: AddToCart -> Checkout -> Paid, ProductView -> AddToCart -> Purchase, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_funnels', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->index();
            $table->string('funnel_name', 100)->index();
            $table->date('date')->index();
            
            // Funnel steps (JSON array of step names and counts)
            $table->json('steps');
            
            // Calculated metrics
            $table->decimal('total_conversion_rate', 5, 2)->default(0);
            
            // Dimensions for filtering
            $table->string('category', 100)->nullable();
            $table->unsignedInteger('seller_id')->nullable();
            
            $table->timestamp('calculated_at')->index();
            
            $table->unique(['tenant_id', 'funnel_name', 'date', 'category', 'seller_id']);
            $table->index(['date', 'funnel_name']);
            
            $table->timestamps();
        });

        // Note: DB::statement() requires MySQL 5.7+ or MariaDB 10.2+
        // For PostgreSQL, use COMMENT ON TABLE syntax instead
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_funnels');
    }
};
