<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Smart Notification Queue (Context-Aware)
        Schema::create('smart_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('channel'); // push, sms, email, in-app
            $table->string('trigger_context'); // location_entry, daily_routine, price_drop
            $table->float('urgency_score')->default(0.5); // 0.0 to 1.0 (Higher priority first)
            $table->timestamp('scheduled_send_at'); // AI Calculated "Best Time to Open"
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_opened')->default(false);
            $table->uuid('correlation_id');
            $table->timestamps();
        });

        // 2. Predictive Inventory Distribution (Stock Balance between Warehouses)
        Schema::create('predictive_stock_redistributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products'); // Assuming common product table
            $table->foreignId('source_warehouse_id')->constrained('warehouses');
            $table->foreignId('target_warehouse_id')->constrained('warehouses');
            $table->integer('suggested_quantity');
            $table->float('confidence_level'); // 0.0 to 1.0
            $table->string('reason_tag'); // 'upcoming_event', 'low_local_stock', 'seasonal_surge'
            $table->string('status')->default('draft'); // draft, in_transit, completed
            $table->json('ai_evidence')->nullable(); // Demand graph data for confirmation
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictive_stock_redistributions');
        Schema::dropIfExists('smart_notifications');
    }
};
