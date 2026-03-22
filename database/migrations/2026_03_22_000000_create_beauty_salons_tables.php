<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Создаёт все таблицы для модуля Beauty по канону 2026.
     */
    public function up(): void
    {
        // 1. Beauty Salons (салоны красоты)
        if (!Schema::hasTable('beauty_salons')) {
            Schema::create('beauty_salons', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index()->comment('ID арендатора (мультитенантность)');
                $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID бизнес-группы (филиал)');
                $table->uuid('uuid')->unique()->index()->comment('Уникальный UUID салона');
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID для трейсинга');
                $table->string('name')->comment('Название салона');
                $table->string('address')->comment('Адрес');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('description')->nullable()->comment('Описание салона');
                $table->json('working_hours')->nullable()->comment('Часы работы JSON');
                $table->point('geo_point')->nullable()->comment('Географические координаты');
                $table->decimal('rating', 3, 2)->default(0)->comment('Средний рейтинг салона');
                $table->unsignedInteger('review_count')->default(0)->comment('Количество отзывов');
                $table->boolean('is_verified')->default(false)->comment('Верифицирован ли салон');
                $table->string('status')->default('active')->comment('Статус: active, inactive, suspended');
                $table->json('tags')->nullable()->comment('Теги для фильтрации и аналитики');
                $table->json('metadata')->nullable()->comment('Дополнительные данные');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'business_group_id']);
                $table->index(['rating', 'review_count']);
            });
            DB::statement("COMMENT ON TABLE beauty_salons IS 'Салоны красоты / мастера — модуль Beauty'");
        }

        // 2. Masters (мастера)
        if (!Schema::hasTable('masters')) {
            Schema::create('masters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('salon_id')->nullable()->index()->comment('ID салона (nullable для самозанятых)');
                $table->unsignedBigInteger('user_id')->index()->comment('Связь с пользователем');
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->string('full_name')->comment('ФИО мастера');
                $table->json('specialization')->nullable()->comment('Специализация: стрижка, маникюр, массаж и т.д.');
                $table->unsignedInteger('experience_years')->default(0)->comment('Стаж работы (лет)');
                $table->decimal('rating', 3, 2)->default(0)->comment('Средний рейтинг мастера');
                $table->unsignedInteger('review_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->json('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'salon_id']);
                $table->index(['rating', 'review_count']);
            });
            DB::statement("COMMENT ON TABLE masters IS 'Мастера (парикмахеры, косметологи и т.д.)'");
        }

        // 3. Beauty Services (услуги)
        if (!Schema::hasTable('beauty_services')) {
            Schema::create('beauty_services', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('salon_id')->nullable()->index();
                $table->unsignedBigInteger('master_id')->nullable()->index();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->string('name')->comment('Название услуги');
                $table->text('description')->nullable();
                $table->unsignedInteger('duration_minutes')->comment('Длительность услуги в минутах');
                $table->unsignedBigInteger('price')->comment('Цена в копейках');
                $table->json('consumables_json')->nullable()->comment('Список расходников и кол-во');
                $table->boolean('is_active')->default(true);
                $table->json('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'salon_id', 'is_active']);
                $table->index(['tenant_id', 'master_id']);
            });
            DB::statement("COMMENT ON TABLE beauty_services IS 'Услуги салонов красоты'");
        }

        // 4. Appointments (записи на услуги)
        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('salon_id')->index();
                $table->unsignedBigInteger('master_id')->index();
                $table->unsignedBigInteger('service_id')->index();
                $table->unsignedBigInteger('client_id')->index()->comment('ID клиента');
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->dateTime('datetime_start')->comment('Дата и время начала');
                $table->dateTime('datetime_end')->nullable();
                $table->string('status')->default('pending')->comment('pending, confirmed, completed, cancelled');
                $table->unsignedBigInteger('price')->comment('Цена в копейках');
                $table->string('payment_status')->default('pending')->comment('pending, paid, refunded');
                $table->text('notes')->nullable()->comment('Заметки к записи');
                $table->json('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status', 'datetime_start']);
                $table->index(['tenant_id', 'client_id']);
                $table->index(['salon_id', 'master_id', 'datetime_start']);
            });
            DB::statement("COMMENT ON TABLE appointments IS 'Записи на услуги салонов красоты'");
        }

        // 5. Consumables (расходники)
        if (!Schema::hasTable('beauty_consumables')) {
            Schema::create('beauty_consumables', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('salon_id')->index();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->string('name')->comment('Название расходника');
                $table->text('description')->nullable();
                $table->string('sku')->nullable()->comment('Артикул');
                $table->unsignedInteger('current_stock')->default(0)->comment('Текущий остаток');
                $table->unsignedInteger('hold_stock')->default(0)->comment('Зарезервированный остаток');
                $table->unsignedInteger('min_stock_threshold')->default(10)->comment('Минимальный порог остатка');
                $table->unsignedBigInteger('unit_price')->nullable()->comment('Цена за единицу (копейки)');
                $table->string('unit_type')->default('шт')->comment('Единица измерения');
                $table->json('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'salon_id']);
                $table->index(['current_stock', 'min_stock_threshold']);
            });
            DB::statement("COMMENT ON TABLE beauty_consumables IS 'Расходники салонов (перчатки, краска, полотенца и т.д.)'");
        }

        // 6. Beauty Products (товары для продажи)
        if (!Schema::hasTable('beauty_products')) {
            Schema::create('beauty_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('salon_id')->nullable()->index();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->string('name')->comment('Название товара');
                $table->text('description')->nullable();
                $table->string('sku')->nullable();
                $table->unsignedInteger('current_stock')->default(0);
                $table->unsignedBigInteger('price')->comment('Цена в копейках');
                $table->boolean('is_active')->default(true);
                $table->json('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'salon_id', 'is_active']);
            });
            DB::statement("COMMENT ON TABLE beauty_products IS 'Товары для продажи (косметика, инструменты)'");
        }

        // 7. Portfolio Items (фото работ мастера)
        if (!Schema::hasTable('portfolio_items')) {
            Schema::create('portfolio_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('master_id')->index();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->string('title')->nullable()->comment('Заголовок работы');
                $table->text('description')->nullable();
                $table->string('image_url')->comment('URL фото до/после');
                $table->string('before_image_url')->nullable()->comment('Фото до');
                $table->string('after_image_url')->nullable()->comment('Фото после');
                $table->json('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'master_id']);
            });
            DB::statement("COMMENT ON TABLE portfolio_items IS 'Портфолио мастеров (фото работ)'");
        }

        // 8. Reviews (отзывы)
        if (!Schema::hasTable('beauty_reviews')) {
            Schema::create('beauty_reviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('salon_id')->nullable()->index();
                $table->unsignedBigInteger('master_id')->nullable()->index();
                $table->unsignedBigInteger('appointment_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->uuid('uuid')->unique()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->unsignedTinyInteger('rating')->comment('Оценка 1-5');
                $table->text('comment')->nullable()->comment('Текст отзыва');
                $table->boolean('is_verified')->default(false)->comment('Проверенный отзыв');
                $table->json('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'salon_id', 'rating']);
                $table->index(['tenant_id', 'master_id', 'rating']);
            });
            DB::statement("COMMENT ON TABLE beauty_reviews IS 'Отзывы о салонах и мастерах'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beauty_reviews');
        Schema::dropIfExists('portfolio_items');
        Schema::dropIfExists('beauty_products');
        Schema::dropIfExists('beauty_consumables');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('beauty_services');
        Schema::dropIfExists('masters');
        Schema::dropIfExists('beauty_salons');
    }
};
