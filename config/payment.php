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
];
