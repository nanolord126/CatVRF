<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LogisticsVerticalMigration
 * 
 * Создание ядра логистики 2026: курьеры, транспорт, зоны, заказы, маршруты.
 * Канон 2026: UUID, JSONB, CorrelationID, Tenant_ID, GeoJSON (PostGIS).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. ТРАНСПОРТНЫЕ СРЕДСТВА (Vehicles)
        if (!Schema::hasTable('logistics_vehicles')) {
            Schema::create('logistics_vehicles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('type'); // car, bike, scooter, walking
                $table->string('model')->nullable();
                $table->string('license_plate')->nullable()->index();
                $table->integer('load_capacity')->default(0); // в граммах/кг
                $table->jsonb('metadata')->nullable(); // тех. состояние, страховка
                $table->boolean('is_active')->default(true)->index();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Транспортные средства для логистики');
                $table->index(['tenant_id', 'type']);
            });
        }

        // 2. КУРЬЕРЫ (Couriers)
        if (!Schema::hasTable('logistics_couriers')) {
            Schema::create('logistics_couriers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('vehicle_id')->nullable()->constrained('logistics_vehicles')->onDelete('set null');
                $table->string('status')->default('offline')->index(); // offline, online, busy, vacation
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->geometry('current_location')->nullable(); // PostGIS или GEOMETRY
                $table->jsonb('work_hours')->nullable();
                $table->jsonb('tags')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->integer('commission_percentage')->default(15); // индивидуальная комиссия
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Курьеры платформы CatVRF');
                $table->index(['tenant_id', 'status']);
            });
        }

        // 3. ГЕО-ЗОНЫ И SURGE (Geo & Surge Zones)
        if (!Schema::hasTable('logistics_geo_zones')) {
            Schema::create('logistics_geo_zones', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->json('boundaries'); // Координаты зоны (Polygon)
                $table->string('type')->default('delivery'); // delivery, pickup, restricted
                $table->decimal('base_price_multiplier', 5, 2)->default(1.00);
                $table->jsonb('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Географические зоны обслуживания');
            });
        }

        if (!Schema::hasTable('logistics_surge_zones')) {
            Schema::create('logistics_surge_zones', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('geo_zone_id')->constrained('logistics_geo_zones')->onDelete('cascade');
                $table->decimal('multiplier', 5, 2)->default(1.00);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('reason')->nullable(); // weather, high_demand, event
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Зоны повышенного спроса (Surge Pricing)');
                $table->index(['tenant_id', 'is_active', 'starts_at', 'ends_at']);
            });
        }

        // 4. ЗАКАЗЫ НА ДОСТАВКУ (Delivery Orders)
        if (!Schema::hasTable('logistics_delivery_orders')) {
            Schema::create('logistics_delivery_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('client_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('courier_id')->nullable()->constrained('logistics_couriers')->onDelete('set null');
                $table->string('status')->default('pending')->index(); // pending, searching, accepted, picking, delivering, completed, cancelled
                $table->string('type')->default('b2c')->index(); // b2c, b2b (магазин-курьер)
                
                $table->string('pickup_address');
                $table->geometry('pickup_location');
                $table->string('dropoff_address');
                $table->geometry('dropoff_location');
                
                $table->integer('price_total')->default(0); // в копейках
                $table->integer('price_delivery')->default(0);
                $table->integer('price_surge')->default(0);
                $table->integer('price_commission')->default(0);
                
                $table->jsonb('cargo_info')->nullable(); // габариты, вес, хрупкость
                $table->jsonb('tracking_history')->nullable(); // логи перемещения
                $table->timestamp('estimated_delivery_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                
                $table->string('idempotency_key')->nullable()->unique();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Заказы на курьерскую доставку');
                $table->index(['tenant_id', 'status', 'type']);
            });
        }

        // 5. МАРШРУТЫ (Routes)
        if (!Schema::hasTable('logistics_routes')) {
            Schema::create('logistics_routes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('delivery_order_id')->constrained('logistics_delivery_orders')->onDelete('cascade');
                $table->linestring('path')->nullable(); // Геометрия маршрута
                $table->integer('planned_distance')->default(0); // в метрах
                $table->integer('planned_duration')->default(0); // в секундах
                $table->jsonb('waypoints')->nullable();
                $table->jsonb('osrm_data')->nullable(); // данные от роутера (OSRM/Yandex)
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Оптимизированные маршруты доставки');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_routes');
        Schema::dropIfExists('logistics_delivery_orders');
        Schema::dropIfExists('logistics_surge_zones');
        Schema::dropIfExists('logistics_geo_zones');
        Schema::dropIfExists('logistics_couriers');
        Schema::dropIfExists('logistics_vehicles');
    }
};


