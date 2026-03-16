<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ТАКСОПАРКИ (CAR FLEETS)
        Schema::create('taxi_fleets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(5.00); // % парка
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // 2. АВТОМОБИЛИ (CARS)
        Schema::create('taxi_cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_id')->constrained('taxi_fleets')->onDelete('cascade');
            $table->string('model');
            $table->string('plate_number')->unique();
            $table->string('color')->nullable();
            $table->string('class')->default('economy'); // economy, comfort, business
            $table->string('status')->default('active'); // active, maintenance, inactive
            $table->timestamps();
        });

        // 3. ВОДИТЕЛИ (DRIVERS) - расширяем профиль
        Schema::create('taxi_driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->foreignId('fleet_id')->constrained('taxi_fleets');
            $table->foreignId('current_car_id')->nullable()->constrained('taxi_cars');
            $table->string('license_number')->nullable();
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->boolean('is_online')->default(false);
            $table->json('current_geo')->nullable(); // {lat, lng}
            $table->timestamps();
        });

        // 4. СМЕНЫ ВОДИТЕЛЕЙ (DRIVER SHIFTS)
        Schema::create('taxi_driver_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('taxi_driver_profiles');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_distance_km', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_driver_shifts');
        Schema::dropIfExists('taxi_driver_profiles');
        Schema::dropIfExists('taxi_cars');
        Schema::dropIfExists('taxi_fleets');
    }
};
