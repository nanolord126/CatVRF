<?php declare(strict_types=1);

return [
    // Platform configuration
    'name' => 'CatVRF Marketplace Platform 2026',
    'version' => '1.0.0',
    'environment' => env('APP_ENV', 'local'),
    'debug' => env('APP_DEBUG', false),

    // Commission configuration
    'commissions' => [
        'default' => 0.14, // 14%
        'by_vertical' => [
            'beauty' => 0.14,
            'food' => 0.14,
            'hotels' => 0.14,
            'auto' => 0.15,
            'tickets' => 0.17,
            'courses' => 0.14,
            'medical' => 0.14,
            'pet' => 0.14,
        ],
        'migration_discounts' => [
            'dikidi' => ['discount' => 0.10, 'duration_months' => 4],
            'flowwow' => ['discount' => 0.10, 'duration_months' => 4],
            'yandex_afisha' => ['discount' => 0.02, 'duration_months' => 24],
            'yandex_travel' => ['discount' => 0.02, 'duration_months' => 24],
        ],
    ],

    // Payout configuration
    'payouts' => [
        'default_schedule_days' => 7,
        'by_vertical' => [
            'hotels' => 4, // 4 days after check-out
            'food' => 3,
            'beauty' => 3,
            'auto' => 3,
            'courses' => 7,
            'medical' => 7,
        ],
        'min_amount' => 50000, // 500 rubles
        'max_daily_volume' => 100000000, // 1M rubles
    ],

    // Wallet configuration
    'wallet' => [
        'initial_balance' => 0,
        'currency' => 'RUB',
        'precision' => 2,
        'min_transaction' => 100, // 1 kopeck
        'max_transaction' => 10000000000, // 100M rubles
        'hold_duration_hours' => 72,
    ],

    // Payment configuration
    'payment' => [
        'providers' => ['tinkoff', 'tochka', 'sber'],
        'default_provider' => 'tinkoff',
        'idempotency_window_hours' => 24,
        'capture_delay_seconds' => 3600, // 1 hour
        'refund_window_days' => 180,
    ],

    // Rate limiting
    'rate_limiting' => [
        'enabled' => true,
        'storage' => 'redis',
        'default_limit' => 1000,
        'window_seconds' => 60,
        'by_endpoint' => [
            'payment.init' => ['limit' => 10, 'window' => 60],
            'promo.apply' => ['limit' => 50, 'window' => 60],
            'recommendation' => ['limit' => 100, 'window' => 60],
            'search' => ['limit' => 1000, 'window' => 3600],
        ],
    ],

    // Fraud detection
    'fraud' => [
        'enabled' => true,
        'ml_enabled' => true,
        'fallback_rules_enabled' => true,
        'block_threshold' => 0.75,
        'review_threshold' => 0.60,
    ],

    // Caching
    'caching' => [
        'driver' => env('CACHE_DRIVER', 'redis'),
        'ttl_short' => 300, // 5 minutes
        'ttl_medium' => 3600, // 1 hour
        'ttl_long' => 86400, // 1 day
        'ttl_very_long' => 604800, // 1 week
    ],

    // Queueing
    'queue' => [
        'driver' => env('QUEUE_CONNECTION', 'redis'),
        'default_queue' => 'default',
        'timeout' => 3600,
        'retry_after' => 300,
        'max_attempts' => 3,
    ],

    // Monitoring
    'monitoring' => [
        'sentry' => [
            'enabled' => env('SENTRY_ENABLED', false),
            'dsn' => env('SENTRY_DSN'),
            'trace_sample_rate' => 0.1,
            'profile_sample_rate' => 0.1,
        ],
        'logging' => [
            'channel' => 'stack',
            'audit_channel' => 'audit',
            'fraud_channel' => 'fraud_alert',
        ],
    ],

    // Feature flags
    'features' => [
        'fraud_ml_v2' => env('FEATURE_FRAUD_ML_V2', false),
        'recommendation_model_v2' => env('FEATURE_RECOMMEND_V2', false),
        'demand_forecast_advanced' => env('FEATURE_FORECAST_ADVANCED', false),
        'ab_testing_enabled' => env('AB_TESTING_ENABLED', true),
    ],

    // Database
    'database' => [
        'driver' => env('DB_CONNECTION', 'pgsql'),
        'migrations_path' => 'database/migrations',
        'seed_path' => 'database/seeders',
    ],

    // API
    'api' => [
        'version' => 'v1',
        'prefix' => 'api',
        'pagination' => [
            'per_page' => 20,
            'max_per_page' => 100,
        ],
    ],

    // Security
    'security' => [
        'https_required' => env('APP_ENV') === 'production',
        'hsts_enabled' => true,
        'cors_enabled' => true,
        'api_key_required' => false,
        'webhook_signature_required' => true,
    ],
];
