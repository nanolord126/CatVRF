<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Art & Gallery Vertical — Production Ready 2026.
 * Implements 7 core tables: art_galleries, artists, artworks, art_materials, exhibitions, art_orders, art_reviews.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Art Galleries
        if (!Schema::hasTable('art_galleries')) {
            Schema::create('art_galleries', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name')->comment('Название галереи');
                $table->string('address')->nullable()->comment('Физический адрес');
                $table->geometry('geo_point')->nullable()->comment('Геолокация для карты');
                $table->jsonb('schedule_json')->nullable()->comment('Режим работы');
                $table->float('rating')->default(0)->index();
                $table->integer('review_count')->default(0);
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable()->comment('Теги для фильтрации');
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['tenant_id', 'is_verified']);
                $table->comment('Таблица художественных галерей');
            });
        }

        // 2. Artists
        if (!Schema::hasTable('artists')) {
            Schema::create('artists', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->comment('Связь с пользователем');
                $table->string('full_name')->index();
                $table->string('pseudonym')->nullable();
                $table->text('biography')->nullable();
                $table->jsonb('specialization')->nullable()->comment('Стили: импрессионизм, сюрреализм и т.д.');
                $table->integer('experience_years')->default(0);
                $table->float('rating')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                
                $table->index(['tenant_id', 'full_name']);
                $table->comment('Таблица художников и мастеров');
            });
        }

        // 3. Artworks
        if (!Schema::hasTable('artworks')) {
            Schema::create('artworks', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('gallery_id')->index();
                $table->unsignedBigInteger('artist_id')->index();
                $table->string('title')->index();
                $table->text('description')->nullable();
                $table->enum('type', ['painting', 'sculpture', 'digital', 'print', 'installation'])->default('painting');
                $table->jsonb('dimensions')->comment('Размеры: width, height, depth, weight');
                $table->integer('price_cents')->comment('Цена в копейках');
                $table->integer('stock_quantity')->default(1)->comment('Количество в наличии (1 для оригиналов)');
                $table->boolean('is_original')->default(true)->comment('Оригинал или копия/принт');
                $table->boolean('has_certificate')->default(false)->comment('Наличие сертификата подлинности');
                $table->string('style')->nullable()->index()->comment('Стиль: модерн, классика и т.д.');
                $table->string('material_main')->nullable()->comment('Основной материал: масло, акрил, бронза');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['gallery_id', 'type', 'style']);
                $table->comment('Таблица произведений искусства');
            });
        }

        // 4. Art Materials (Resources for art creation)
        if (!Schema::hasTable('art_materials')) {
            Schema::create('art_materials', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name')->index();
                $table->string('sku')->unique();
                $table->integer('price_cents');
                $table->integer('stock_level')->default(0);
                $table->integer('min_threshold')->default(5);
                $table->string('correlation_id')->nullable();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                
                $table->comment('Расходные материалы для художников');
            });
        }

        // 5. Exhibitions
        if (!Schema::hasTable('art_exhibitions')) {
            Schema::create('art_exhibitions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('gallery_id')->index();
                $table->string('title')->index();
                $table->text('description')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_virtual')->default(false);
                $table->integer('entry_fee_cents')->default(0);
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->index(['gallery_id', 'starts_at', 'ends_at']);
                $table->comment('Выставки и вернисажи');
            });
        }

        // 6. Art Orders
        if (!Schema::hasTable('art_orders')) {
            Schema::create('art_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('customer_id')->index()->comment('User ID');
                $table->jsonb('items_json')->comment('Список SKU и цен');
                $table->integer('total_amount_cents');
                $table->string('status')->default('pending')->index();
                $table->string('payment_status')->default('unpaid');
                $table->boolean('is_b2b')->default(false);
                $table->string('shipping_address')->nullable();
                $table->jsonb('shipping_details')->nullable()->comment('Трек-номер, вес, страховка');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Заказы в арт-вертикали');
            });
        }

        // 7. Art Reviews
        if (!Schema::hasTable('art_reviews')) {
            Schema::create('art_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->morphs('reviewable'); // Artwork, Artist, Gallery
                $table->unsignedBigInteger('user_id')->index();
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('media_json')->nullable()->comment('Фото/видео произведения в интерьере');
                $table->boolean('is_verified_purchase')->default(false);
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->comment('Отзывы и оценки в арт-вертикали');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('art_reviews');
        Schema::dropIfExists('art_orders');
        Schema::dropIfExists('art_exhibitions');
        Schema::dropIfExists('art_materials');
        Schema::dropIfExists('artworks');
        Schema::dropIfExists('artists');
        Schema::dropIfExists('art_galleries');
    }
};


