<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_delivery_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->constrained('restaurant_orders')->onDelete('cascade');
            $table->foreignId('courier_id')->nullable()->constrained('delivery_couriers')->onDelete('set null');
            $table->enum('status', ['assigned', 'picked_up', 'delivering', 'delivered', 'cancelled'])->default('assigned');
            $table->timestamp('assigned_at');
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('pickup_address');
            $table->string('delivery_address');
            $table->json('delivery_coordinates');
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->string('current_address')->nullable();
            $table->decimal('estimated_distance_km', 5, 2)->nullable();
            $table->decimal('remaining_distance_km', 5, 2)->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('estimated_arrival_minutes')->nullable();
            $table->decimal('delivery_fee', 10, 2)->nullable();
            $table->json('route_data')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->index(['tenant_id', 'order_id', 'status']);
            $table->index(['courier_id', 'status']);
            $table->index(['current_latitude', 'current_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_delivery_tracking');
    }
};
