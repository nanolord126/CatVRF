declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_taste_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->onDelete('cascade');
            $table->tenant_id();

            // Основные embeddings (384 или 768 размерность)
            $table->json('embedding')->nullable()->comment('Vectorized representation of user tastes (SentenceTransformers)');

            // Явные предпочтения (заполняет пользователь)
            $table->json('explicit_preferences')->nullable()->comment('Explicit user preferences: sizes, brands, styles, diets');

            // Неявные оценки (ML собирает автоматически)
            $table->json('implicit_scores')->nullable()->comment('ML-scores for categories/styles: {"fashion": 0.92, "italian_food": 0.87}');

            // Профиль размеров по вертикалям
            $table->json('size_profile')->nullable()->comment('Size preferences: {"clothing": "M", "shoes": "42", "rings": "17"}');

            // Избранные бренды (топ-10)
            $table->json('favorite_brands')->nullable()->comment('Top favorite brands: ["Nike", "Zara", "Lush"]');

            // Цветовые предпочтения
            $table->json('color_preferences')->nullable()->comment('Preferred colors: {"primary": "navy blue", "secondary": "white", "accents": "gold"}');

            // История поведения за последние 30 дней (для анализа)
            $table->json('recent_interactions')->nullable()->comment('Recent interactions: views, purchases, wishlist adds');

            // ML-версия (для кэширования и отката)
            $table->integer('ml_version')->default(1)->index()->comment('Version of ML model used for this profile');

            // Статус анализа
            $table->enum('analysis_status', [
                'pending',      // Ожидает первого анализа
                'processed',    // Обработан
                'retraining',   // В процессе переобучения
                'error'         // Ошибка анализа
            ])->default('pending')->index();

            // Количество данных для анализа
            $table->integer('data_points_count')->default(0)->comment('Total user interaction data points analyzed');

            // Confidence score (0-1)
            $table->decimal('confidence_score', 5, 4)->default(0)->comment('ML confidence in the profile (0-1)');

            // Персональные рекомендации включены?
            $table->boolean('personalization_enabled')->default(true)->index()->comment('User can disable personalization (but data still collected)');

            // Статистика использования профиля
            $table->integer('recommendation_views')->default(0)->comment('How many times recommendations based on this profile were shown');
            $table->integer('recommendation_clicks')->default(0)->comment('How many clicks on recommendations');
            $table->decimal('recommendation_ctr', 5, 4)->default(0)->comment('Click-through rate for recommendations');

            // Audit
            $table->string('correlation_id')->nullable()->index();
            $table->timestamp('last_analyzed_at')->nullable();
            $table->timestamp('last_model_update_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['user_id', 'analysis_status']);
            $table->index(['user_id', 'last_analyzed_at']);
            $table->index(['personalization_enabled', 'updated_at']);

            $table->comment('ML taste profiles for personalized recommendations. Core of RecommendationService (CANON 2026)');
        });

        // Таблица для истории взаимодействий пользователя (события)
        Schema::create('user_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tenant_id();

            // Тип взаимодействия
            $table->enum('interaction_type', [
                'product_view',          // Просмотр товара
                'product_click',         // Клик на товар
                'add_to_cart',           // Добавление в корзину
                'remove_from_cart',      // Удаление из корзины
                'add_to_wishlist',       // Добавление в вишлист
                'purchase',              // Покупка
                'rating_submit',         // Оставленный отзыв
                'share',                 // Поделился товаром
                'ai_constructor_use',    // Использование AI-конструктора
            ])->index();

            // Контекст взаимодействия
            $table->morphs('interactable')->comment('Product, Service, or other entity user interacted with');

            // Вертикаль
            $table->string('vertical')->nullable()->index();

            // Категория
            $table->string('category')->nullable();

            // Для товаров/услуг: цена, размер, цвет и т.д.
            $table->json('item_attributes')->nullable()->comment('Product attributes: price, size, color, brand');

            // Время, проведённое (в секундах)
            $table->integer('duration_seconds')->default(0);

            // Метаданные (гео, источник трафика и т.д.)
            $table->json('metadata')->nullable()->comment('Additional context: IP, device, source, search_query');

            // Audit
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            // Индексы для эффективного анализа
            $table->index(['user_id', 'interaction_type', 'created_at']);
            $table->index(['user_id', 'vertical', 'created_at']);

            $table->comment('User interaction events for ML taste profile analysis (CANON 2026)');
        });

        // Таблица для сохранения embeddings товаров (для поиска похожих)
        Schema::create('product_embeddings', function (Blueprint $table) {
            $table->id();
            $table->tenant_id();

            // Товар/услуга (polymorphic)
            $table->morphs('embeddable')->comment('Product, Service, or other entity');

            // Embedding (вектор)
            $table->json('embedding')->comment('Vectorized representation of product (SentenceTransformers)');

            // Текст, использованный для embedding
            $table->text('source_text')->comment('Original text used to create embedding');

            // Версия модели embeddings
            $table->integer('model_version')->default(1);

            // Метаданные товара
            $table->json('product_metadata')->nullable()->comment('Price, category, brand, etc');

            // Audit
            $table->timestamp('updated_at')->nullable();

            // Индексы
            $table->index(['embeddable_type', 'embeddable_id']);
            $table->index(['tenant_id', 'model_version']);

            $table->comment('Product embeddings for cosine similarity search in recommendations (CANON 2026)');
        });

        // Таблица для кэширования рекомендаций на основе ML
        Schema::create('ml_recommendation_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tenant_id();

            // Ключ кэша (user_id + vertical + context)
            $table->string('cache_key')->unique()->index();

            // Рекомендации (ID товаров/услуг)
            $table->json('recommended_items')->comment('Array of recommended item IDs');

            // Scores
            $table->json('recommendation_scores')->comment('ML scores for each item');

            // Тип рекомендации
            $table->string('recommendation_type')->default('hybrid')->comment('hybrid, ml_only, popular');

            // Время кэширования
            $table->integer('ttl_seconds')->default(3600)->comment('TTL for this cache entry');
            $table->timestamp('expires_at')->index();

            // Статистика
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('ctr', 5, 4)->default(0);

            $table->timestamps();

            $table->comment('Cache for ML-based recommendations (CANON 2026)');
        });

        // Таблица для логирования ML-решений
        Schema::create('ml_decision_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tenant_id();

            // Тип решения
            $table->enum('decision_type', [
                'show_recommendation',
                'personalize_search',
                'ai_constructor',
                'price_adjustment',
                'promo_suggestion',
            ]);

            // Контекст
            $table->json('context')->comment('Context: action, item_id, parameters');

            // ML inputs (признаки)
            $table->json('ml_features')->comment('Features used for decision');

            // ML output (результат)
            $table->json('ml_output')->comment('ML model output/scores');

            // Финальное решение
            $table->json('final_decision')->comment('Final decision taken');

            // Confidence
            $table->decimal('confidence_score', 5, 4)->default(0);

            // ML-версия
            $table->integer('ml_version')->default(1);

            // Audit
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'decision_type', 'created_at']);

            $table->comment('ML decision logs for audit and optimization (CANON 2026)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_decision_logs');
        Schema::dropIfExists('ml_recommendation_cache');
        Schema::dropIfExists('product_embeddings');
        Schema::dropIfExists('user_interactions');
        Schema::dropIfExists('user_taste_profiles');
    }
};
