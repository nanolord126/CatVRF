<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('food_order_id')->constrained('food_orders')->onDelete('cascade');
            $table->foreignId('courier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('customer_address');
            $table->decimal('delivery_lat', 10, 8)->nullable();
            $table->decimal('delivery_lon', 11, 8)->nullable();
            $table->string('delivery_point')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('eta_minutes')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['food_order_id', 'status']);
            $table->index('courier_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_delivery_orders');
    }
};
