<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Marketplace Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация модуля маркетплейса
    | Витринная система с агрегацией из вертикалей и алгоритмами ранжирования
    |
    */

    'ranking' => [
        /*
         * Веса факторов ранжирования (должны суммироваться к 1.0)
         */
        'popularity_weight' => env('MARKETPLACE_POPULARITY_WEIGHT', 0.25),
        'conversion_weight' => env('MARKETPLACE_CONVERSION_WEIGHT', 0.20),
        'recency_weight' => env('MARKETPLACE_RECENCY_WEIGHT', 0.15),
        'rating_weight' => env('MARKETPLACE_RATING_WEIGHT', 0.15),
        'price_weight' => env('MARKETPLACE_PRICE_WEIGHT', 0.10),
        'availability_weight' => env('MARKETPLACE_AVAILABILITY_WEIGHT', 0.10),
        'promoted_weight' => env('MARKETPLACE_PROMOTED_WEIGHT', 0.03),
        'ml_weight' => env('MARKETPLACE_ML_WEIGHT', 0.01),
        'personalization_weight' => env('MARKETPLACE_PERSONALIZATION_WEIGHT', 0.01),

        /*
         * Версия алгоритма ранжирования
         */
        'algorithm_version' => env('MARKETPLACE_ALGORITHM_VERSION', '1.0.0'),

        /*
         * Интервал пересчета рейтингов (в минутах)
         */
        'recalculation_interval_minutes' => env('MARKETPLACE_RECALCULATION_INTERVAL', 60),

        /*
         * Включить ML-скоринг (требует ML сервиса)
         */
        'enable_ml' => env('MARKETPLACE_ENABLE_ML', false),

        /*
         * Включить персонализацию (требует user context)
         */
        'enable_personalization' => env('MARKETPLACE_ENABLE_PERSONALIZATION', false),
    ],

    'aggregation' => [
        /*
         * Размер батча для синхронизации
         */
        'batch_size' => env('MARKETPLACE_BATCH_SIZE', 100),

        /*
         * Интервал синхронизации по умолчанию (в минутах)
         */
        'default_sync_interval' => env('MARKETPLACE_SYNC_INTERVAL', 60),

        /*
         * Таймаут синхронизации (в секундах)
         */
        'sync_timeout' => env('MARKETPLACE_SYNC_TIMEOUT', 300),
    ],

    'cache' => [
        /*
         * Включить кэширование витрины
         */
        'enabled' => env('MARKETPLACE_CACHE_ENABLED', true),

        /*
         * TTL кэша (в секундах)
         */
        'ttl' => env('MARKETPLACE_CACHE_TTL', 300),

        /*
         * Префикс ключей кэша
         */
        'prefix' => env('MARKETPLACE_CACHE_PREFIX', 'marketplace:'),
    ],

    'verticals' => [
        /*
         * Список активных вертикалей для агрегации
         */
        'active' => array_filter(
            explode(',', env('MARKETPLACE_ACTIVE_VERTICALS', 'beauty,restaurant'))
        ),

        /*
         * Конфигурация для каждой вертикали
         */
        'beauty' => [
            'enabled' => env('MARKETPLACE_BEAUTY_ENABLED', true),
            'auto_publish' => env('MARKETPLACE_BEAUTY_AUTO_PUBLISH', false),
            'sync_interval' => env('MARKETPLACE_BEAUTY_SYNC_INTERVAL', 60),
        ],

        'restaurant' => [
            'enabled' => env('MARKETPLACE_RESTAURANT_ENABLED', true),
            'auto_publish' => env('MARKETPLACE_RESTAURANT_AUTO_PUBLISH', true),
            'sync_interval' => env('MARKETPLACE_RESTAURANT_SYNC_INTERVAL', 30),
        ],

        // Добавить конфигурации для других вертикалей по мере реализации
        // 'fashion' => [...],
        // 'hotels' => [...],
        // 'fitness' => [...],
    ],

    'recommendations' => [
        /*
         * Лимит рекомендаций по умолчанию
         */
        'default_limit' => env('MARKETPLACE_RECOMMENDATION_LIMIT', 20),

        /*
         * Порог схожести для похожих товаров
         */
        'similarity_threshold' => env('MARKETPLACE_SIMILARITY_THRESHOLD', 0.3),
    ],
];
