<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Курсы обучения
        Schema::create('staff_courses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('difficulty_level')->default('beginner'); // beginner, intermediate, advanced
            
            // Контент
            $table->text('content')->nullable();
            $table->json('modules')->nullable(); // Модули курса
            $table->string('thumbnail_url')->nullable();
            $table->integer('duration_minutes')->default(0);
            
            // Детали
            $table->integer('max_participants')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            
            // Статус
            $table->string('status')->default('draft'); // draft, published, archived
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'status']);
        });

        // Записи на курсы
        Schema::create('staff_course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('staff_courses')->onDelete('cascade');
            
            // Прогресс
            $table->decimal('progress', 5, 2)->default(0);
            $table->integer('modules_completed')->default(0);
            $table->integer('total_modules')->default(0);
            
            // Время
            $table->integer('time_spent_minutes')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_date')->nullable();
            
            // Статус
            $table->string('status')->default('in_progress'); // in_progress, completed, dropped, failed
            
            // Оценка
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            
            $table->timestamps();
            
            $table->unique(['staff_id', 'course_id']);
            $table->index(['tenant_id', 'course_id']);
            $table->index(['tenant_id', 'status']);
        });

        // Сертификации
        Schema::create('staff_certifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            
            $table->string('name');
            $table->string('issuing_organization');
            $table->string('credential_id')->nullable();
            
            // Даты
            $table->date('issued_date');
            $table->date('expiry_date')->nullable();
            
            // Документы
            $table->string('certificate_url')->nullable();
            $table->string('certificate_path')->nullable();
            
            // Верификация
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'staff_id']);
            $table->index('expiry_date');
        });

        // План развития (PDP)
        Schema::create('staff_development_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('manager_id')->nullable()->constrained('staff')->onDelete('set null');
            
            // Период
            $table->date('start_date');
            $table->date('end_date');
            
            // Цели
            $table->json('goals')->nullable();
            $table->text('objectives')->nullable();
            
            // Развивающие мероприятия
            $table->json('development_activities')->nullable();
            
            // Статус
            $table->string('status')->default('active'); // active, completed, on_hold
            $table->decimal('progress', 5, 2)->default(0);
            
            // Обратная связь
            $table->text('manager_notes')->nullable();
            $table->text('employee_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'staff_id']);
            $table->index(['tenant_id', 'status']);
        });

        // Тесты знаний
        Schema::create('staff_quizzes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained('staff_courses')->onDelete('set null');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->integer('passing_score')->default(70);
            
            // Вопросы
            $table->json('questions')->nullable();
            
            $table->boolean('is_published')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
        });

        // Результаты тестов
        Schema::create('staff_quiz_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('quiz_id')->constrained('staff_quizzes')->onDelete('cascade');
            
            // Результат
            $table->decimal('score', 5, 2)->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('total_questions')->default(0);
            $table->boolean('passed')->default(false);
            
            // Детали
            $table->json('answers')->nullable();
            $table->integer('time_spent_seconds')->default(0);
            
            $table->timestamp('completed_at')->useCurrent();
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'staff_id']);
            $table->index(['tenant_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_quiz_results');
        Schema::dropIfExists('staff_quizzes');
        Schema::dropIfExists('staff_development_plans');
        Schema::dropIfExists('staff_certifications');
        Schema::dropIfExists('staff_course_enrollments');
        Schema::dropIfExists('staff_courses');
    }
};
