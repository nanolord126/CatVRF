<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Core for Auto Service & Parts Domain 2026.
 * 
 * Includes:
 * - auto_parts: SKU, GTIN, Price, Category
 * - auto_vehicles: VIN-id, Owner, Brand, Model
 * - auto_services: Repair types, Prices, Duration
 * - auto_repair_orders: Statuses, Spare parts list, Labor cost
 * - auto_catalog_brands: Manufacturers
 * 
 * Multi-Tenancy (tenant_id) + Global Tracking (correlation_id) applied everywhere.
 * UTF-8 no BOM + CRLF Line Endings.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Таблица брендов (бренды авто и бренды запчастей)
        if (!Schema::hasTable('auto_catalog_brands')) {
            Schema::create('auto_catalog_brands', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name')->index();
                $table->string('slug')->unique();
                $table->enum('type', ['vehicle', 'part', 'mixed'])->default('mixed');
                $table->string('country')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->jsonb('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Бренды автомобилей и производителей автозапчастей');
            });
        }

        // 2. Таблица автомобилей (Клиенты / Автопарки)
        if (!Schema::hasTable('auto_vehicles')) {
            Schema::create('auto_vehicles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('auto_catalog_brand_id')->index();
                $table->unsignedBigInteger('owner_id')->index(); // user_id (B2C) or business_group_id (B2B)
                $table->string('owner_type')->default('user'); // user, business_group
                $table->string('vin', 17)->unique()->index();
                $table->string('model')->index();
                $table->year('year_produced')->nullable();
                $table->string('engine_code')->nullable();
                $table->string('license_plate')->nullable()->index();
                $table->unsignedInteger('current_mileage')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->jsonb('metadata')->nullable(); // Цвет, комплектация и т.д.
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('База данных автомобилей клиентов');
            });
        }

        // 3. Таблица автозапчастей (Каталог)
        if (!Schema::hasTable('auto_parts')) {
            Schema::create('auto_parts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('auto_catalog_brand_id')->index();
                $table->string('sku')->index(); // Артикул
                $table->string('gtin')->nullable()->index(); // Штрих-код
                $table->string('oem_number')->nullable()->index(); // Оригинальный номер
                $table->string('name')->index();
                $table->text('description')->nullable();
                $table->bigInteger('price_kopecks')->default(0);
                $table->bigInteger('wholesale_price_kopecks')->default(0); // Опт для B2B
                $table->integer('stock_quantity')->default(0);
                $table->string('category')->index(); // Двигатель, Ходовая, ТО и т.д.
                $table->jsonb('compatibility_vin')->nullable(); // С какими VIN совместимо (JSON)
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->jsonb('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Каталог автозапчастей, шин и расходников');
            });
        }

        // 4. Таблица услуг автосервиса (СТО)
        if (!Schema::hasTable('auto_services')) {
            Schema::create('auto_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name')->index();
                $table->text('description')->nullable();
                $table->bigInteger('labor_price_kopecks')->default(0); // Цена работ
                $table->integer('estimated_minutes')->default(60); // Нормо-часы/минуты
                $table->string('category')->default('general')->index(); // Ремонт, Диагностика, Мойка
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Список услуг автосервиса (СТО)');
            });
        }

        // 5. Таблица заказов на ремонт (СТО)
        if (!Schema::hasTable('auto_repair_orders')) {
            Schema::create('auto_repair_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('auto_vehicle_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index(); // Филиал СТО
                $table->enum('status', ['draft', 'appointment_booked', 'diagnosing', 'parts_waiting', 'repairing', 'ready', 'cancelled', 'completed'])->default('draft')->index();
                $table->timestamp('appointment_at')->nullable()->index();
                $table->bigInteger('total_amount_kopecks')->default(0);
                $table->bigInteger('parts_amount_kopecks')->default(0);
                $table->bigInteger('labor_amount_kopecks')->default(0);
                $table->jsonb('items')->nullable(); // Список используемых запчастей и услуг
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->text('client_notes')->nullable();
                $table->text('mechanic_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Заказ-наряды на ремонт и обслуживание в СТО');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_repair_orders');
        Schema::dropIfExists('auto_services');
        Schema::dropIfExists('auto_parts');
        Schema::dropIfExists('auto_vehicles');
        Schema::dropIfExists('auto_catalog_brands');
    }
};
