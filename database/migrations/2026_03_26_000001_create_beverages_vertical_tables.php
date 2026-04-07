<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Beverage Shops (Venues)
        if (!Schema::hasTable('beverage_shops')) {
            Schema::create('beverage_shops', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index()->comment('Идентификатор тенанта (владельца бизнеса)');
                $table->string('business_group_id')->nullable()->index()->comment('Идентификатор филиала/группы');
                $table->string('correlation_id')->nullable()->index();
                
                $table->string('name')->comment('Название заведения (Кофейня, Бар)');
                $table->string('type')->comment('Тип: coffee_shop, tea_house, bar, brewery');
                $table->string('address')->comment('Физический адрес');
                $table->jsonb('geo_point')->nullable()->comment('Координаты {lat, lon}');
                $table->jsonb('schedule')->comment('График работы по дням');
                
                $table->decimal('rating', 3, 2)->default(0);
                $table->integer('review_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->jsonb('tags')->nullable()->comment('Теги для аналитики и фильтрации');
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Заведения вертикали Beverages: кофейни, чайные, бары');
            });
        }

        // 2. Beverage Categories
        if (!Schema::hasTable('beverage_categories')) {
            Schema::create('beverage_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->foreignId('shop_id')->constrained('beverage_shops')->onDelete('cascade');
                
                $table->string('name')->comment('Название категории: Эспрессо-бар, Авторские чаи');
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->string('correlation_id')->nullable();
                
                $table->timestamps();
                $table->comment('Категории напитков в меню');
            });
        }

        // 3. Drinks (Products)
        if (!Schema::hasTable('beverage_items')) {
            Schema::create('beverage_items', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->foreignId('category_id')->constrained('beverage_categories');
                $table->foreignId('shop_id')->constrained('beverage_shops');
                
                $table->string('name')->comment('Название напитка');
                $table->text('description')->nullable();
                $table->integer('price')->comment('Цена в копейках');
                $table->integer('volume_ml')->comment('Объем в мл');
                $table->jsonb('ingredients')->comment('Состав напитка для списания остатков');
                $table->jsonb('allergens')->nullable()->comment('Список аллергенов');
                $table->jsonb('nutritional_value')->nullable()->comment('КБЖУ');
                
                $table->integer('stock_count')->default(0)->comment('Текущий остаток (для бутылочных/готовых)');
                $table->string('freshness_control_type')->default('none')->comment('Тип контроля свежести');
                $table->integer('shelf_life_hours')->nullable()->comment('Срок годности в часах после приготовления');
                
                $table->boolean('is_available')->default(true);
                $table->string('correlation_id')->nullable();
                $table->jsonb('tags')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Напитки: кофе, чай, коктейли, бутылочная продукция');
            });
        }

        // 4. Beverage Orders
        if (!Schema::hasTable('beverage_orders')) {
            Schema::create('beverage_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('business_group_id')->nullable()->index();
                $table->foreignId('shop_id')->constrained('beverage_shops');
                $table->unsignedBigInteger('customer_id')->index()->comment('ID пользователя (B2C/B2B)');
                
                $table->string('status')->comment('Статус: pending, processing, ready, completed, cancelled');
                $table->integer('total_amount')->comment('Итоговая сумма в копейках');
                $table->string('payment_status')->default('unpaid');
                $table->string('payment_method')->nullable();
                
                $table->jsonb('items_snapshot')->comment('Срез товаров в заказе на момент создания');
                $table->string('delivery_type')->default('pickup')->comment('Тип: pickup, delivery, table');
                $table->string('address')->nullable()->comment('Адрес доставки');
                
                $table->string('idempotency_key')->unique();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('metadata')->nullable();
                
                $table->timestamps();
                $table->comment('Заказы в вертикали Beverages');
            });
        }

        // 5. Beverage Subscriptions
        if (!Schema::hasTable('beverage_subscriptions')) {
            Schema::create('beverage_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->foreignId('shop_id')->constrained('beverage_shops');
                
                $table->string('plan_type')->comment('Тип: daily_coffee, weekly_bar, monthly_tea');
                $table->integer('price')->comment('Стоимость подписки');
                $table->integer('limit_count')->comment('Лимит напитков в периоде');
                $table->integer('used_count')->default(0);
                
                $table->timestamp('starts_at');
                $table->timestamp('expires_at');
                $table->boolean('auto_renew')->default(true);
                $table->string('status')->default('active');
                
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->comment('Абонементы на напитки (кофе по подписке и т.д.)');
            });
        }

        // 6. Beverage Reviews
        if (!Schema::hasTable('beverage_reviews')) {
            Schema::create('beverage_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->foreignId('shop_id')->constrained('beverage_shops');
                $table->foreignId('item_id')->nullable()->constrained('beverage_items');
                $table->unsignedBigInteger('user_id')->index();
                
                $table->integer('rating')->comment('Оценка 1-5');
                $table->text('comment')->nullable();
                $table->jsonb('media')->nullable()->comment('Фото/видео напитка');
                
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->comment('Отзывы пользователей о заведениях и конкретных напитках');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beverage_reviews');
        Schema::dropIfExists('beverage_subscriptions');
        Schema::dropIfExists('beverage_orders');
        Schema::dropIfExists('beverage_items');
        Schema::dropIfExists('beverage_categories');
        Schema::dropIfExists('beverage_shops');
    }
};


