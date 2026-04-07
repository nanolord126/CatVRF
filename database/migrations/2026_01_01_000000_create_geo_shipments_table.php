<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('geo_shipments')) {
            return;
        }

        Schema::create('geo_shipments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Уникальный идентификатор доставки');
            $table->unsignedBigInteger('tenant_id')->index()->comment('ID тенанта');
            $table->unsignedBigInteger('delivery_order_id')->index()->comment('Связь с заказом / Delivery');
            $table->unsignedBigInteger('courier_id')->nullable()->index()->comment('Назначенный курьер');
            
            $table->string('status')->default('pending')->index()->comment('Текущий статус (pending, in_transit, etc)');
            
            $table->decimal('pickup_lat', 10, 8)->comment('Широта точки забора');
            $table->decimal('pickup_lng', 11, 8)->comment('Долгота точки забора');
            $table->decimal('dropoff_lat', 10, 8)->comment('Широта точки доставки');
            $table->decimal('dropoff_lng', 11, 8)->comment('Долгота точки доставки');
            
            $table->decimal('current_lat', 10, 8)->nullable()->comment('Текущая широта курьера');
            $table->decimal('current_lng', 11, 8)->nullable()->comment('Текущая долгота курьера');
            
            $table->unsignedInteger('estimated_distance_meters')->nullable()->comment('Расчетная дистанция (OSRM)');
            $table->unsignedInteger('estimated_duration_seconds')->nullable()->comment('Расчетное время (ETA)');
            $table->unsignedBigInteger('calculated_cost')->nullable()->comment('Стоимость доставки в копейках');
            
            $table->string('correlation_id')->index()->comment('ID сквозной транзакции');
            
            $table->jsonb('tags')->nullable()->comment('Теги аналитики');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_shipments');
    }
};

