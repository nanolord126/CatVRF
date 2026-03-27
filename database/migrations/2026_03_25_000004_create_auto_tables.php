<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * КАНОН 2026: Migration (Auto Domain).
     * Единая структура для Такси, СТО, Мойки и Продаж запчастей.
     */
    public function up(): void
    {
        // 1. ТРАНСПОРТ (Vehicle)
        if (!Schema::hasTable('auto_vehicles')) {
            Schema::create('auto_vehicles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('business_group_id')->nullable()->index();
                
                $table->string('vin', 17)->nullable()->index();
                $table->string('license_plate')->nullable()->index();
                $table->string('brand')->index();
                $table->string('model')->index();
                $table->integer('year')->nullable();
                $table->string('color')->nullable();
                
                $table->enum('type', ['taxi', 'fleet', 'private', 'sale'])->default('private');
                $table->enum('status', ['active', 'repair', 'sold', 'wash', 'ride'])->default('active');
                
                $table->jsonb('technical_specs')->nullable(); // Двигатель, КПП, привод
                $table->jsonb('amenities')->nullable(); // Кондиционер, кожа, мультимедиа
                
                $table->decimal('price_kopecks', 20, 0)->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Реестр транспортных средств вертикали Auto');
            });
        }

        // 2. ЗАКАЗЫ СТО (Repair Orders)
        if (!Schema::hasTable('auto_repair_orders')) {
            Schema::create('auto_repair_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('vehicle_id')->constrained('auto_vehicles');
                $table->foreignId('client_id')->index();
                
                $table->enum('status', ['pending', 'diagnostic', 'in_progress', 'testing', 'completed', 'cancelled'])->default('pending');
                $table->text('client_complaint')->nullable();
                $table->text('mechanic_report')->nullable();
                
                $table->decimal('labor_cost_kopecks', 20, 0)->default(0);
                $table->decimal('parts_cost_kopecks', 20, 0)->default(0);
                $table->decimal('total_cost_kopecks', 20, 0)->default(0);
                
                $table->jsonb('parts_list')->nullable(); // Списанные запчасти
                $table->jsonb('ai_estimate')->nullable(); // Оценка AI Vision (повреждения)
                
                $table->timestamp('planned_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Заказы на обслуживание и ремонт (СТО)');
            });
        }

        // 3. ЗАПЧАСТИ (Parts/Inventory)
        if (!Schema::hasTable('auto_parts')) {
            Schema::create('auto_parts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                
                $table->string('sku')->index();
                $table->string('oem_number')->nullable()->index();
                $table->string('name')->index();
                $table->string('brand')->index();
                
                $table->integer('stock_quantity')->default(0);
                $table->integer('min_threshold')->default(5);
                
                $table->decimal('purchase_price_kopecks', 20, 0);
                $table->decimal('sale_price_kopecks', 20, 0);
                
                $table->jsonb('compatibility')->nullable(); // Совместимые модели VIN/Brand
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Склад запчастей и аксессуаров');
            });
        }

        // 4. ТАКСИ (Taxi Rides)
        if (!Schema::hasTable('auto_taxi_rides')) {
            Schema::create('auto_taxi_rides', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('vehicle_id')->constrained('auto_vehicles');
                $table->foreignId('driver_id')->index();
                $table->foreignId('passenger_id')->index();
                
                $table->point('pickup_point');
                $table->point('dropoff_point');
                $table->string('pickup_address');
                $table->string('dropoff_address');
                
                $table->enum('status', ['searching', 'accepted', 'arrived', 'riding', 'completed', 'cancelled'])->default('searching');
                $table->decimal('price_kopecks', 20, 0);
                $table->decimal('surge_multiplier', 5, 2)->default(1.0);
                
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('История поездок такси (вертикаль Auto)');
            });
        }

        // 5. МОЙКА (Wash Bookings)
        if (!Schema::hasTable('auto_wash_bookings')) {
            Schema::create('auto_wash_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('vehicle_id')->nullable()->constrained('auto_vehicles');
                
                $table->string('service_name'); // Кузов, Комплекс, Детейлинг
                $table->decimal('price_kopecks', 20, 0);
                $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
                
                $table->timestamp('scheduled_at')->index();
                $table->integer('duration_minutes')->default(30);
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Бронирование автомойки');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_wash_bookings');
        Schema::dropIfExists('auto_taxi_rides');
        Schema::dropIfExists('auto_repair_orders');
        Schema::dropIfExists('auto_parts');
        Schema::dropIfExists('auto_vehicles');
    }
};
