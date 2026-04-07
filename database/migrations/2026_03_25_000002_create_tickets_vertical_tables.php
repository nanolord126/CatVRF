<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026: Миграция вертикали Tickets.
 * Слой 1: Базовая схема данных для эвентов, билетов и чекинов.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Площадки (Venues)
        if (!Schema::hasTable('venues')) {
            Schema::create('venues', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name')->comment('Название площадки');
                $table->string('address')->comment('Адрес');
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lon', 11, 8)->nullable();
                $table->jsonb('capacity_info')->comment('Детализация вместимости по секторам');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Площадки для проведения мероприятий (стадионы, залы, клубы)');
            });
        }

        // 2. Схемы залов (SeatMaps)
        if (!Schema::hasTable('seat_maps')) {
            Schema::create('seat_maps', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
                $table->string('name');
                $table->jsonb('layout')->comment('SVG или JSON структура мест и секторов');
                $table->jsonb('metadata')->nullable()->comment('Дополнительные визуальные настройки');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Конструктор схем залов с привязкой к площадке');
            });
        }

        // 3. Мероприятия (Events)
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('venue_id')->constrained('venues');
                $table->foreignId('seat_map_id')->nullable()->constrained('seat_maps');
                $table->string('title')->index();
                $table->text('description');
                $table->string('slug')->unique();
                $table->dateTime('start_at')->index();
                $table->dateTime('end_at')->nullable();
                $table->string('status')->default('draft')->index(); // draft, published, cancelled, completed
                $table->string('category')->index(); // concert, theater, sport, etc.
                $table->integer('max_tickets_per_user')->default(4);
                $table->boolean('is_b2b')->default(false)->comment('Для корпоративных эвентов');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Центральная сущность мероприятий');
            });
        }

        // 4. Типы билетов (TicketTypes)
        if (!Schema::hasTable('ticket_types')) {
            Schema::create('ticket_types', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
                $table->string('name')->comment('Standard, VIP, Fan Zone');
                $table->integer('price')->comment('Цена в копейках');
                $table->integer('quantity')->comment('Общее кол-во выпущенных билетов');
                $table->integer('sold_count')->default(0);
                $table->jsonb('rules')->comment('Правила возврата, входа, подарков');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Категории билетов и цены');
            });
        }

        // 5. Билеты (Tickets)
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('event_id')->constrained('events');
                $table->foreignId('ticket_type_id')->constrained('ticket_types');
                $table->foreignId('user_id')->nullable()->index();
                $table->string('order_uuid')->nullable()->index();
                $table->string('qr_code')->unique()->index();
                $table->integer('price_paid')->comment('Фактически оплаченная сумма');
                $table->string('status')->default('pending')->index(); // pending, active, used, refunded, cancelled
                $table->string('seat_label')->nullable()->comment('Сектор-Ряд-Место');
                $table->jsonb('metadata')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamp('issued_at')->nullable();
                $table->timestamps();

                $table->comment('Экземпляры проданных билетов');
            });
        }

        // 6. Логи чекинов (CheckInLogs)
        if (!Schema::hasTable('check_in_logs')) {
            Schema::create('check_in_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('ticket_id')->constrained('tickets');
                $table->foreignId('gate_id')->nullable();
                $table->foreignId('checked_by')->nullable()->comment('Сотрудник на контроле');
                $table->string('status')->index(); // success, fail (double scan, expired, wrong gate)
                $table->string('failure_reason')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('История проходов по билетам');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('check_in_logs');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_types');
        Schema::dropIfExists('events');
        Schema::dropIfExists('seat_maps');
        Schema::dropIfExists('venues');
    }
};


