<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Fraud Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for fraud detection thresholds and settings.
    |
    */

    'fraud' => [
        // Amount threshold for rule-based fraud check (in kopecks)
        // Default: 5,000,000 kopecks = 50,000 RUB
        'amount_threshold' => env('PAYMENT_FRAUD_AMOUNT_THRESHOLD', 5000000),

        // Enable ML-based fraud detection
        'ml_enabled' => env('PAYMENT_FRAUD_ML_ENABLED', true),

        // Fraud block threshold (0.0 - 1.0)
        'block_threshold' => env('PAYMENT_FRAUD_BLOCK_THRESHOLD', 0.85),
    ],

    /*
    |--------------------------------------------------------------------------
    | Idempotency Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment idempotency keys.
    |
    */

    'idempotency' => [
        // TTL for idempotency keys in seconds
        // Default: 86400 seconds = 24 hours
        'ttl' => env('PAYMENT_IDEMPOTENCY_TTL', 86400),

        // Redis key prefix
        'key_prefix' => env('PAYMENT_IDEMPOTENCY_KEY_PREFIX', 'payment:idempotency:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment gateway communication.
    |
    */

    'gateway' => [
        // Default timeout for gateway calls in seconds
        'timeout' => env('PAYMENT_GATEWAY_TIMEOUT', 5),

        // Maximum retry attempts
        'max_retries' => env('PAYMENT_GATEWAY_MAX_RETRIES', 3),

        // Circuit breaker configuration
        'circuit_breaker' => [
            // Number of failures before opening circuit
            'threshold' => env('PAYMENT_CIRCUIT_BREAKER_THRESHOLD', 5),

            // Time in seconds before circuit can close
            'timeout' => env('PAYMENT_CIRCUIT_BREAKER_TIMEOUT', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flags for gradual rollout of new payment engine.
    |
    */

    'features' => [
        // Enable new PaymentEngineService
        'new_engine_enabled' => env('PAYMENT_NEW_ENGINE_ENABLED', false),

        // Enable async fraud detection
        'async_fraud_enabled' => env('PAYMENT_ASYNC_FRAUD_ENABLED', true),

        // Enable circuit breaker
        'circuit_breaker_enabled' => env('PAYMENT_CIRCUIT_BREAKER_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue names for payment-related jobs.
    |
    */

    'queues' => [
        'fraud_check' => env('PAYMENT_FRAUD_CHECK_QUEUE', 'payment-fraud-check'),
        'reconciliation' => env('PAYMENT_RECONCILIATION_QUEUE', 'payment-reconciliation'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reconciliation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment reconciliation with gateways.
    |
    */

    'reconciliation' => [
        // Enable daily reconciliation
        'enabled' => env('PAYMENT_RECONCILIATION_ENABLED', false),

        // Time for daily reconciliation (cron format)
        'schedule' => env('PAYMENT_RECONCILIATION_SCHEDULE', '0 2 * * *'),

        // Lookback period in days
        'lookback_days' => env('PAYMENT_RECONCILIATION_LOOKBACK_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment gateway webhooks.
    |
    */

    'webhook' => [
        // Enable HMAC signature verification
        'verify_hmac' => env('PAYMENT_WEBHOOK_VERIFY_HMAC', true),

        // Replay protection window in seconds
        'replay_protection_window' => env('PAYMENT_WEBHOOK_REPLAY_WINDOW', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for specific payment gateway providers.
    |
    */

    'tinkoff' => [
        'api_url' => env('TINKOFF_API_URL', 'https://securepay.tinkoff.ru/v2'),
        'terminal_key' => env('TINKOFF_TERMINAL_KEY'),
        'secret_key' => env('TINKOFF_SECRET_KEY'),
        'timeout' => env('TINKOFF_TIMEOUT', 30),
    ],

    'tochka' => [
        'api_url' => env('TOCHKA_API_URL', 'https://enter.tochka.com/api'),
        'client_id' => env('TOCHKA_CLIENT_ID'),
        'client_secret' => env('TOCHKA_CLIENT_SECRET'),
        'payer_account' => env('TOCHKA_PAYER_ACCOUNT'),
        'timeout' => env('TOCHKA_TIMEOUT', 30),
    ],

    'sber' => [
        'api_url' => env('SBER_API_URL', 'https://securepayments.sberbank.ru/'),
        'terminal_key' => env('SBER_TERMINAL_KEY'),
        'secret_key' => env('SBER_SECRET_KEY'),
        'login' => env('SBER_LOGIN'),
        'password' => env('SBER_PASSWORD'),
        'timeout' => env('SBER_TIMEOUT', 30),
    ],

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'tinkoff'),

    /*
    |--------------------------------------------------------------------------
    | Smart Routing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for intelligent gateway selection.
    |
    */
    'smart_routing' => [
        'enabled' => env('PAYMENT_SMART_ROUTING_ENABLED', true),
        'metrics_ttl' => env('PAYMENT_SMART_ROUTING_METRICS_TTL', 86400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Escrow Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for escrow hold operations.
    |
    */
    'escrow' => [
        'auto_release_enabled' => env('PAYMENT_ESCROW_AUTO_RELEASE', true),
        'default_hold_days' => env('PAYMENT_ESCROW_DEFAULT_HOLD_DAYS', 7),
        'max_hold_days' => env('PAYMENT_ESCROW_MAX_HOLD_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recurring Payments Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for subscription billing.
    |
    */
    'recurring' => [
        'max_retry_attempts' => env('PAYMENT_RECURRING_MAX_RETRIES', 3),
        'retry_delay_hours' => env('PAYMENT_RECURRING_RETRY_DELAY', 24),
        'grace_period_days' => env('PAYMENT_RECURRING_GRACE_PERIOD', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbox Pattern Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for reliable webhook delivery.
    |
    */
    'outbox' => [
        'max_retry_attempts' => env('PAYMENT_OUTBOX_MAX_RETRIES', 5),
        'cleanup_days' => env('PAYMENT_OUTBOX_CLEANUP_DAYS', 30),
        'batch_size' => env('PAYMENT_OUTBOX_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Commission Configuration
    |--------------------------------------------------------------------------
    |
    | Default commission configuration for split payments.
    |
    */
    'commission' => [
        'platform_rate' => env('PAYMENT_PLATFORM_COMMISSION', 0.05),
        'min_commission' => env('PAYMENT_MIN_COMMISSION', 100),
    ],
];
