<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026 — ENTERTAINMENT V1.0 (Billiards, Quests, Karaoke, Clubs)
 * 1. Идемпотентность (Schema::hasTable)
 * 2. Комментарии ко всем полям
 * 3. Tenant-isolation (tenant_id, business_group_id)
 * 4. Audit-fields (correlation_id, uuid)
 * 5. Tags (jsonb)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('entertainment_venues')) {
            Schema::create('entertainment_venues', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('business_group_id')->nullable()->index();
                
                $table->string('name')->comment('Название заведения (Боулинг, Квест и т.д.)');
                $table->string('type')->comment('Тип: billiards, quest, karaoke, club, cinema');
                $table->string('address');
                $table->jsonb('geo_point')->nullable()->comment('Координаты заведения');
                $table->jsonb('schedule')->nullable()->comment('Расписание работы по дням');
                
                $table->float('rating')->default(5.0);
                $table->integer('review_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_b2b_enabled')->default(true)->comment('Разрешены ли корпоративные бронирования');
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable()->comment('Теги для фильтрации и аналитики');
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Заведения развлекательного сектора (Entertainment)');
            });
        }

        if (!Schema::hasTable('entertainment_events')) {
            Schema::create('entertainment_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('venue_id')->constrained('entertainment_venues')->onDelete('cascade');
                $table->foreignId('tenant_id')->index();
                
                $table->string('title')->comment('Название мероприятия / сеанса');
                $table->text('description')->nullable();
                $table->dateTime('starts_at')->index();
                $table->dateTime('ends_at')->nullable();
                
                $table->integer('base_price_kopecks')->comment('Базовая цена в копейках');
                $table->integer('total_capacity')->default(0)->comment('Всего мест на событие');
                $table->integer('available_capacity')->default(0)->comment('Доступно мест');
                
                $table->string('status')->default('active')->comment('active, cancelled, completed');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                
                $table->comment('События, сеансы, квесты во времени');
            });
        }

        if (!Schema::hasTable('entertainment_seat_maps')) {
            Schema::create('entertainment_seat_maps', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('venue_id')->constrained('entertainment_venues');
                $table->foreignId('tenant_id')->index();
                
                $table->string('name')->comment('Название схемы (Зал 1, Столы 1-10)');
                $table->jsonb('layout')->comment('Сложная JSON-схема расположения мест/столов');
                $table->jsonb('categories')->comment('Категории мест: VIP, Standard, Economy');
                
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->comment('Схемы залов, рассадки, столов');
            });
        }

        if (!Schema::hasTable('entertainment_bookings')) {
            Schema::create('entertainment_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('event_id')->constrained('entertainment_events');
                $table->foreignId('user_id')->nullable()->index();
                
                $table->string('type')->default('b2c')->comment('b2c, b2b (корпоратив)');
                $table->string('status')->default('pending')->comment('pending, confirmed, paid, cancelled, completed');
                $table->integer('total_amount_kopecks');
                
                $table->jsonb('selected_seats')->nullable()->comment('Список выбранных мест/столов');
                $table->string('idempotency_key')->nullable()->unique();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                
                $table->comment('Бронирования билетов и столов');
            });
        }

        if (!Schema::hasTable('entertainment_tickets')) {
            Schema::create('entertainment_tickets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('booking_id')->constrained('entertainment_bookings')->onDelete('cascade');
                $table->foreignId('event_id')->constrained('entertainment_events');
                $table->foreignId('tenant_id')->index();
                
                $table->string('ticket_number')->unique()->comment('Номер билета для проверки');
                $table->string('qr_code')->nullable();
                $table->string('seat_label')->nullable()->comment('Напр: Ряд 5, Место 12');
                
                $table->boolean('is_validated')->default(false)->comment('Проверен ли на входе (чекер)');
                $table->dateTime('validated_at')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Билеты на мероприятия');
            });
        }

        if (!Schema::hasTable('entertainment_reviews')) {
            Schema::create('entertainment_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('venue_id')->constrained('entertainment_venues');
                $table->foreignId('user_id')->index();
                $table->foreignId('tenant_id')->index();
                
                $table->integer('rating')->unsigned();
                $table->text('comment')->nullable();
                $table->jsonb('photos')->nullable();
                
                $table->string('correlation_id')->nullable();
                $table->timestamps();
                
                $table->comment('Отзывы пользователей о заведениях');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entertainment_reviews');
        Schema::dropIfExists('entertainment_tickets');
        Schema::dropIfExists('entertainment_bookings');
        Schema::dropIfExists('entertainment_seat_maps');
        Schema::dropIfExists('entertainment_events');
        Schema::dropIfExists('entertainment_venues');
    }
};


