<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Honest Mark (Честный ЗНАК) API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for integration with Russian Honest Mark system
    | for product labeling and certificate management.
    |
    */

    'api_url' => env('HONESTYSIGN_API_URL', 'https://api.crpt.ru'),

    'token' => env('HONESTYSIGN_TOKEN'),

    'timeout' => env('HONESTYSIGN_TIMEOUT', 30),

    'enabled' => env('HONESTYSIGN_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache duration for mark and certificate validation results.
    |
    */

    'cache_ttl' => env('HONESTYSIGN_CACHE_TTL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Number of retries and delay between retries for API calls.
    |
    */

    'max_retries' => env('HONESTYSIGN_MAX_RETRIES', 3),

    'retry_delay' => env('HONESTYSIGN_RETRY_DELAY', 1000), // milliseconds

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Webhook endpoint for receiving updates from Honest Mark.
    |
    */

    'webhook_secret' => env('HONESTYSIGN_WEBHOOK_SECRET'),

    'webhook_enabled' => env('HONESTYSIGN_WEBHOOK_ENABLED', false),
];
