<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026: Слой 1 — Инфраструктура (Taxi, Delivery, Logistics).
 * Идемпотентность, комментарии, scoping, аналитика.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Автопарки (Fleets) — B2B сущность
        if (!Schema::hasTable('taxi_fleets')) {
            Schema::create('taxi_fleets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index()->comment('Изоляция по тенанту');
                $table->string('name')->comment('Название автопарка');
                $table->string('inn', 12)->index()->comment('ИНН для B2B расчётов');
                $table->decimal('commission_rate', 5, 2)->default(5.00)->comment('Комиссия парка в %');
                $table->jsonb('settings')->nullable()->comment('Настройки выплат, графиков');
                $table->string('status')->default('active')->index();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable()->comment('Теги для аналитики');
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Автопарки (Fleets) для управления водителями и комиссиями');
            });
        }

        // 2. Водители (Drivers)
        if (!Schema::hasTable('taxi_drivers')) {
            Schema::create('taxi_drivers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('fleet_id')->nullable()->constrained('taxi_fleets')->onDelete('set null');
                $table->string('license_number')->unique()->comment('Водительское удостоверение');
                $table->boolean('is_active')->default(false)->index();
                $table->boolean('is_available')->default(false)->index()->comment('На линии');
                $table->decimal('rating', 3, 2)->default(5.00)->index();
                $table->string('current_location_point')->nullable()->comment('Последние координаты (string point)');
                $table->jsonb('balance_meta')->nullable()->comment('Кэш баланса, лимиты');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Водители такси и курьеры доставки');
            });
        }

        // 3. Транспортные средства (Vehicles)
        if (!Schema::hasTable('taxi_vehicles')) {
            Schema::create('taxi_vehicles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('driver_id')->nullable()->constrained('taxi_drivers');
                $table->string('brand')->index();
                $table->string('model')->index();
                $table->string('plate_number')->unique()->index();
                $table->string('color');
                $table->year('year');
                $table->string('class')->default('economy')->index()->comment('economy, comfort, business, delivery');
                $table->jsonb('documents')->nullable()->comment('СТС, ОСАГО, Лицензия такси');
                $table->string('status')->default('active');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Транспортные средства водителей и парков');
            });
        }

        // 4. Зоны повышенного спроса (Surge Zones)
        if (!Schema::hasTable('taxi_surge_zones')) {
            Schema::create('taxi_surge_zones', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name');
                $table->decimal('multiplier', 4, 2)->default(1.00)->comment('Коэффициент (например 1.5)');
                $table->jsonb('boundary_polygon')->comment('Координаты полигона зоны');
                $table->timestamp('expires_at')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Гео-зоны для динамического ценообразования (Surge)');
            });
        }

        // 5. Поездки (Taxi Rides)
        if (!Schema::hasTable('taxi_rides')) {
            Schema::create('taxi_rides', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('passenger_id')->constrained('users')->comment('Кто заказал (B2C/B2B сотрудник)');
                $table->foreignId('driver_id')->nullable()->constrained('taxi_drivers');
                $table->foreignId('vehicle_id')->nullable()->constrained('taxi_vehicles');
                $table->string('status')->default('pending')->index()->comment('pending, accepted, picking, in_progress, completed, cancelled');
                
                // Гео-данные
                $table->string('pickup_address');
                $table->string('pickup_point')->index();
                $table->string('dropoff_address');
                $table->string('dropoff_point')->index();
                $table->decimal('distance_km', 10, 2)->nullable();
                
                // Финансы (в копейках)
                $table->integer('base_price')->default(0);
                $table->decimal('surge_multiplier', 4, 2)->default(1.00);
                $table->integer('total_price')->default(0);
                $table->integer('fleet_commission')->default(0);
                $table->integer('platform_commission')->default(0);
                
                $table->string('idempotency_key')->nullable()->unique();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('metadata')->nullable()->comment('ML-скоринг, логи путей');
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Поездки такси с расчётом стоимости и комиссий');
            });
        }

        // 6. Заказы доставки (Delivery Orders)
        if (!Schema::hasTable('delivery_orders')) {
            Schema::create('delivery_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('sender_id')->constrained('users');
                $table->foreignId('courier_id')->nullable()->constrained('taxi_drivers');
                $table->string('status')->default('pending')->index();
                
                // Груз
                $table->string('package_type')->comment('box, document, food, fragile');
                $table->decimal('weight_kg', 8, 2)->default(0);
                $table->string('recipient_name');
                $table->string('recipient_phone');
                
                // Гео
                $table->string('pickup_point');
                $table->string('dropoff_point');
                
                $table->integer('price')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('metadata')->nullable()->comment('Фото груза, доп. инструкции');
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Заказы логистики и курьерской доставки');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('taxi_rides');
        Schema::dropIfExists('taxi_surge_zones');
        Schema::dropIfExists('taxi_vehicles');
        Schema::dropIfExists('taxi_drivers');
        Schema::dropIfExists('taxi_fleets');
    }
};
