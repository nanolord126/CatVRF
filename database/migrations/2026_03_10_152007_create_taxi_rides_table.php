<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_rides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('passenger_id')->constrained('users');
            $table->enum('vehicle_class', ['economy', 'comfort', 'premium'])->default('economy');
            $table->decimal('pickup_lat', 10, 8);
            $table->decimal('pickup_lng', 11, 8);
            $table->decimal('dropoff_lat', 10, 8);
            $table->decimal('dropoff_lng', 11, 8);
            $table->decimal('distance_km', 10, 2);
            $table->decimal('fare_amount', 10, 2);
            $table->enum('status', ['requested', 'accepted', 'completed', 'cancelled'])->default('requested');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_rides');
    }
};
