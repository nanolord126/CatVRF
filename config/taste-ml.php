<?php

declare(strict_types=1);

/**
 * config/taste-ml.php
 * 
 * Конфигурация ML-анализа вкусов пользователя v2.0
 * CANON 2026: Production-ready
 */

return [
    // ========== EMBEDDING CONFIGURATION ==========

    'embeddings' => [
        'model' => env('TASTE_EMBEDDINGS_MODEL', 'text-embedding-3-large'),
        'dimensions' => env('TASTE_EMBEDDINGS_DIMENSIONS', 768),  // 768 или 384
        'provider' => env('TASTE_EMBEDDINGS_PROVIDER', 'openai'),  // openai | huggingface
    ],

    // ========== ML MODEL CONFIGURATION ==========

    'model' => [
        'version' => env('TASTE_MODEL_VERSION', 'taste-v2.3-20260325'),
        'path' => storage_path('models/taste/'),
        'type' => 'lightgbm',  // lightgbm | xgboost | sklearn
        'auto_retrain' => env('TASTE_MODEL_AUTO_RETRAIN', true),
        'retrain_schedule' => '03:00',  // UTC
        'min_accuracy' => 0.85,  // Если accuracy < 85%, откатить на старую версию
    ],

    // ========== DATA QUALITY THRESHOLDS ==========

    'quality_thresholds' => [
        'cold_start' => 5,              // Минимум взаимодействий для холодного старта
        'ready_for_recommendations' => 10,  // Минимум для ML-рекомендаций
        'mature_profile' => 50,         // Профиль считается "зрелым"
    ],

    // ========== RECOMMENDATION INFLUENCE ==========

    'recommendation' => [
        'max_influence' => 0.70,         // Max влияние ML на выдачу (30% остального)
        'min_influence' => 0.30,         // Min влияние для новых пользователей
        'diversity_ratio' => 0.20,       // 20% новинок и акций (не ML)
    ],

    // ========== CATEGORY SCORES CONFIGURATION ==========

    'categories' => [
        // Все возможные категории для ML-анализа
        'fashion_women' => ['weight' => 1.0, 'enabled' => true],
        'fashion_men' => ['weight' => 1.0, 'enabled' => true],
        'fashion_kids' => ['weight' => 0.9, 'enabled' => true],
        'italian_food' => ['weight' => 1.0, 'enabled' => true],
        'japanese_food' => ['weight' => 1.0, 'enabled' => true],
        'asian_food' => ['weight' => 1.0, 'enabled' => true],
        'minimal_interior' => ['weight' => 1.0, 'enabled' => true],
        'scandinavian_interior' => ['weight' => 1.0, 'enabled' => true],
        'natural_cosmetics' => ['weight' => 1.0, 'enabled' => true],
        'luxury_beauty' => ['weight' => 1.0, 'enabled' => true],
        'eco_friendly' => ['weight' => 0.8, 'enabled' => true],
        'sports' => ['weight' => 1.0, 'enabled' => true],
        'travel' => ['weight' => 1.0, 'enabled' => true],
    ],

    // ========== INTERACTION WEIGHTS ==========

    'interactions' => [
        // Вес каждого типа взаимодействия в ML-анализе
        'view' => 0.1,                  // Просмотр товара = 0.1
        'cart_add' => 0.5,              // Добавление в корзину = 0.5
        'cart_remove' => -0.3,          // Удаление из корзины = -0.3 (отклонение)
        'wishlist_add' => 0.3,          // Добавление в вишлист = 0.3
        'purchase' => 1.0,              // Покупка = 1.0 (максимальный вес)
        'review' => 0.4,                // Оставление отзыва = 0.4
        'share' => 0.2,                 // Поделиться = 0.2
    ],

    // ========== BEHAVIORAL METRICS ==========

    'behavioral' => [
        // Параметры для анализа поведения
        'session_duration_window' => 90,     // Дни для анализа
        'purchase_frequency_window' => 180,  // Дни для анализа
        'price_sensitivity_threshold' => 1000,  // Пороговое значение в руб.
    ],

    // ========== CACHING ==========

    'cache' => [
        'enabled' => env('TASTE_CACHE_ENABLED', true),
        'ttl_embeddings' => 86400,      // 24 часа
        'ttl_scores' => 3600,           // 1 час
        'ttl_profile' => 300,           // 5 минут (рекомендации)
        'store' => 'redis',             // redis | memcached | database
    ],

    // ========== AI CONSTRUCTORS CONFIGURATION ==========

    'constructors' => [
        'beauty' => [
            'enabled' => env('AI_BEAUTY_CONSTRUCTOR_ENABLED', true),
            'vision_model' => 'gpt-4-vision',
            'max_file_size' => 5 * 1024 * 1024,  // 5 MB
            'supported_formats' => ['jpg', 'jpeg', 'png', 'webp'],
        ],
        'interior' => [
            'enabled' => env('AI_INTERIOR_CONSTRUCTOR_ENABLED', true),
            'vision_model' => 'gpt-4-vision',
        ],
        'fashion' => [
            'enabled' => env('AI_FASHION_CONSTRUCTOR_ENABLED', true),
            'vision_model' => 'gpt-4-vision',
        ],
    ],

    // ========== LOGGING ==========

    'logging' => [
        'channel' => 'audit',
        'debug_mode' => env('TASTE_DEBUG', false),
        'log_interactions' => env('TASTE_LOG_INTERACTIONS', true),
        'log_embeddings' => env('TASTE_LOG_EMBEDDINGS', false),  // Может быть бол ьшой объём
    ],

    // ========== FRAUD DETECTION ==========

    'fraud' => [
        'enabled' => true,
        'check_on_explicit_update' => true,
        'check_on_purchase' => true,
        'min_interactions_for_fraud_check' => 3,
    ],
];
