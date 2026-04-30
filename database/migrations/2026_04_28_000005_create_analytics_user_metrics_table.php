<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create analytics_user_metrics table
 *
 * Stores aggregated metrics per user for user analytics and RFM segmentation.
 * Contains PII (user_id) - must be anonymized for GDPR compliance when exported.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_user_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->index();
            $table->unsignedInteger('user_id')->index();
            $table->date('date')->index();
            
            // Session metrics
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('page_views')->default(0);
            
            // Engagement metrics
            $table->unsignedInteger('products_viewed')->default(0);
            $table->unsignedInteger('orders_placed')->default(0);
            
            // Financial metrics
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->decimal('avg_order_value', 15, 2)->default(0);
            
            // Cart metrics
            $table->unsignedInteger('cart_items')->default(0);
            $table->decimal('cart_value', 15, 2)->default(0);
            
            // RFM segmentation
            $table->string('rfm_segment', 20)->default('unknown')->index();
            $table->unsignedInteger('days_since_last_order')->default(999)->index();
            
            $table->timestamp('calculated_at')->index();
            
            $table->unique(['tenant_id', 'user_id', 'date']);
            $table->index(['date', 'user_id']);
            $table->index(['tenant_id', 'rfm_segment']);
            
            $table->timestamps();
        });

        DB::statement("ALTER TABLE analytics_user_metrics COMMENT = 'Daily aggregated metrics per user - contains PII, GDPR compliant with anonymization support'");
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_user_metrics');
    }
};
