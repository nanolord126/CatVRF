<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create analytics_events table
 *
 * Stores raw analytics events for real-time processing and funnel analysis.
 * This is the event stream table - events are processed into aggregated tables.
 * Data retention: 90 days (configurable).
 * 
 * GDPR: Contains user_id - must support right to be forgotten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('event_type', 100)->index();
            $table->string('entity_type', 100)->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            
            // Event metadata (JSON)
            $table->json('metadata')->nullable();
            
            // Dimensions for filtering
            $table->string('category', 100)->nullable()->index();
            $table->unsignedInteger('seller_id')->nullable()->index();
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->string('device', 20)->nullable();
            $table->string('os', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('traffic_source', 50)->nullable();
            $table->string('utm_medium', 50)->nullable();
            $table->string('utm_source', 50)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            
            // Geographic dimensions
            $table->string('country', 50)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            
            // Monetary value (if applicable)
            $table->decimal('monetary_value', 15, 2)->nullable();
            
            $table->timestamp('occurred_at')->index();
            $table->timestamp('processed_at')->nullable()->index();
            
            $table->index(['tenant_id', 'event_type', 'occurred_at']);
            $table->index(['occurred_at', 'tenant_id']);
            
            $table->timestamps();
        });

        // Add partitioning hint for ClickHouse migration
        // Note: DB::statement() requires MySQL 5.7+ or MariaDB 10.2+
        // For PostgreSQL, use COMMENT ON TABLE syntax instead
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
