<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-Tenant: User Interest Segmentation & Real-Time Feedback
 * 
 * Адресное формирование интересов пользователей
 * для персонализированных рекомендаций.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Интересы конкретного пользователя (персональный профиль)
        Schema::create('user_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('vertical')->index(); // taxi, clinics, restaurants, flowers
            $table->string('category')->index();
            
            // Сила интереса - динамическая (обновляется в реальном времени)
            $table->float('interest_score')->default(0); // 0-100
            $table->float('engagement_level')->default(0); // 0-1 (view, click, purchase)
            $table->unsignedInteger('interaction_count')->default(0);
            $table->timestamp('last_interaction_at')->nullable();
            
            // Контекст интереса
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('geo_zone')->nullable();
            $table->json('time_preferences')->nullable(); // {morning: 0.2, afternoon: 0.6, evening: 0.2}
            
            // Для ML
            $table->string('correlation_id')->index();
            $table->timestamps();
            
            $table->unique(['user_id', 'vertical', 'category']);
            $table->index(['interest_score', 'vertical']);
            $table->index(['last_interaction_at']);
        });

        // 2. История интересов (для анализа эволюции)
        Schema::create('user_interest_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('vertical');
            $table->string('category');
            $table->date('snapshot_date')->index();
            
            $table->float('interest_score'); // Снимок на эту дату
            $table->float('engagement_level');
            $table->unsignedInteger('events_in_period')->default(0);
            
            // Тренд за период
            $table->string('trend'); // rising, falling, stable
            $table->float('velocity')->nullable(); // Скорость изменения
            
            $table->timestamps();
            $table->index(['user_id', 'snapshot_date']);
            $table->index(['vertical', 'trend']);
        });

        // 3. Реальная обратная связь пользователя на рекомендации
        // Что пользователь думает о рекомендациях
        Schema::create('recommendation_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('vertical');
            $table->string('recommended_category');
            $table->string('model_version')->default('default');
            
            // Реакция пользователя
            $table->enum('feedback', ['positive', 'neutral', 'negative', 'not_clicked'])->index();
            $table->unsignedSmallInteger('rating')->nullable(); // 1-5 stars если есть
            $table->text('comment')->nullable();
            
            // Поведение после рекомендации
            $table->boolean('was_clicked')->default(false);
            $table->boolean('was_purchased')->default(false);
            $table->decimal('purchase_amount', 15, 2)->nullable();
            $table->unsignedInteger('time_to_action_seconds')->nullable();
            
            // Для ML feedback
            $table->string('correlation_id')->index();
            $table->json('context')->nullable(); // device, location, etc.
            $table->timestamps();
            
            $table->index(['user_id', 'feedback', 'created_at']);
            $table->index(['vertical', 'feedback']);
            $table->index(['model_version', 'feedback']);
        });

        // 4. User Cohort Assignment (в каком когорте находится юзер)
        // Динамическое распределение в когорты
        Schema::create('user_cohort_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('cohort_id')->index();
            $table->string('vertical');
            $table->string('persona'); // premium, budget, occasional, loyal
            $table->string('rfm_segment'); // VIP, Regular, At-Risk, Lost
            
            // Уверенность в назначении
            $table->float('confidence')->default(0.5); // 0-1
            
            // Когда переоценить назначение
            $table->timestamp('assigned_at');
            $table->timestamp('expires_at')->nullable(); // Когда переоценить
            $table->timestamp('reevaluate_at')->nullable();
            
            $table->timestamps();
            $table->unique(['user_id', 'vertical']);
            $table->index(['cohort_id', 'vertical']);
            $table->index(['expires_at']);
        });

        // 5. Aggregated User Behavior Metrics (для быстрого анализа)
        // Подсчёты интересов по категориям без сохранения всех событий
        Schema::create('user_category_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('vertical');
            $table->string('category');
            $table->date('metric_date')->index();
            
            // Метрики за день
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->unsignedInteger('purchase_count')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->float('avg_rating_given')->nullable();
            
            // Время провождения
            $table->unsignedInteger('total_time_seconds')->default(0);
            $table->string('peak_hour')->nullable(); // Когда наибольшая активность
            
            // Для аналитики
            $table->string('correlation_id')->index();
            $table->timestamps();
            
            $table->unique(['user_id', 'vertical', 'category', 'metric_date']);
            $table->index(['metric_date', 'vertical']);
        });

        // 6. ML Model Recommendations Log (для тренировки)
        // Какие рекомендации дала модель каждому пользователю
        Schema::create('ml_recommendation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->uuid('cohort_id')->nullable()->index(); // Если рекомендация когорте
            $table->string('vertical');
            $table->string('model_name')->index();
            $table->string('model_version');
            
            // Рекомендация
            $table->string('recommended_category');
            $table->string('recommendation_type'); // cold_start, interest_based, trending, price_range
            $table->float('model_confidence');
            $table->json('ranking_scores')->nullable(); // {category1: 0.9, category2: 0.7}
            
            // Результат и обратная связь
            $table->string('outcome')->default('pending'); // clicked, purchased, ignored
            $table->boolean('label_available')->default(false); // Есть ли уже feedback
            $table->string('correlation_id')->index();
            
            $table->timestamps();
            $table->index(['user_id', 'outcome', 'created_at']);
            $table->index(['model_name', 'outcome']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_recommendation_logs');
        Schema::dropIfExists('user_category_metrics');
        Schema::dropIfExists('user_cohort_assignments');
        Schema::dropIfExists('recommendation_feedback');
        Schema::dropIfExists('user_interest_snapshots');
        Schema::dropIfExists('user_interests');
    }
};
