<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026: Миграции для вертикали Travel.
 * Слой 1: База данных.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Популярные направления
        if (!Schema::hasTable('destinations')) {
            Schema::create('destinations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Название города/страны');
                $table->string('country_code', 3)->index();
                $table->text('description')->nullable();
                $table->jsonb('geo_point')->comment('Координаты центра');
                $table->jsonb('tags')->nullable()->comment('Курорт, лыжи, пляж');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Destination: точки на карте для туров');
            });
        }

        // 2. Туры (Пакетные предложения)
        if (!Schema::hasTable('tours')) {
            Schema::create('tours', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('destination_id')->constrained();
                $table->string('title')->comment('Название тура');
                $table->text('content')->comment('Описание программы');
                $table->integer('base_price')->comment('Базовая цена в копейках');
                $table->integer('duration_days')->default(1);
                $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('easy');
                $table->jsonb('amenities')->nullable()->comment('Отель, трансфер, питание');
                $table->jsonb('tags')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Tours: пакетные предложения от туроператоров');
            });
        }

        // 3. Конкретные выезды (Trips)
        if (!Schema::hasTable('trips')) {
            Schema::create('trips', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('tour_id')->constrained();
                $table->dateTime('start_at')->index();
                $table->dateTime('end_at')->index();
                $table->integer('price')->comment('Фактическая цена выезда');
                $table->integer('max_slots')->default(20);
                $table->integer('booked_slots')->default(0);
                $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft')->index();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Trips: конкретные даты выезда по туру');
            });
        }

        // 4. Экскурсии (Дополнительные услуги)
        if (!Schema::hasTable('excursions')) {
            Schema::create('excursions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('destination_id')->constrained();
                $table->string('name');
                $table->text('description');
                $table->integer('price')->comment('Цена за человека');
                $table->integer('duration_minutes')->default(120);
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Excursions: короткие активности внутри дестинации');
            });
        }

        // 5. Бронирования (Bookings)
        if (!Schema::hasTable('travel_bookings')) {
            Schema::create('travel_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->index();
                $table->morphs('bookable'); // Trip или Excursion
                $table->integer('slots_count')->default(1);
                $table->integer('total_price')->comment('Итого в копейках');
                $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled', 'completed'])->default('pending')->index();
                $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid', 'refunded'])->default('unpaid');
                $table->string('idempotency_key')->nullable()->unique();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('metadata')->nullable()->comment('Данные паспортов, предпочтения');
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Travel Bookings: заказы туров и экскурсий');
            });
        }

        // 6. Отзывы (Reviews)
        if (!Schema::hasTable('travel_reviews')) {
            Schema::create('travel_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('booking_id')->constrained('travel_bookings');
                $table->foreignId('user_id')->index();
                $table->integer('rating')->unsigned()->comment('1-10');
                $table->text('comment');
                $table->jsonb('photos')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Reviews: отзывы путешественников');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_reviews');
        Schema::dropIfExists('travel_bookings');
        Schema::dropIfExists('excursions');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('tours');
        Schema::dropIfExists('destinations');
    }
};
