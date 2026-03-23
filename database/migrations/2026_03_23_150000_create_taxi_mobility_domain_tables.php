<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для Раздела 3: Такси, Грузоперевозки и Мобильность (КАНОН 2026)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('taxi_drivers')) {
            Schema::create('taxi_drivers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('license_number')->unique();
                $table->float('rating')->default(5.0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_online')->default(false);
                $table->jsonb('current_location')->nullable(); // Храним [lat, lng]
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Водители такси и курьеры грузоперевозок');
            });
        }

        if (!Schema::hasTable('taxi_rides')) {
            Schema::create('taxi_rides', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('passenger_id')->index();
                $table->unsignedBigInteger('driver_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                
                $table->string('pickup_address');
                $table->string('dest_address');
                // В PostgreSQL используем geometry(Point, 4326), если есть PostGIS,
                // Но для совместимости в базовом Laravel Laravel-дропе — jsonb или float
                $table->jsonb('route_data')->nullable(); // OSRM path geometry
                
                $table->string('status')->index(); // pending, accepted, on_way, arrived, started, completed, cancelled
                $table->bigInteger('price_cents');
                $table->integer('distance_meters')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->float('surge_multiplier')->default(1.0);
                
                $table->enum('cargo_type', ['passenger', 'express', 'cargo', 'oversized'])->default('passenger');
                $table->float('cargo_weight_kg')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Поездки и логистические наряды');
            });
        }

        if (!Schema::hasTable('taxi_vehicles')) {
            Schema::create('taxi_vehicles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('driver_id')->nullable()->index();
                
                $table->string('brand');
                $table->string('model');
                $table->string('license_plate')->unique();
                $table->string('color');
                $table->string('class')->index(); // economy, comfort, business, cargo
                $table->year('year');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Транспортные средства');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_vehicles');
        Schema::dropIfExists('taxi_rides');
        Schema::dropIfExists('taxi_drivers');
    }
};
