<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026 — PHOTOGRAPHY VERTICAL MIGRATION
 * 1. PhotoStudio
 * 2. Photographer
 * 3. Portfolio
 * 4. PhotoSession (Events/Types)
 * 5. Booking
 * 6. Review
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. PhotoStudio
        if (!Schema::hasTable('photography_studios')) {
            Schema::create('photography_studios', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('business_group_id')->nullable()->index();
                
                $table->string('name')->comment('Название студии');
                $table->string('address')->comment('Адрес студии');
                $table->geometry('geo_point')->nullable()->comment('Координаты');
                $table->jsonb('schedule_json')->comment('Расписание работы');
                $table->jsonb('amenities')->nullable()->comment('Удобства: гримерка, циклорама и т.д.');
                
                $table->boolean('is_active')->default(true)->index();
                $table->float('rating')->default(0);
                $table->integer('review_count')->default(0);
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Фотостудии (площадки)');
            });
        }

        // 2. Photographer
        if (!Schema::hasTable('photography_photographers')) {
            Schema::create('photography_photographers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->nullable()->comment('Связь с системным юзером');
                
                $table->string('full_name');
                $table->string('specialization')->comment('Жанры: свадебный, fashion, репортаж');
                $table->integer('experience_years')->default(0);
                $table->integer('base_price_hour_kopecks')->default(0);
                
                $table->jsonb('equipment_json')->nullable()->comment('Камеры, свет, объективы');
                $table->boolean('is_available')->default(true)->index();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Фотографы');
            });
        }

        // 3. Portfolio
        if (!Schema::hasTable('photography_portfolios')) {
            Schema::create('photography_portfolios', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('photographer_id')->constrained('photography_photographers')->onDelete('cascade');
                
                $table->string('title');
                $table->text('description')->nullable();
                $table->jsonb('media_urls')->comment('Массив ссылок на фото/видео');
                $table->string('style_tag')->index()->comment('Стиль: лофт, минимализм, классика');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Портфолио фотографов');
            });
        }

        // 4. PhotoSession (Types / Packages)
        if (!Schema::hasTable('photography_sessions')) {
            Schema::create('photography_sessions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                
                $table->string('name')->comment('Название пакета: Экспресс, Свадьба, Контент');
                $table->string('vertical_type')->default('photography')->index();
                $table->integer('duration_minutes')->default(60);
                $table->integer('price_kopecks');
                $table->integer('prepayment_kopecks')->default(0);
                
                $table->jsonb('includes_json')->comment('Что входит: ретушь, исходники, макияж');
                $table->boolean('is_active')->default(true);
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Типы фотосессий и пакеты услуг');
            });
        }

        // 5. Booking
        if (!Schema::hasTable('photography_bookings')) {
            Schema::create('photography_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('client_id')->index();
                
                $table->foreignId('session_id')->constrained('photography_sessions');
                $table->foreignId('photographer_id')->nullable()->constrained('photography_photographers');
                $table->foreignId('studio_id')->nullable()->constrained('photography_studios');
                
                $table->timestamp('starts_at')->index();
                $table->timestamp('ends_at')->index();
                
                $table->enum('status', ['pending', 'confirmed', 'paid', 'completed', 'cancelled', 'rescheduled'])->default('pending')->index();
                $table->integer('total_amount_kopecks');
                $table->integer('paid_amount_kopecks')->default(0);
                
                $table->string('idempotency_key')->unique();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Бронирования фотосессий');
            });
        }

        // 6. Review
        if (!Schema::hasTable('photography_reviews')) {
            Schema::create('photography_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->index();
                
                $table->foreignId('booking_id')->constrained('photography_bookings');
                $table->foreignId('photographer_id')->nullable();
                $table->foreignId('studio_id')->nullable();
                $table->foreignId('user_id');
                
                $table->tinyInteger('rating')->unsigned();
                $table->text('comment')->nullable();
                $table->jsonb('photos')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Отзывы пользователей');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('photography_reviews');
        Schema::dropIfExists('photography_bookings');
        Schema::dropIfExists('photography_sessions');
        Schema::dropIfExists('photography_portfolios');
        Schema::dropIfExists('photography_photographers');
        Schema::dropIfExists('photography_studios');
    }
};


