<?php

declare(strict_types=1);

return [
    /*
     * Redis Cache Configuration
     */
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => env('CACHE_PREFIX', 'laravel_cache_'),
    ],

    /*
     * Cache TTL Settings (in seconds)
     */
    'ttl' => [
        'b2b_mode' => (int)env('CACHE_B2B_MODE_TTL', 3600), // 1 hour
        'response' => (int)env('CACHE_RESPONSE_TTL', 600), // 10 minutes
        'user_taste' => (int)env('CACHE_USER_TASTE_TTL', 1800), // 30 minutes
        'popular_products' => (int)env('CACHE_POPULAR_PRODUCTS_TTL', 14400), // 4 hours
        'master_availability' => (int)env('CACHE_MASTER_AVAILABILITY_TTL', 7200), // 2 hours
        'vertical_stats' => (int)env('CACHE_VERTICAL_STATS_TTL', 28800), // 8 hours
        'ai_constructor' => (int)env('CACHE_AI_CONSTRUCTOR_TTL', 43200), // 12 hours
    ],

    /*
     * Cache Tags Configuration
     */
    'tags' => [
        'enabled' => env('CACHE_TAGS_ENABLED', true),
        'prefix' => env('CACHE_TAG_PREFIX', 'tag_'),
    ],

    /*
     * Queue Cache Warming
     */
    'warming' => [
        'enabled' => env('CACHE_WARMING_ENABLED', true),
        'queue' => env('CACHE_WARMING_QUEUE', 'cache-warm'),
        'jobs' => [
            'user_taste' => env('CACHE_WARM_USER_TASTE', true),
            'ai_constructor' => env('CACHE_WARM_AI_CONSTRUCTOR', true),
            'popular_products' => env('CACHE_WARM_POPULAR_PRODUCTS', true),
            'master_availability' => env('CACHE_WARM_MASTER_AVAILABILITY', true),
            'vertical_stats' => env('CACHE_WARM_VERTICAL_STATS', true),
        ],
    ],

    /*
     * Cache Invalidation
     */
    'invalidation' => [
        'enabled' => env('CACHE_INVALIDATION_ENABLED', true),
        'strategies' => [
            'tags' => env('CACHE_INVALIDATION_TAGS', true),
            'keys' => env('CACHE_INVALIDATION_KEYS', false),
            'flush' => env('CACHE_INVALIDATION_FLUSH', false),
        ],
    ],
];
