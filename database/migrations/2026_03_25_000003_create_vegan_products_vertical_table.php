<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for VeganProducts Vertical - Full 2026 Production Ready.
 * Tables: vegan_stores, vegan_categories, vegan_products, vegan_recipes, 
 * vegan_subscription_boxes, vegan_subscriptions, vegan_reviews.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Vegan Stores (Business Centers)
        if (!Schema::hasTable('vegan_stores')) {
            Schema::create('vegan_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups');
                $table->string('name')->comment('Название магазина или пекарни');
                $table->string('address')->comment('Адрес производства/точки выдачи');
                $table->point('location')->nullable()->index();
                $table->json('schedule')->nullable()->comment('Расписание работы JSON');
                $table->string('certification_id')->nullable()->comment('Сертификат Vegan Society или др.');
                $table->boolean('is_active')->default(true);
                $table->decimal('rating', 3, 2)->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable()->comment('Теги для фильтрации: органик, без глютена');
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Магазины и производства веганских продуктов');
            });
        }

        // 2. Vegan Categories
        if (!Schema::hasTable('vegan_categories')) {
            Schema::create('vegan_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->string('slug')->index();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                $table->comment('Категории веганских товаров: мясозаменители, молочка, сладости');
            });
        }

        // 3. Vegan Products (Core Entity)
        if (!Schema::hasTable('vegan_products')) {
            Schema::create('vegan_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('vegan_store_id')->constrained('vegan_stores')->onDelete('cascade');
                $table->foreignId('vegan_category_id')->constrained('vegan_categories')->onDelete('cascade');
                $table->string('name')->index();
                $table->string('sku')->unique()->index();
                $table->string('brand')->nullable();
                $table->integer('price')->comment('Цена в копейках (B2C)');
                $table->integer('b2b_price')->nullable()->comment('Оптовая цена для бизнеса (B2B)');
                $table->boolean('is_b2b_available')->default(true);
                $table->jsonb('nutrition_info')->comment('БЖУ, калории, витамины');
                $table->jsonb('allergen_info')->comment('Маркировка аллергенов (орехи, соя, глютен)');
                $table->text('ingredients')->comment('Полный состав продукта');
                $table->integer('current_stock')->default(0);
                $table->integer('hold_stock')->default(0);
                $table->string('availability_status')->default('in_stock')->index();
                $table->jsonb('images')->nullable();
                $table->integer('shelf_life_days')->nullable();
                $table->float('weight_grams')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Веганские продукты с маркировкой состава и БЖУ');
            });
        }

        // 4. Vegan Recipes (Content & Upsell)
        if (!Schema::hasTable('vegan_recipes')) {
            Schema::create('vegan_recipes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('title')->index();
                $table->text('description');
                $table->jsonb('steps')->comment('Пошаговая инструкция');
                $table->integer('cooking_time_minutes')->default(30);
                $table->string('difficulty')->default('medium');
                $table->jsonb('ingredient_ids')->comment('Список ID продуктов из системы');
                $table->jsonb('nutrition_total')->comment('Общий БЖУ рецепта');
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                $table->comment('Рецепты из веганских продуктов');
            });
        }

        // 5. Subscription Boxes
        if (!Schema::hasTable('vegan_subscription_boxes')) {
            Schema::create('vegan_subscription_boxes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->text('description');
                $table->integer('price_monthly')->comment('Стоимость подписки в месяц (копейки)');
                $table->string('plan_type')->default('weekly')->comment('weekly, monthly, bi_weekly');
                $table->jsonb('included_product_ids')->comment('Продукты, входящие в бокс');
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                $table->comment('Подписочные боксы веганской еды');
            });
        }

        // 6. Reviews
        if (!Schema::hasTable('vegan_reviews')) {
            Schema::create('vegan_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users');
                $table->morphs('reviewable');
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('meta')->nullable()->comment('Доп. инфо: фото, подтвержденная покупка');
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                $table->comment('Отзывы на веганские товары и боксы');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vegan_reviews');
        Schema::dropIfExists('vegan_subscription_boxes');
        Schema::dropIfExists('vegan_recipes');
        Schema::dropIfExists('vegan_products');
        Schema::dropIfExists('vegan_categories');
        Schema::dropIfExists('vegan_stores');
    }
};
