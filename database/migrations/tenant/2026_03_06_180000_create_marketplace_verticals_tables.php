<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. FLOWERS (Цветы: букеты, доставка)
        Schema::create('flowers_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->json('composition')->nullable(); // Состав букета
            $table->boolean('is_available')->default(true);
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // 2. RESTAURANTS (Рестораны: меню, столы)
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->integer('capacity');
            $table->string('status')->default('available');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('restaurant_menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. TAXI (Такси: поездки, водители)
        Schema::create('taxi_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->string('from_address');
            $table->string('to_address');
            $table->decimal('fare', 15, 2);
            $table->string('status')->default('pending');
            $table->uuid('correlation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. CLINICS & VET (Медицина и Ветклиники: записи, пациенты)
        Schema::create('medical_appointments', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // HUMAN or ANIMAL
            $table->foreignId('doctor_id')->constrained('users');
            $table->string('patient_name');
            $table->dateTime('scheduled_at');
            $table->text('notes')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });

        // 5. EVENTS (Мероприятия: билеты, брони)
        if (!Schema::hasTable('venues')) {
            Schema::create('venues', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('address');
                $table->integer('capacity');
                $table->json('geo_location')->nullable();
                $table->json('hall_layout')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->string('status')->default('draft');
                $table->json('seating_data')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
                $table->string('category');
                $table->decimal('price', 15, 2);
                $table->integer('quantity_available');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('event_bookings')) {
            Schema::create('event_bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('events');
                $table->string('event_name'); // redundant but kept from original schema
                $table->decimal('price', 15, 2);
                $table->integer('tickets_count');
                $table->uuid('correlation_id')->nullable();
                $table->timestamps();
            });
        }

        // 6. SPORTS (Спорт: залы, тренеры, расписание)
        if (!Schema::hasTable('gyms')) {
            Schema::create('gyms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('address');
                $table->json('geo_location')->nullable();
                $table->json('occupancy_data')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('coaches')) {
            Schema::create('coaches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->string('specialization');
                $table->text('bio')->nullable();
                $table->decimal('hourly_rate', 15, 2);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('training_schedules')) {
            Schema::create('training_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gym_id')->constrained('gyms');
                $table->foreignId('coach_id')->constrained('coaches');
                $table->string('training_type');
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->integer('max_participants');
                $table->timestamps();
            });
        }

        // 7. EDUCATION (Обучение: курсы, модули, уроки)
        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('category');
                $table->decimal('price', 15, 2);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('course_modules')) {
            Schema::create('course_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
                $table->string('title');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lessons')) {
            Schema::create('lessons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_id')->constrained('course_modules')->onDelete('cascade');
                $table->string('title');
                $table->string('video_url')->nullable();
                $table->text('content')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('education_courses')) {
            Schema::create('education_courses', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('type'); // LESSON, WORKSHOP, SPORT_SESSION
                $table->decimal('price', 15, 2);
                $table->integer('duration_minutes');
                $table->timestamps();
            });
        }

        // ECOSYSTEM: HR EXCHANGE (Биржа персонала)
        Schema::create('hr_exchange_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('role_code');
            $table->decimal('hourly_rate', 15, 2);
            $table->string('status')->default('open');
            $table->timestamps();
        });

        // ECOSYSTEM: B2B SUPPLY (Производитель -> Бизнес)
        Schema::create('b2b_supply_offers', function (Blueprint $table) {
            $table->id();
            $table->string('manufacturer_name');
            $table->string('product_name');
            $table->decimal('wholesale_price', 15, 2);
            $table->integer('min_batch');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_supply_offers');
        Schema::dropIfExists('hr_exchange_offers');
        Schema::dropIfExists('education_courses');
        Schema::dropIfExists('event_bookings');
        Schema::dropIfExists('medical_appointments');
        Schema::dropIfExists('taxi_trips');
        Schema::dropIfExists('restaurant_menu_items');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('flowers_items');
    }
};
