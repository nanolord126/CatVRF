<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vertical_courses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            
            // Бизнес-вертикаль (beauty, hotels, flowers, auto, etc.)
            $table->string('vertical')->index();
            
            // Целевая роль (manager, specialist, administrator, etc.)
            $table->string('target_role')->nullable();
            
            // Уровень сложности (beginner, intermediate, advanced)
            $table->string('difficulty_level')->default('beginner');
            
            // Продолжительность в часах
            $table->integer('duration_hours')->default(0);
            
            // Обязательный курс для вертикали
            $table->boolean('is_required')->default(false);
            
            // Предварительные требования (JSON)
            $table->json('prerequisites')->nullable();
            
            // Цели обучения (JSON)
            $table->json('learning_objectives')->nullable();
            
            // Дополнительные метаданные (JSON)
            $table->json('metadata')->nullable();
            
            // Correlation ID для трассировки
            $table->string('correlation_id')->nullable();
            
            $table->timestamps();
            
            // Индексы для оптимизации запросов
            $table->index(['vertical', 'target_role']);
            $table->index(['vertical', 'difficulty_level']);
            $table->index(['vertical', 'is_required']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vertical_courses');
    }
};
