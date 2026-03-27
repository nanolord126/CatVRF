<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026: Миграция вертикали Education.
 * Изоляция тенантов, UUID, correlation_id, аудит.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Преподаватели / Инструкторы
        if (!Schema::hasTable('teachers')) {
            Schema::create('teachers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('specialization');
                $table->text('bio')->nullable();
                $table->jsonb('experience')->nullable(); // JSON с историей работы
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->boolean('is_active')->default(true)->index();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Преподаватели платформы Education 2026');
            });
        }

        // 2. Курсы
        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('teacher_id')->constrained('teachers');
                $table->string('title');
                $table->text('description');
                $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
                $table->integer('price')->default(0); // В копейках
                $table->integer('subscription_price')->nullable(); // Цена абонемента в месяц
                $table->integer('duration_hours')->nullable();
                $table->string('status')->default('draft')->index(); // draft, published, archived
                $table->boolean('is_b2b')->default(false)->index();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Учебные курсы: модульное обучение, B2B/B2C');
            });
        }

        // 3. Модули курса
        if (!Schema::hasTable('course_modules')) {
            Schema::create('course_modules', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
                $table->string('title');
                $table->integer('order')->default(0);
                $table->text('description')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Модули в рамках курса');
            });
        }

        // 4. Уроки
        if (!Schema::hasTable('lessons')) {
            Schema::create('lessons', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('course_module_id')->constrained('course_modules')->onDelete('cascade');
                $table->string('title');
                $table->enum('type', ['video', 'text', 'quiz', 'live'])->default('video');
                $table->text('content')->nullable(); // Для текстовых уроков или описаний
                $table->string('video_url')->nullable();
                $table->integer('duration_minutes')->default(0);
                $table->integer('order')->default(0);
                $table->boolean('is_free_preview')->default(false);
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Уроки: видео, тесты, живые трансляции');
            });
        }

        // 5. Зачисления (Enrollments)
        if (!Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('course_id')->constrained('courses');
                $table->string('type')->default('full'); // full (весь курс), module (через подписку), b2b (групповой)
                $table->timestamp('expires_at')->nullable();
                $table->integer('progress_percent')->default(0);
                $table->string('status')->default('active')->index(); // active, completed, suspended
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'course_id']);
                $table->comment('История покупок и зачислений на курсы');
            });
        }

        // 6. Видеозвонки (Уроки в реальном времени)
        if (!Schema::hasTable('video_calls')) {
            Schema::create('video_calls', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('lesson_id')->nullable()->constrained('lessons');
                $table->foreignId('teacher_id')->constrained('teachers');
                $table->string('room_id')->unique(); // Идентификатор WebRTC комнаты
                $table->timestamp('scheduled_at');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->string('status')->default('scheduled')->index(); // scheduled, live, ended
                $table->jsonb('participants_logs')->nullable(); // Кто заходил/выходил
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Интеграция с WebRTC для живых занятий');
            });
        }

        // 7. Отзывы
        if (!Schema::hasTable('course_reviews')) {
            Schema::create('course_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('course_id')->constrained('courses');
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->comment('Отзывы студентов о курсах');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_reviews');
        Schema::dropIfExists('video_calls');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('teachers');
    }
};
