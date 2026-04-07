<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026: Hotels Vertical Migrations (Layer 1)
 * 
 * Включает: hotels, rooms, bookings, amenities, reviews, b2b_contracts
 */
return new class extends Migration
{
    public function up(): void
    {
        // 🏨 1. Таблица Отелей
        if (!Schema::hasTable('hotels')) {
            Schema::create('hotels', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Название отеля');
                $table->text('description')->nullable()->comment('Описание сервиса');
                $table->string('address')->comment('Фактический адрес');
                $table->geometry('geo_point')->nullable()->comment('Координаты для карты');
                $table->integer('stars')->default(0)->comment('Количество звезд (0-5)');
                $table->boolean('is_active')->default(true)->index();
                $table->jsonb('schedule_json')->nullable()->comment('Правила заезда/выезда');
                $table->float('rating')->default(0);
                $table->integer('review_count')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Основная таблица отелей/гостиниц 2026');
            });
        }

        // 🛏️ 2. Таблица Номеров
        if (!Schema::hasTable('hotel_rooms')) {
            Schema::create('hotel_rooms', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
                $table->string('room_number')->comment('Номер комнаты');
                $table->string('room_type')->comment('Тип: Standard, Deluxe, Suite');
                $table->integer('capacity_adults')->default(2);
                $table->integer('capacity_children')->default(0);
                $table->integer('base_price_b2c')->comment('Базовая цена в копейках для физлиц');
                $table->integer('base_price_b2b')->comment('Базовая цена для бизнеса (юрлица)');
                $table->integer('total_stock')->default(1)->comment('Количество одинаковых номеров');
                $table->integer('min_stay_days')->default(1);
                $table->boolean('is_available')->default(true)->index();
                $table->jsonb('metadata')->nullable()->comment('ТТХ номера: площадь, вид из окна');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Таблица категорий и конкретных номеров');
            });
        }

        // 📅 3. Таблица Бронирований
        if (!Schema::hasTable('hotel_bookings')) {
            Schema::create('hotel_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('hotel_id')->constrained('hotels');
                $table->foreignId('room_id')->constrained('hotel_rooms');
                $table->date('check_in')->index();
                $table->date('check_out')->index();
                $table->integer('adults')->default(1);
                $table->integer('children')->default(0);
                $table->integer('total_price')->comment('Итоговая сумма в копейках');
                $table->integer('commission_amount')->default(0)->comment('Комиссия платформы');
                $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'])
                    ->default('pending')->index();
                $table->enum('payment_status', ['pending', 'authorized', 'captured', 'refunded', 'failed'])
                    ->default('pending')->index();
                $table->string('idempotency_key')->nullable()->unique();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('guest_details')->nullable()->comment('ФИО гостя, паспортные данные');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status']);
                $table->comment('Бронирования номеров (B2C/B2B)');
            });
        }

        // 🧺 4. Таблица Удобств
        if (!Schema::hasTable('hotel_amenities')) {
            Schema::create('hotel_amenities', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('name');
                $table->string('icon')->nullable();
                $table->string('category')->default('general'); // food, comfort, health
                $table->timestamps();
            });

            Schema::create('hotel_amenity_pivot', function (Blueprint $table) {
                $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
                $table->foreignId('amenity_id')->constrained('hotel_amenities')->onDelete('cascade');
                $table->index(['hotel_id', 'amenity_id']);
            });
        }

        // 💼 5. B2B Контракты
        if (!Schema::hasTable('hotel_b2b_contracts')) {
            Schema::create('hotel_b2b_contracts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('hotel_id')->constrained('hotels');
                $table->string('business_name')->comment('Название компании-партнера');
                $table->string('inn')->index();
                $table->float('discount_percent')->default(0);
                $table->integer('payout_delay_days')->default(4)->comment('КАНОН: 4 дня для гостиниц');
                $table->date('start_at');
                $table->date('end_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Специальные условия для юрлиц и командировок');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_b2b_contracts');
        Schema::dropIfExists('hotel_amenity_pivot');
        Schema::dropIfExists('hotel_amenities');
        Schema::dropIfExists('hotel_bookings');
        Schema::dropIfExists('hotel_rooms');
        Schema::dropIfExists('hotels');
    }
};


