<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wedding Planning Vertical Migration 2026
 *
 * Создание таблиц для:
 * - wedding_planners (организаторы)
 * - wedding_vendors (подрядчики: декор, ведущие, фото, площадки)
 * - wedding_packages (свадебные пакеты)
 * - wedding_events (мероприятия/свадьбы)
 * - wedding_bookings (бронирования услуг/пакетов)
 * - wedding_contracts (договоры и условия)
 * - wedding_reviews (отзывы после события)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Wedding Planners
        if (!Schema::hasTable('wedding_planners')) {
            Schema::create('wedding_planners', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->integer('tenant_id')->index();
                $blueprint->integer('business_group_id')->nullable()->index();

                $blueprint->string('name')->comment('Имя организатора или агентства');
                $blueprint->string('contact_phone');
                $blueprint->string('contact_email');
                $blueprint->integer('experience_years')->default(0);
                $blueprint->integer('rating')->default(0);
                $blueprint->jsonb('specialization')->nullable()->comment('Стили: бохо, рустик, классика и т.д.');
                $blueprint->jsonb('available_dates')->nullable();

                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->jsonb('tags')->nullable();
                $blueprint->timestamps();
                $blueprint->softDeletes();

                $blueprint->comment('Таблица свадебных организаторов и агентств');
            });
        }

        // 2. Wedding Vendors (Подрядчики)
        if (!Schema::hasTable('wedding_vendors')) {
            Schema::create('wedding_vendors', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->integer('tenant_id')->index();

                $blueprint->string('name');
                $blueprint->enum('category', [
                    'photographer',
                    'videographer',
                    'host',
                    'decorator',
                    'florist',
                    'counselor',
                    'venue',
                    'catering',
                    'transport',
                    'makeup_artist',
                    'other'
                ])->index();

                $blueprint->integer('base_price')->default(0)->comment('Цена в копейках');
                $blueprint->string('currency', 3)->default('RUB');
                $blueprint->jsonb('portfolio_links')->nullable();
                $blueprint->jsonb('equipment_list')->nullable();
                $blueprint->integer('rating')->default(0);
                $blueprint->boolean('is_verified')->default(false);

                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->jsonb('tags')->nullable();
                $blueprint->timestamps();
                $blueprint->softDeletes();

                $blueprint->comment('Таблица свадебных подрядчиков и специалистов');
            });
        }

        // 3. Wedding Packages (Свадебные пакеты)
        if (!Schema::hasTable('wedding_packages')) {
            Schema::create('wedding_packages', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->integer('tenant_id')->index();
                $blueprint->foreignId('planner_id')->constrained('wedding_planners');

                $blueprint->string('title');
                $blueprint->text('description');
                $blueprint->integer('price')->default(0)->comment('Цена пакета в копейках');
                $blueprint->integer('max_guests')->default(50);
                $blueprint->jsonb('included_services')->comment('Список услуг/вендоров в пакете');
                $blueprint->boolean('is_active')->default(true);

                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->jsonb('tags')->nullable();
                $blueprint->timestamps();

                $blueprint->comment('Готовые свадебные пакеты с набором услуг');
            });
        }

        // 4. Wedding Events (Свадьбы/Мероприятия)
        if (!Schema::hasTable('wedding_events')) {
            Schema::create('wedding_events', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->integer('tenant_id')->index();
                $blueprint->integer('owner_id')->index()->comment('ID пользователя B2C/B2B');

                $blueprint->string('title')->comment('Например: Свадьба Ивана и Марии');
                $blueprint->timestamp('event_date')->index();
                $blueprint->string('location')->nullable();
                $blueprint->integer('guest_count')->default(0);
                $blueprint->integer('total_budget')->default(0)->comment('В копейках');
                $blueprint->enum('status', [
                    'planning',
                    'confirmed',
                    'active',
                    'completed',
                    'cancelled',
                    'archived'
                ])->default('planning')->index();

                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->jsonb('tags')->nullable();
                $blueprint->timestamps();
                $blueprint->softDeletes();

                $blueprint->comment('Основные сущности свадебных мероприятий');
            });
        }

        // 5. Wedding Bookings (Бронирования услуг/пакетов)
        if (!Schema::hasTable('wedding_bookings')) {
            Schema::create('wedding_bookings', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->integer('tenant_id')->index();
                $blueprint->foreignId('event_id')->constrained('wedding_events');
                $blueprint->nullableMorphs('bookable'); // Package или Vendor

                $blueprint->integer('amount')->default(0)->comment('Сумма в копейках');
                $blueprint->integer('prepayment_amount')->default(0);
                $blueprint->enum('status', [
                    'pending',
                    'reserved',
                    'paid_partial',
                    'paid_full',
                    'failed',
                    'cancelled',
                    'refunded'
                ])->default('pending')->index();

                $blueprint->timestamp('booked_at')->nullable();
                $blueprint->string('idempotency_key')->nullable()->unique();
                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->jsonb('tags')->nullable();
                $blueprint->timestamps();

                $blueprint->comment('Транзакции бронирования услуг для конкретной свадьбы');
            });
        }

        // 6. Wedding Contracts (Договоры)
        if (!Schema::hasTable('wedding_contracts')) {
            Schema::create('wedding_contracts', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->integer('tenant_id')->index();
                $blueprint->foreignId('event_id')->constrained('wedding_events');

                $blueprint->string('contract_number')->unique();
                $blueprint->jsonb('terms')->comment('Условия: предоплата, отмена, форс-мажор');
                $blueprint->enum('status', ['draft', 'sent', 'signed', 'expired', 'voided'])->default('draft');
                $blueprint->timestamp('signed_at')->nullable();

                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->jsonb('tags')->nullable();
                $blueprint->timestamps();

                $blueprint->comment('Юридические документы и условия сопровождения свадьбы');
            });
        }

        // 7. Wedding Reviews (Отзывы)
        if (!Schema::hasTable('wedding_reviews')) {
            Schema::create('wedding_reviews', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->uuid('uuid')->unique()->index();
                $blueprint->integer('tenant_id')->index();
                $blueprint->integer('user_id')->index();
                $blueprint->nullableMorphs('reviewable'); // Review на Planner или Vendor

                $blueprint->integer('rating')->default(5);
                $blueprint->text('comment')->nullable();
                $blueprint->jsonb('media_urls')->nullable();
                $blueprint->boolean('is_published')->default(true);

                $blueprint->string('correlation_id')->nullable()->index();
                $blueprint->timestamps();

                $blueprint->comment('Отзывы пользователей о качестве услуг WeddingPlanning');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wedding_reviews');
        Schema::dropIfExists('wedding_contracts');
        Schema::dropIfExists('wedding_bookings');
        Schema::dropIfExists('wedding_events');
        Schema::dropIfExists('wedding_packages');
        Schema::dropIfExists('wedding_vendors');
        Schema::dropIfExists('wedding_planners');
    }
};


