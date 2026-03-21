<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026 AI Anonymized Feedback & Interest Aggregation
 * 
 * Обратная отдача для ML: агрегированные интересы без user_id
 * для обучения персонализированных моделей.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Анонимные когорты пользователей (RFM + Persona)
        // Используется для обучения моделей на сегментах
        Schema::create('ai_user_cohorts', function (Blueprint $table) {
            $table->id();
            $table->uuid('cohort_id')->unique()->index();
            $table->string('vertical')->index(); // taxi, clinics, restaurants, flowers
            $table->string('persona')->index(); // premium, budget, occasional, loyal
            $table->string('rfm_segment'); // VIP, Regular, At-Risk, Lost
            $table->unsignedInteger('user_count')->default(0);
            $table->decimal('avg_ltv', 15, 2)->default(0); // Average Lifetime Value
            $table->decimal('avg_order_value', 15, 2)->default(0);
            $table->float('churn_probability')->default(0); // 0-1
            $table->json('geo_zones')->nullable(); // [zone_id, zone_id]
            $table->json('preferred_categories')->nullable(); // [cat1, cat2]
            $table->json('demographic_profile')->nullable(); // {age_bracket, income_level, family_status}
            $table->string('correlation_id')->index();
            $table->timestamps();
            
            $table->unique(['vertical', 'persona', 'rfm_segment']);
            $table->index(['created_at', 'vertical']);
        });

        // 2. Агрегированные события по вертикалям (без user_id)
        // Интересы формируются через event_type + category + geo
        Schema::create('ai_aggregated_interests', function (Blueprint $table) {
            $table->id();
            $table->uuid('interest_id')->unique()->index();
            $table->string('vertical')->index(); // taxi, clinics, restaurants
            $table->string('category')->index(); // cuisine_type, service_type, vehicle_class
            $table->string('interest_type'); // view, click, purchase, search, rating
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('geo_zone')->nullable();
            $table->unsignedBigInteger('event_count')->default(0);
            $table->float('engagement_score')->default(0); // 0-100
            $table->float('conversion_rate')->default(0); // 0-1
            $table->json('time_distribution')->nullable(); // {morning: 0.2, afternoon: 0.5, evening: 0.3}
            $table->json('day_of_week_pattern')->nullable(); // {Mon: 0.1, Tue: 0.15, ... Sun: 0.05}
            $table->string('correlation_id')->index();
            $table->timestamp('last_updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();
            
            $table->unique(['vertical', 'category', 'interest_type', 'geo_zone']);
            $table->index(['engagement_score', 'vertical']);
            $table->index(['created_at']);
        });

        // 3. Профили интересов по kohort'ам (адресная формировка)
        // Какие интересы у какой когорты
        Schema::create('ai_cohort_interests', function (Blueprint $table) {
            $table->id();
            $table->uuid('cohort_id')->index();
            $table->foreign('cohort_id')->references('cohort_id')->on('ai_user_cohorts')->onDelete('cascade');
            
            $table->string('vertical');
            $table->string('category');
            $table->float('interest_strength')->comment('0-100: как сильно когорта интересуется'); // 0-100
            $table->float('predicted_ctr')->nullable(); // Click-Through Rate
            $table->float('predicted_conversion')->nullable(); // Conversion Rate
            $table->unsignedInteger('sample_size')->default(0); // На скольких пользователях посчитано
            $table->json('confidence_interval')->nullable(); // {lower: 0.3, upper: 0.4}
            $table->string('correlation_id')->index();
            $table->timestamps();
            
            $table->unique(['cohort_id', 'vertical', 'category']);
            $table->index(['interest_strength', 'vertical']);
        });

        // 4. Feedback от ML моделей (метрики точности)
        // Модели дают обратную связь на рекомендации
        Schema::create('ai_model_feedback', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->unique()->index();
            $table->string('model_name'); // recommendation_v1, pricing_v2, etc.
            $table->string('vertical');
            $table->string('cohort_id')->nullable()->index(); // На какой когорте работало
            
            // Метрики качества
            $table->float('precision')->nullable(); // Точность предсказаний
            $table->float('recall')->nullable(); // Полнота
            $table->float('f1_score')->nullable(); // Гармоническое среднее
            $table->float('auc_roc')->nullable(); // Area Under ROC Curve
            $table->unsignedBigInteger('predictions_count')->default(0); // На скольких предсказаниях
            $table->unsignedBigInteger('conversions_from_predictions')->default(0); // Из них сконвертилось
            
            // Для отладки
            $table->json('error_analysis')->nullable(); // Где модель ошибалась
            $table->json('feature_importance')->nullable(); // Какие фичи важны
            $table->boolean('approved_for_production')->default(false);
            $table->timestamp('last_validation_at')->nullable();
            $table->string('validation_status'); // pending, approved, rejected, needs_retraining
            $table->timestamps();
            
            $table->index(['model_name', 'vertical', 'validation_status']);
            $table->index(['created_at']);
        });

        // 5. Тренировочные наборы данных (для обучения моделей)
        // Агрегированные события без user_id
        Schema::create('ai_training_datasets', function (Blueprint $table) {
            $table->id();
            $table->uuid('dataset_id')->unique()->index();
            $table->string('vertical');
            $table->string('purpose'); // recommendation, pricing, churn_prediction, fraud_detection
            $table->string('cohort_type')->nullable(); // all, premium, budget, at_risk
            
            // Статистика датасета
            $table->unsignedBigInteger('sample_count')->default(0); // Сколько sample'ов
            $table->unsignedInteger('feature_count')->default(0); // Сколько фич
            $table->float('train_split')->default(0.7);
            $table->float('validation_split')->default(0.15);
            $table->float('test_split')->default(0.15);
            
            // Балансировка и качество
            $table->boolean('is_balanced')->default(false);
            $table->json('class_distribution')->nullable(); // {positive: 0.2, negative: 0.8}
            $table->boolean('is_production_ready')->default(false);
            
            // Версионирование
            $table->string('version')->default('1.0');
            $table->text('description')->nullable();
            $table->string('correlation_id')->index();
            $table->timestamp('expires_at')->nullable(); // Когда датасет устаревает
            $table->timestamps();
            
            $table->unique(['vertical', 'purpose', 'version']);
            $table->index(['created_at', 'is_production_ready']);
        });

        // 6. История изменений интересов (для анализа тренда)
        // Как меняются интересы когорт во времени
        Schema::create('ai_interest_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('cohort_id')->index();
            $table->string('vertical');
            $table->string('category');
            $table->date('date')->index(); // День снимка
            
            // Снимок интереса на дату
            $table->float('interest_strength')->default(0); // 0-100
            $table->float('engagement_delta')->nullable(); // Изменение за день
            $table->unsignedBigInteger('event_count_daily')->default(0);
            
            // Тренд
            $table->string('trend')->default('stable'); // rising, falling, stable
            $table->float('trend_velocity')->nullable(); // Скорость изменения
            
            $table->string('correlation_id')->index();
            $table->timestamps();
            
            $table->unique(['cohort_id', 'vertical', 'category', 'date']);
            $table->index(['date', 'vertical']);
            $table->index(['trend', 'trend_velocity']);
        });

        // 7. Рекомендации на основе интересов (для фидбека)
        // Какие рекомендации дала модель когорте
        Schema::create('ai_recommendation_audit', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->unique()->index();
            $table->uuid('cohort_id')->index();
            $table->string('model_name');
            $table->string('vertical');
            
            // Рекомендация
            $table->string('recommended_category')->index();
            $table->string('recommendation_reason'); // interest_match, trending, price_range, geo_proximity
            $table->float('recommendation_confidence')->comment('0-1: уверенность модели');
            
            // Результат
            $table->string('outcome')->default('pending'); // pending, clicked, purchased, ignored, negative_feedback
            $table->timestamp('feedback_received_at')->nullable();
            $table->json('feedback_metadata')->nullable(); // {dwell_time: 5, scroll_depth: 0.8}
            
            // Для обучения
            $table->boolean('is_training_sample')->default(true);
            $table->string('correlation_id_parent')->nullable(); // Цепочка событий
            
            $table->timestamps();
            $table->index(['model_name', 'outcome', 'created_at']);
            $table->index(['cohort_id', 'vertical']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendation_audit');
        Schema::dropIfExists('ai_interest_history');
        Schema::dropIfExists('ai_training_datasets');
        Schema::dropIfExists('ai_model_feedback');
        Schema::dropIfExists('ai_cohort_interests');
        Schema::dropIfExists('ai_aggregated_interests');
        Schema::dropIfExists('ai_user_cohorts');
    }
};
