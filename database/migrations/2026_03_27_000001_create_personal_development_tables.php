<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PersonalDevelopment Domain Migrations — Production Ready 2026
 * 
 * Включает: Coach, Program, Session, Course, Enrollment, Review, Milestone.
 * Реализовано для B2B (корпоративное развитие) и B2C (частные клиенты).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Коучи / Наставники
        if (!Schema::hasTable('pd_coaches')) {
            Schema::create('pd_coaches', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('name')->comment('Имя коуча');
                $table->text('bio')->comment('Биография и достижения');
                $table->jsonb('specializations')->comment('Направления: мотивация, тайм-менеджмент, карьера');
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->bigInteger('hourly_rate_kopecks')->unsigned()->comment('Ставка за час в копейках');
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_active']);
                $table->comment('Таблица коучей и наставников PersonalDevelopment');
            });
        }

        // 2. Программы саморазвития (длительные)
        if (!Schema::hasTable('pd_programs')) {
            Schema::create('pd_programs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('coach_id')->constrained('pd_coaches')->onDelete('cascade');
                $table->string('title')->comment('Название программы');
                $table->text('description')->comment('Описание целей и результата');
                $table->enum('level', ['beginner', 'intermediate', 'advanced', 'vip'])->default('beginner');
                $table->bigInteger('price_kopecks')->unsigned();
                $table->integer('duration_days')->unsigned();
                $table->boolean('is_corporate')->default(false)->comment('Флаг B2B программы');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->comment('Программы долгосрочного развития');
            });
        }

        // 3. Сессии (коуч-сессии, созвоны)
        if (!Schema::hasTable('pd_sessions')) {
            Schema::create('pd_sessions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('coach_id')->constrained('pd_coaches')->onDelete('cascade');
                $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
                $table->datetime('scheduled_at')->index();
                $table->integer('duration_minutes')->default(60);
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
                $table->string('video_link')->nullable()->comment('Ссылка на Zoom/Meet');
                $table->text('notes_after')->nullable()->comment('Заметки коуча после сессии');
                $table->bigInteger('amount_kopecks')->unsigned();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Индивидуальные сессии с коучами');
            });
        }

        // 4. Курсы (контентные)
        if (!Schema::hasTable('pd_courses')) {
            Schema::create('pd_courses', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('title');
                $table->text('content_summary');
                $table->jsonb('modules')->comment('Структура модулей курса');
                $table->bigInteger('price_kopecks')->unsigned();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Обучающие курсы саморазвития');
            });
        }

        // 5. Записи на курсы / подписки
        if (!Schema::hasTable('pd_enrollments')) {
            Schema::create('pd_enrollments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('course_id')->nullable()->constrained('pd_courses');
                $table->foreignId('program_id')->nullable()->constrained('pd_programs');
                $table->integer('progress_percent')->default(0);
                $table->enum('status', ['active', 'completed', 'dropped'])->default('active');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Регистрации пользователей на курсы и программы');
            });
        }

        // 6. Майлстоуны / Цели (Progress Tracking)
        if (!Schema::hasTable('pd_milestones')) {
            Schema::create('pd_milestones', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('enrollment_id')->constrained('pd_enrollments')->onDelete('cascade');
                $table->string('title');
                $table->text('requirements');
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Контрольные точки прогресса пользователя');
            });
        }

        // 7. Отзывы
        if (!Schema::hasTable('pd_reviews')) {
            Schema::create('pd_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->morphs('reviewable'); // Coach, Course, Program
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->integer('rating')->unsigned();
                $table->text('comment');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Отзывы пользователей о коучах и программах');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pd_reviews');
        Schema::dropIfExists('pd_milestones');
        Schema::dropIfExists('pd_enrollments');
        Schema::dropIfExists('pd_courses');
        Schema::dropIfExists('pd_sessions');
        Schema::dropIfExists('pd_programs');
        Schema::dropIfExists('pd_coaches');
    }
};


