<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Logistics Vertical 2026.
 * Tables: couriers, delivery_orders, geo_zones, surge_zones, logistics_vehicles, logistics_routes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('geo_zones')) {
            Schema::create('geo_zones', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name')->comment('Название зоны (Центр, Север, и т.д.)');
                $table->jsonb('polygon')->comment('Координаты полигона зоны доставки');
                $table->integer('base_delivery_price')->default(25000)->comment('Базовая цена в копейках');
                $table->float('distance_multiplier')->default(1.0)->comment('Коэффициент за расстояние');
                $table->boolean('is_active')->default(true);
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_active']);
                $table->comment('Гео-зоны обслуживания для логистики');
            });
        }

        if (!Schema::hasTable('surge_zones')) {
            Schema::create('surge_zones', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('geo_zone_id')->constrained('geo_zones')->onDelete('cascade');
                $table->float('multiplier')->default(1.0)->comment('Текущий коэффициент спроса (1.2, 1.5, 2.0)');
                $table->string('reason')->nullable()->comment('Причина (час пик, дождь, праздник)');
                $table->timestamp('expires_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['geo_zone_id', 'expires_at']);
                $table->comment('Динамические зоны повышенного спроса (Surge)');
            });
        }

        if (!Schema::hasTable('logistics_vehicles')) {
            Schema::create('logistics_vehicles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->enum('type', ['bike', 'car', 'van', 'truck', 'scooter'])->default('bike');
                $table->string('brand')->nullable();
                $table->string('model')->nullable();
                $table->string('license_plate')->nullable()->unique();
                $table->integer('load_capacity_kg')->default(50);
                $table->boolean('is_active')->default(true);
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'type']);
                $table->comment('Транспортные средства курьеров');
            });
        }

        if (!Schema::hasTable('couriers')) {
            Schema::create('couriers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('vehicle_id')->nullable()->constrained('logistics_vehicles');
                $table->enum('status', ['online', 'offline', 'busy'])->default('offline');
                $table->geometry('current_location')->nullable();
                $table->float('rating')->default(5.0);
                $table->integer('commission_percent')->default(15)->comment('Комиссия платформы для этого курьера');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status']);
                $table->comment('Курьеры и их текущий статус');
            });
        }

        if (!Schema::hasTable('delivery_orders')) {
            Schema::create('delivery_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups');
                $table->foreignId('courier_id')->nullable()->constrained('couriers');
                $table->enum('status', ['pending', 'assigned', 'picked_up', 'delivered', 'cancelled'])->default('pending');
                $table->enum('mode', ['b2b', 'b2c'])->default('b2c');
                $table->jsonb('pickup_address')->comment('Адрес забора (json: address, lat, lon)');
                $table->jsonb('delivery_address')->comment('Адрес доставки (json: address, lat, lon)');
                $table->integer('total_amount')->comment('Стоимость доставки в копейках');
                $table->float('surge_multiplier')->default(1.0);
                $table->string('idempotency_key')->nullable()->unique();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'status', 'mode']);
                $table->comment('Заказы на доставку');
            });
        }

        if (!Schema::hasTable('logistics_routes')) {
            Schema::create('logistics_routes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('delivery_order_id')->constrained('delivery_orders')->onDelete('cascade');
                $table->jsonb('path_coordinates')->comment('Массив точек маршрута (lat, lon)');
                $table->integer('estimated_distance_meters')->default(0);
                $table->integer('estimated_time_minutes')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Оптимизированные маршруты для заказов');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_routes');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('couriers');
        Schema::dropIfExists('logistics_vehicles');
        Schema::dropIfExists('surge_zones');
        Schema::dropIfExists('geo_zones');
    }
};


