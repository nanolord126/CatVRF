<?php declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Flight Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for external flight search API integration.
    | Supports multiple providers: Amadeus, Sabre, Skyscanner.
    |
    */

    'default_provider' => env('FLIGHT_SEARCH_PROVIDER', 'amadeus'),

    /*
    |--------------------------------------------------------------------------
    | Amadeus API Configuration
    |--------------------------------------------------------------------------
    |
    | API credentials for Amadeus flight search.
    | Get credentials at: https://developers.amadeus.com/
    |
    */
    'amadeus' => [
        'api_key' => env('AMADEUS_API_KEY'),
        'api_secret' => env('AMADEUS_API_SECRET'),
        'base_url' => env('AMADEUS_BASE_URL', 'https://test.api.amadeus.com'),
        'timeout' => env('AMADEUS_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sabre API Configuration
    |--------------------------------------------------------------------------
    |
    | API credentials for Sabre flight search.
    | Get credentials at: https://developer.sabre.com/
    |
    */
    'sabre' => [
        'api_key' => env('SABRE_API_KEY'),
        'api_secret' => env('SABRE_API_SECRET'),
        'base_url' => env('SABRE_BASE_URL', 'https://api.test.sabre.com'),
        'timeout' => env('SABRE_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Skyscanner API Configuration
    |--------------------------------------------------------------------------
    |
    | API credentials for Skyscanner flight search.
    | Get credentials at: https://partners.skyscanner.net/
    |
    */
    'skyscanner' => [
        'api_key' => env('SKYSCANNER_API_KEY'),
        'base_url' => env('SKYSCANNER_BASE_URL', 'https://partners.api.skyscanner.net'),
        'timeout' => env('SKYSCANNER_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for flight search results to reduce API calls.
    |
    */
    'cache' => [
        'enabled' => env('FLIGHT_SEARCH_CACHE_ENABLED', true),
        'ttl' => env('FLIGHT_SEARCH_CACHE_TTL', 1800), // 30 minutes in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for fallback behavior when external APIs fail.
    |
    */
    'fallback' => [
        'enabled' => env('FLIGHT_SEARCH_FALLBACK_ENABLED', true),
        'max_retries' => env('FLIGHT_SEARCH_MAX_RETRIES', 3),
        'retry_delay' => env('FLIGHT_SEARCH_RETRY_DELAY', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings for API calls.
    |
    */
    'rate_limit' => [
        'enabled' => env('FLIGHT_SEARCH_RATE_LIMIT_ENABLED', true),
        'max_requests' => env('FLIGHT_SEARCH_MAX_REQUESTS', 100),
        'per_minutes' => env('FLIGHT_SEARCH_PER_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of supported currencies for flight search.
    |
    */
    'supported_currencies' => [
        'RUB', 'USD', 'EUR', 'GBP', 'JPY', 'CNY', 'INR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Classes
    |--------------------------------------------------------------------------
    |
    | List of supported cabin classes.
    |
    */
    'supported_classes' => [
        'economy',
        'business',
        'first',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation rules for flight search parameters.
    |
    */
    'validation' => [
        'max_passengers' => 9,
        'max_date_range_days' => 365,
        'min_advance_booking_days' => 0,
        'max_advance_booking_days' => 360,
    ],
];
