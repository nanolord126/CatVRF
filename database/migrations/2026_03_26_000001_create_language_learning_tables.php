<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Миграция для вертикали LanguageLearning (Изучение языков).
     * Создает структуру для школ, учителей, курсов, уроков, записей, отзывов и видеозвонков.
     */
    public function up(): void
    {
        // 1. Языковые школы
        if (!Schema::hasTable('language_schools')) {
            Schema::create('language_schools', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index()->comment('Изоляция тенанта');
                $table->string('name')->comment('Название школы');
                $table->text('description')->nullable();
                $table->string('address')->nullable();
                $table->jsonb('languages')->comment('Список преподаваемых языков');
                $table->boolean('is_verified')->default(false);
                $table->jsonb('settings')->nullable()->comment('Настройки школы (B2B/B2C лимиты)');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Таблица языковых школ');
            });
        }

        // 2. Преподаватели
        if (!Schema::hasTable('language_teachers')) {
            Schema::create('language_teachers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('school_id')->nullable()->constrained('language_schools')->onDelete('set null');
                $table->string('full_name');
                $table->string('native_language');
                $table->jsonb('teaching_languages')->comment('Языки и уровни (A1-C2)');
                $table->text('bio')->nullable();
                $table->integer('experience_years')->default(0);
                $table->decimal('rating', 3, 2)->default(0);
                $table->integer('hourly_rate')->default(0)->comment('Ставка в копейках');
                $table->jsonb('availability')->nullable()->comment('Расписание доступности');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->comment('Таблица преподавателей иностранных языков');
            });
        }

        // 3. Курсы
        if (!Schema::hasTable('language_courses')) {
            Schema::create('language_courses', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('school_id')->constrained('language_schools');
                $table->foreignId('teacher_id')->constrained('language_teachers');
                $table->string('title');
                $table->string('language');
                $table->string('level_from')->default('A1');
                $table->string('level_to')->default('B2');
                $table->text('syllabus')->nullable()->comment('Программа курса');
                $table->integer('price_total')->default(0)->comment('Полная цена в копейках');
                $table->integer('price_per_module')->default(0)->comment('Цена за модуль');
                $table->integer('max_students')->default(10);
                $table->enum('type', ['group', 'individual', 'club', 'intensive'])->default('group');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->comment('Курсы изучения языков');
            });
        }

        // 4. Уроки
        if (!Schema::hasTable('language_lessons')) {
            Schema::create('language_lessons', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('course_id')->constrained('language_courses')->onDelete('cascade');
                $table->string('topic');
                $table->text('description')->nullable();
                $table->dateTime('scheduled_at')->index();
                $table->integer('duration_minutes')->default(60);
                $table->string('status')->default('scheduled')->comment('scheduled, active, completed, cancelled');
                $table->text('homework')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Конкретные занятия в рамках курса');
            });
        }

        // 5. Записи на курсы (Enrollments)
        if (!Schema::hasTable('language_enrollments')) {
            Schema::create('language_enrollments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->comment('Ученик (B2C) или Представитель (B2B)');
                $table->foreignId('course_id')->constrained('language_courses');
                $table->integer('paid_amount')->default(0);
                $table->string('payment_status')->default('pending');
                $table->string('status')->default('active')->comment('active, finished, on_hold');
                $table->jsonb('progress_data')->nullable()->comment('Процент прохождения, оценки');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Регистрации учеников на курсы');
            });
        }

        // 6. Видеозвонки (WebRTC Sessions)
        if (!Schema::hasTable('language_videocalls')) {
            Schema::create('language_videocalls', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('lesson_id')->constrained('language_lessons');
                $table->string('room_id')->unique()->comment('Уникальный ID комнаты WebRTC');
                $table->string('provider')->default('internal')->comment('internal, zoom, jitsi');
                $table->dateTime('started_at')->nullable();
                $table->dateTime('ended_at')->nullable();
                $table->integer('recorded_size_bytes')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Сессии видеосвязи для обучения');
            });
        }

        // 7. Отзывы
        if (!Schema::hasTable('language_reviews')) {
            Schema::create('language_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id');
                $table->morphs('reviewable'); // Курс или Учитель
                $table->integer('rating')->default(5);
                $table->text('comment');
                $table->boolean('is_published')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Отзывы учеников об обучении');
            });
        }
    }

    /**
     * Откат миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('language_reviews');
        Schema::dropIfExists('language_videocalls');
        Schema::dropIfExists('language_enrollments');
        Schema::dropIfExists('language_lessons');
        Schema::dropIfExists('language_courses');
        Schema::dropIfExists('language_teachers');
        Schema::dropIfExists('language_schools');
    }
};
