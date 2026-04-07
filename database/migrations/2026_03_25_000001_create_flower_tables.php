<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026: Migration for Flowers Vertical.
 * Идемпотентность, комментарии, tenant_id, correlation_id, jsonb tags.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Цветочные магазины (Shops)
        if (!Schema::hasTable('flower_shops')) {
            Schema::create('flower_shops', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('business_group_id')->nullable()->index();
                $table->string('name')->comment('Название магазина');
                $table->string('address')->nullable();
                $table->geometry('geo_point')->nullable();
                $table->jsonb('schedule_json')->nullable();
                $table->decimal('rating', 3, 2)->default(0);
                $table->integer('review_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Цветочные магазины тенанта');
            });
        }

        // 2. Цветы и товары (Products)
        if (!Schema::hasTable('flower_products')) {
            Schema::create('flower_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('shop_id')->constrained('flower_shops')->onDelete('cascade');
                $table->string('name');
                $table->string('sku')->index();
                $table->string('type')->comment('flower, gift, accessory');
                $table->integer('price_kopecks')->comment('Цена в копейках (int)');
                $table->integer('current_stock')->default(0);
                $table->integer('min_stock_threshold')->default(10);
                $table->timestamp('freshness_date')->nullable()->comment('Дата срезки/поступления');
                $table->jsonb('metadata')->nullable()->comment('Цвет, сорт, аромат');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Цветы и товары (склад)');
            });
        }

        // 3. Букеты (Bouquets)
        if (!Schema::hasTable('flower_bouquets')) {
            Schema::create('flower_bouquets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('shop_id')->constrained('flower_shops');
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('price_kopecks');
                $table->jsonb('composition_json')->comment('Состав: [product_id => quantity]');
                $table->jsonb('consumables_json')->comment('Расходники: [item_id => quantity]');
                $table->string('status')->default('active'); // active, seasonal, hidden
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Готовые букеты и конструкторные шаблоны');
            });
        }

        // 4. Расходные материалы (Consumables)
        if (!Schema::hasTable('flower_consumables')) {
            Schema::create('flower_consumables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Лента, бумага, губка, коробка');
                $table->integer('current_stock')->default(0);
                $table->string('unit')->default('pcs'); // pcs, m, ml
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Расходные материалы для упаковки');
            });
        }

        // 5. Заказы (B2C/B2B Orders)
        if (!Schema::hasTable('flower_orders')) {
            Schema::create('flower_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->nullable()->index();
                $table->foreignId('business_group_id')->nullable()->index();
                $table->string('type')->default('b2c'); // b2c, b2b
                $table->string('status')->default('pending'); 
                $table->integer('total_price_kopecks');
                $table->jsonb('items_json')->comment('Список букетов и товаров');
                $table->timestamp('delivery_at')->nullable();
                $table->string('delivery_address')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->comment('Заказы цветов B2C и B2B');
            });
        }

        // 6. Портфолио и фото
        if (!Schema::hasTable('flower_portfolio')) {
            Schema::create('flower_portfolio', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('shop_id')->constrained('flower_shops');
                $table->string('image_path');
                $table->string('title')->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Портфолио работ флористов');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flower_portfolio');
        Schema::dropIfExists('flower_orders');
        Schema::dropIfExists('flower_consumables');
        Schema::dropIfExists('flower_bouquets');
        Schema::dropIfExists('flower_products');
        Schema::dropIfExists('flower_shops');
    }
};


