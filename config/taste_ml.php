<?php

declare(strict_types=1);

/**
 * CANON 2026: Taste ML Analysis Configuration
 * Конфигурация для ML-анализа вкусов пользователей
 */

return [
    // === EMBEDDINGS ===

    'embeddings' => [
        // Размерность embedding'а (384 или 768)
        'dimension' => env('TASTE_EMBEDDING_DIMENSION', 384),

        // Провайдер embeddings (openai, sentencetransformers, local)
        'provider' => env('TASTE_EMBEDDING_PROVIDER', 'sentencetransformers'),

        // OpenAI API key (если используется OpenAI)
        'openai_key' => env('OPENAI_API_KEY', ''),

        // Модель OpenAI для embeddings
        'openai_model' => 'text-embedding-3-small', // или text-embedding-3-large

        // Модель SentenceTransformers
        'sentencetransformers_model' => 'sentence-transformers/all-MiniLM-L6-v2',

        // Путь к локальной модели (если используется local)
        'local_model_path' => storage_path('models/taste_embeddings'),
    ],

    // === ML РЕКОМЕНДАЦИИ ===

    'recommendations' => [
        // Гибридная модель (40/30/20/10)
        'weights' => [
            'ml_taste' => 0.40,           // ML вкусы пользователя
            'popularity' => 0.30,         // Популярные в гео
            'novelty' => 0.20,            // Новинки
            'promotions' => 0.10,         // Акции
        ],

        // Минимальный cosine similarity для включения товара
        'min_similarity' => 0.50,

        // Максимальное влияние ML на выдачу
        'max_ml_weight' => 0.70,

        // Пороги для снижения ML-веса
        'ctr_threshold_low' => 0.05,      // Если CTR < 5% → снизить ML до 25%
        'ctr_threshold_critical' => 0.03, // Если CTR < 3% → отключить ML совсем

        // Батч-размер для job'а обновления рекомендаций
        'batch_size' => 1000,

        // Кэширование рекомендаций (в секундах)
        'cache_ttl' => 3600, // 1 час
    ],

    // === PROFILE RECALCULATION ===

    'recalculation' => [
        // Расписание пересчёта (в UTC)
        'schedule' => '04:30', // 04:30 UTC = 07:30 MSK

        // Максимум профилей на обновление за раз
        'max_profiles_per_batch' => 1000,

        // Минимум взаимодействий для пересчёта
        'min_interactions_required' => 5,

        // Сохранять последние N взаимодействий в истории
        'interaction_history_limit' => 100,

        // Timeout для job'а (в секундах)
        'job_timeout' => 600,

        // Количество retry'ев
        'job_tries' => 3,
    ],

    // === ЯВНЫЕ ПРЕДПОЧТЕНИЯ (EXPLICIT) ===

    'explicit_preferences' => [
        'size_categories' => [
            'clothing' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'shoes' => [30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48],
            'jeans' => [24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 38, 40],
        ],

        'diet_restrictions' => [
            'vegetarian',
            'vegan',
            'gluten_free',
            'dairy_free',
            'nut_free',
            'keto',
            'low_carb',
            'halal',
            'kosher',
        ],

        'allergies' => [
            'peanuts',
            'tree_nuts',
            'milk',
            'eggs',
            'fish',
            'shellfish',
            'sesame',
            'soy',
        ],

        'style_preferences' => [
            'minimalism',
            'maximalism',
            'boho',
            'vintage',
            'modern',
            'classic',
            'gothic',
            'bohemian',
            'luxury',
            'casual',
            'sporty',
            'romantic',
        ],

        'color_palettes' => [
            'warm' => ['red', 'orange', 'yellow', 'brown', 'gold'],
            'cool' => ['blue', 'purple', 'green', 'pink', 'silver'],
            'neutral' => ['black', 'white', 'grey', 'beige', 'cream'],
        ],
    ],

    // === FRAUD DETECTION ===

    'fraud_detection' => [
        // Проверять ли на фрод перед выдачей рекомендаций
        'enabled' => true,

        // Максимум рекомендаций за час для одного пользователя
        'recommendations_per_hour_limit' => 500,

        // Максимум кликов по рекомендациям за час
        'clicks_per_hour_limit' => 200,

        // Максимум покупок за час (подозрение на автоматизацию)
        'purchases_per_hour_limit' => 10,
    ],

    // === RATE LIMITING ===

    'rate_limiting' => [
        // Максимум запросов рекомендаций на пользователя в минуту
        'recommendations_per_minute' => 100,

        // Максимум запросов AI-конструктора в день
        'ai_constructor_per_day' => 50,

        // Окно для скользящей очереди (в минутах)
        'window_minutes' => 1,
    ],

    // === CACHING ===

    'cache' => [
        // Сохранять ли explicit preferences в кэше
        'explicit_preferences' => true,
        'explicit_preferences_ttl' => 86400, // 24 часа

        // Сохранять ли implicit scores в кэше
        'implicit_scores' => true,
        'implicit_scores_ttl' => 86400,

        // Сохранять ли рекомендации
        'recommendations' => true,
        'recommendations_ttl' => 3600, // 1 час

        // Store кэша (redis, file, array)
        'store' => env('TASTE_CACHE_STORE', 'redis'),

        // Префикс ключей в кэше
        'prefix' => 'taste:',
    ],

    // === METRICS & MONITORING ===

    'metrics' => [
        // Сохранять ли метрики качества
        'enabled' => true,

        // Период расчёта метрик (в днях)
        'calculation_period' => 30,

        // Целевой CTR (для алертов)
        'target_ctr' => 0.08,

        // Целевой acceptance rate
        'target_acceptance_rate' => 0.20,

        // Целевой average similarity
        'target_avg_similarity' => 0.65,

        // Алерты в Sentry
        'sentry_alerts' => env('SENTRY_DSN') !== null,
    ],

    // === GDPR & PRIVACY ===

    'privacy' => [
        // Дни хранения взаимодействий (потом удалять)
        'interaction_retention_days' => 90,

        // Дни хранения embeddings после удаления пользователя
        'embedding_retention_days' => 30,

        // Хранить ли PII в логах
        'log_pii' => false,

        // Анонимизировать ли данные для обучения моделей
        'anonymize_training_data' => true,
    ],

    // === DATABASE ===

    'database' => [
        // Использовать ли pgvector для PostgreSQL
        'use_pgvector' => env('DB_CONNECTION') === 'pgsql',

        // Размер batch'а для bulk-операций
        'batch_size' => 1000,

        // Использовать ли raw queries для performance
        'use_raw_queries' => true,
    ],

    // === LOGGING ===

    'logging' => [
        // Канал для логирования ML операций
        'channel' => 'audit',

        // Логировать ли взаимодействия (может быть много данных)
        'log_interactions' => true,

        // Логировать ли embeddings recalculation
        'log_recalculation' => true,

        // Уровень логирования
        'level' => env('APP_DEBUG') ? 'debug' : 'info',
    ],

    // === FEATURE FLAGS ===

    'feature_flags' => [
        // Включена ли персонализация по умолчанию
        'personalization_enabled' => true,

        // Использовать ли новую гибридную модель рекомендаций
        'hybrid_recommendations' => true,

        // Использовать ли AI-конструкторы
        'ai_constructors_enabled' => true,

        // Использовать ли real-time обновления профилей
        'realtime_updates' => env('TASTE_REALTIME_UPDATES', false),
    ],
];
