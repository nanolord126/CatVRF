<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026: Migration for ShortTermRentals vertical (Layer 1)
 * 
 * Обязательно: correlation_id, uuid, tenant_id, business_group_id, tags (jsonb), comment.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('str_properties')) {
            Schema::create('str_properties', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
                $table->string('name')->comment('Наименование объекта (ЖК, Коттедж и т.д.)');
                $table->string('address')->comment('Полный адрес объекта');
                $table->string('city')->index()->comment('Город');
                $table->decimal('lat', 10, 8)->nullable()->comment('Широта');
                $table->decimal('lon', 11, 8)->nullable()->comment('Долгота');
                $table->string('type')->default('apartment')->comment('Тип объекта (apartment, loft, studio, villa)');
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_verified')->default(false)->index();
                $table->decimal('rating', 3, 2)->default(0);
                $table->integer('review_count')->default(0);
                $table->jsonb('schedule_json')->nullable()->comment('График заезда/выезда и правила');
                $table->jsonb('tags')->nullable()->comment('Теги для фильтрации и аналитики');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Объекты недвижимости для посуточной аренды');
            });
        }

        if (!Schema::hasTable('str_apartments')) {
            Schema::create('str_apartments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('property_id')->constrained('str_properties')->onDelete('cascade');
                $table->string('room_number')->nullable()->comment('Номер квартиры или апартамента');
                $table->integer('floor')->nullable();
                $table->integer('area_sqm')->comment('Площадь в кв.м.');
                $table->integer('capacity_adults')->default(1);
                $table->integer('capacity_children')->default(0);
                $table->integer('base_price_b2c')->comment('Базовая цена B2C в копейках');
                $table->integer('base_price_b2b')->comment('Базовая цена B2B в копейках');
                $table->integer('deposit_amount')->default(0)->comment('Сумма залога в копейках (hold)');
                $table->integer('min_stay_days')->default(1);
                $table->boolean('is_available')->default(true)->index();
                $table->jsonb('features_json')->nullable()->comment('Удобства (WiFi, Кондей, Кухня и т.д.)');
                $table->jsonb('metadata')->nullable()->comment('Метаданные (фото, описание)');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Конкретные лоты апартаментов/квартир');
            });
        }

        if (!Schema::hasTable('str_bookings')) {
            Schema::create('str_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
                $table->foreignId('apartment_id')->constrained('str_apartments')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->dateTime('check_in')->index();
                $table->dateTime('check_out')->index();
                $table->string('status')->default('pending')->index()->comment('Status: pending, confirmed, active, completed, cancelled, failed');
                $table->integer('total_price')->comment('Полная стоимость в копейках');
                $table->integer('deposit_amount')->comment('Сумма залога в копейках');
                $table->string('deposit_status')->default('pending')->comment('Deposit: pending, held, released, charged');
                $table->string('payment_status')->default('pending')->comment('Payment: pending, paid, refund_pending, refunded');
                $table->dateTime('payout_at')->nullable()->index()->comment('Дата выплаты владельцу (через 4 дня после выезда)');
                $table->boolean('is_b2b')->default(false)->index();
                $table->jsonb('metadata')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Бронирования апартаментов посуточно');
            });
        }

        if (!Schema::hasTable('str_amenities')) {
            Schema::create('str_amenities', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name')->comment('Наименование услуги (клининг, завтрак, трансфер)');
                $table->string('icon')->nullable();
                $table->text('description')->nullable();
                $table->integer('cost')->default(0)->comment('Стоимость в копейках');
                $table->boolean('is_active')->default(true)->index();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Дополнительные услуги для апартаментов');
            });
        }

        if (!Schema::hasTable('str_amenity_map')) {
            Schema::create('str_amenity_map', function (Blueprint $table) {
                $table->foreignId('apartment_id')->constrained('str_apartments')->onDelete('cascade');
                $table->foreignId('amenity_id')->constrained('str_amenities')->onDelete('cascade');
                $table->primary(['apartment_id', 'amenity_id']);
            });
        }

        if (!Schema::hasTable('str_reviews')) {
            Schema::create('str_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('booking_id')->constrained('str_bookings')->onDelete('cascade');
                $table->foreignId('apartment_id')->constrained('str_apartments')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->integer('rating')->default(5)->index();
                $table->text('comment')->nullable();
                $table->jsonb('media')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Отзывы гостей после проживания');
            });
        }

        if (!Schema::hasTable('str_calendar_availability')) {
            Schema::create('str_calendar_availability', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('apartment_id')->constrained('str_apartments')->onDelete('cascade');
                $table->date('date')->index();
                $table->boolean('is_available')->default(true)->index();
                $table->integer('price_override_b2c')->nullable()->comment('Переопределение цены B2C');
                $table->integer('price_override_b2b')->nullable()->comment('Переопределение цены B2B');
                $table->string('reason')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['apartment_id', 'date']);
                $table->comment('Календарь доступности и цен на конкретные даты');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('str_calendar_availability');
        Schema::dropIfExists('str_reviews');
        Schema::dropIfExists('str_amenity_map');
        Schema::dropIfExists('str_amenities');
        Schema::dropIfExists('str_bookings');
        Schema::dropIfExists('str_apartments');
        Schema::dropIfExists('str_properties');
    }
};


