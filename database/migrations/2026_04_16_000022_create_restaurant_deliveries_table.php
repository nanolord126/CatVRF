<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('courier_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->string('status')->default('pending');
            
            $table->text('pickup_address');
            $table->decimal('pickup_lat', 10, 8);
            $table->decimal('pickup_lon', 11, 8);
            
            $table->text('delivery_address');
            $table->string('delivery_city');
            $table->decimal('delivery_lat', 10, 8);
            $table->decimal('delivery_lon', 11, 8);
            
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            $table->boolean('route_optimized_by_ai')->default(false);
            $table->decimal('ai_confidence_score', 5, 3)->nullable();
            
            $table->timestamp('pickup_time')->nullable();
            $table->timestamp('delivery_time')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('delivery_notes')->nullable();
            
            $table->boolean('signature_required')->default(false);
            $table->boolean('signature_received')->default(false);
            $table->boolean('photo_required')->default(false);
            $table->boolean('photo_received')->default(false);
            
            $table->string('contact_phone');
            $table->string('contact_name');
            $table->string('tracking_code')->unique();
            
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'courier_id']);
            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'restaurant_id']);
            $table->index(['tenant_id', 'tracking_code']);
            $table->index('tracking_code');
            $table->index('uuid');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('restaurant_orders')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('courier_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('restaurant_deliveries', function (Blueprint $table) {
            $table->comment('Restaurant deliveries with AI/ML optimization');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_deliveries');
    }
};
