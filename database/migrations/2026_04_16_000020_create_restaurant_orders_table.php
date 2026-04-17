<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('restaurant_orders')) {
            return;
        }

        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('user_id');
            $table->uuid('uuid')->unique();
            $table->string('order_number')->unique();
            $table->string('status')->default('pending');
            $table->enum('delivery_type', ['delivery', 'pickup', 'dine_in'])->default('delivery');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency')->default('RUB');
            
            $table->text('delivery_address')->nullable();
            $table->string('delivery_city')->nullable();
            $table->decimal('delivery_lat', 10, 8)->nullable();
            $table->decimal('delivery_lon', 11, 8)->nullable();
            
            $table->timestamp('scheduled_delivery_time')->nullable();
            $table->timestamp('estimated_delivery_time')->nullable();
            $table->timestamp('actual_delivery_time')->nullable();
            $table->text('delivery_notes')->nullable();
            
            $table->string('payment_method')->default('online');
            $table->string('payment_status')->default('pending');
            $table->string('payment_id')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            
            $table->json('special_requests')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'restaurant_id']);
            $table->index(['tenant_id', 'delivery_type']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('order_number');
            $table->index('uuid');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->comment('Restaurant orders with delivery integration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_orders');
    }
};
