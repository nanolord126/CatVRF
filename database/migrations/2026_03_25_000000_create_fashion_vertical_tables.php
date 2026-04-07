<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНЬОН 2026 — ПОЛНАЯ РЕАЛИЗАЦИЯ ВЕРТИКАЛИ FASHION
 * 
 * Содержит 6 критических таблиц: fashion_stores, fashion_products (B2C/B2B), 
 * fashion_sizes, fashion_collections, fashion_reviews, fashion_b2b_orders.
 * 
 * Обязательно: UUID, correlation_id, tenant_id, JSONB.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fashion_stores')) {
            Schema::create('fashion_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Название магазина');
                $table->string('slug')->unique();
                $table->string('inn', 12)->nullable()->index()->comment('ИНН для B2B режима');
                $table->string('type')->default('boutique')->comment('boutique, retail, outlet, showroom');
                $table->jsonb('schedule_json')->nullable()->comment('Расписание работы по дням');
                $table->float('rating')->default(0);
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Материнская таблица магазинов и шоурумов Fashion');
            });
        }

        if (!Schema::hasTable('fashion_products')) {
            Schema::create('fashion_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('fashion_store_id')->constrained('fashion_stores')->onDelete('cascade');
                $table->string('name')->comment('Название товара');
                $table->text('description')->nullable();
                $table->string('sku')->index();
                $table->string('brand')->index();
                $table->string('color')->index();
                $table->string('material')->nullable();
                $table->integer('price_b2c')->comment('Розничная цена в копейках');
                $table->integer('price_b2b')->nullable()->comment('Оптовая цена в копейках');
                $table->integer('old_price')->nullable()->comment('Предыдущая цена (если подешевело)');
                $table->integer('stock_quantity')->default(0);
                $table->integer('reserve_quantity')->default(0);
                $table->jsonb('images')->nullable()->comment('Массив URL изображений');
                $table->jsonb('attributes')->nullable()->comment('Ткань, стиль, сезон, крой');
                $table->string('status')->default('active')->index();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Товары: одежда, обувь, аксессуары. Поддержка B2C/B2B цен.');
            });
        }

        if (!Schema::hasTable('fashion_sizes')) {
            Schema::create('fashion_sizes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fashion_product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->string('size_type')->default('EU')->comment('EU, UK, US, RU');
                $table->string('size_value')->index()->comment('S, M, L, 42, 44 и т.д.');
                $table->integer('stock')->default(0);
                $table->jsonb('measurements')->nullable()->comment('Обхват груди, талии, бедер для SizeRecommendationService');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Размерная сетка для каждого товара с параметрами для AI');
            });
        }

        if (!Schema::hasTable('fashion_collections')) {
            Schema::create('fashion_collections', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('fashion_store_id')->constrained('fashion_stores')->onDelete('cascade');
                $table->string('name')->comment('Название коллекции (напр. Winter 2026)');
                $table->string('season')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->comment('Коллекции брендов для тематического поиска и AI комплектов');
            });
        }

        if (!Schema::hasTable('fashion_b2b_orders')) {
            Schema::create('fashion_b2b_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('fashion_store_id')->constrained('fashion_stores');
                $table->string('buyer_inn')->index();
                $table->integer('total_amount')->comment('Сумма заказа в копейках');
                $table->string('status')->default('pending');
                $table->jsonb('items_json')->comment('Состав заказа (product_id, size, qty, price)');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('metadata')->nullable();
                $table->timestamps();

                $table->comment('Оптовые заказы (B2B) с проверкой ИНН и юридических данных');
            });
        }

        if (!Schema::hasTable('fashion_reviews')) {
            Schema::create('fashion_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('fashion_product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users');
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('photos')->nullable();
                $table->jsonb('user_parameters')->nullable()->comment('Рост, размер пользователя при покупке (для ML)');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Отзывы с параметрами пользователя для уточнения SizeRecommendationService');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_reviews');
        Schema::dropIfExists('fashion_b2b_orders');
        Schema::dropIfExists('fashion_collections');
        Schema::dropIfExists('fashion_sizes');
        Schema::dropIfExists('fashion_products');
        Schema::dropIfExists('fashion_stores');
    }
};


