<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 2026_03_27_000001_create_premium_education_tables.
 * Канон 2026: Idempotency, Table Comments, correlation_id, uuid, tenant_id.
 * Создание инфраструктуры для Премиум Онлайн-образования и Корпоративного обучения.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Таблица Курсов (Courses)
        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('title')->comment('Название курса');
                $table->text('description')->nullable()->comment('Описание программы');
                $table->string('level')->default('middle')->comment('Уровень сложности: junior, middle, senior, expert');
                $table->unsignedInteger('price_kopecks')->unsigned()->comment('Цена для B2C в копейках');
                $table->unsignedInteger('corporate_price_kopecks')->unsigned()->comment('Базовая цена для B2B (за сотрудника)');
                $table->jsonb('syllabus')->nullable()->comment('Учебный план (AI-generated)');
                $table->boolean('is_active')->default(true)->index();
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable()->comment('Теги для аналитики и рекомендаций');
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Курсы премиального онлайн-обучения');
            });
        }

        // 2. Уроки и интерактивные занятия (Lessons)
        if (!Schema::hasTable('lessons')) {
            Schema::create('lessons', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
                $table->string('title');
                $table->text('content')->nullable()->comment('Материалы урока');
                $table->enum('type', ['video_call', 'recorded_video', 'interactive_lab', 'quiz'])->default('recorded_video');
                $table->string('meeting_url')->nullable()->comment('Ссылка на интерактивный видеозвонок CRM 2026');
                $table->integer('duration_minutes')->default(45);
                $table->integer('order')->default(0);
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Уроки и сессии внутри курсов');
            });
        }

        // 3. Корпоративные контракты (B2B Contracts)
        if (!Schema::hasTable('corporate_contracts')) {
            Schema::create('corporate_contracts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index()->comment('ID компании-клиента (B2B)');
                $table->unsignedBigInteger('provider_tenant_id')->index()->comment('ID учебного центра');
                $table->string('contract_number')->unique();
                $table->unsignedBigInteger('total_amount_kopecks')->comment('Общая сумма контракта');
                $table->integer('slots_count')->comment('Количество оплаченных мест для сотрудников');
                $table->integer('used_slots_count')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('metadata')->nullable()->comment('Доп. условия контракта');
                $table->timestamps();

                $table->comment('Корпоративные контракты на обучение сотрудников');
            });
        }

        // 4. Зачисления и Прогресс (Enrollments)
        if (!Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('course_id')->constrained('courses');
                $table->foreignId('corporate_contract_id')->nullable()->constrained('corporate_contracts');
                
                $table->enum('mode', ['b2c', 'b2b'])->default('b2c');
                $table->integer('progress_percent')->default(0);
                $table->jsonb('ai_path')->nullable()->comment('Персонализированная траектория AI');
                $table->timestamp('completed_at')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'course_id']);
                $table->comment('История обучения и зачисления пользователей');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('corporate_contracts');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('courses');
    }
};
