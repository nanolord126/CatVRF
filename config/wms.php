<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | WMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Warehouse Management System compliance features
    |
    */

    'cold_chain' => [
        'default_min_temp' => env('COLD_CHAIN_MIN_TEMP', 2.0),
        'default_max_temp' => env('COLD_CHAIN_MAX_TEMP', 25.0),
        'default_humidity_min' => env('COLD_CHAIN_HUMIDITY_MIN', 30.0),
        'default_humidity_max' => env('COLD_CHAIN_HUMIDITY_MAX', 75.0),
        'alert_threshold_minutes' => env('COLD_CHAIN_ALERT_THRESHOLD', 15),
    ],

    'egisz' => [
        'api_url' => env('EGISZ_API_URL'),
        'api_key' => env('EGISZ_API_KEY'),
        'certificate_path' => env('EGISZ_CERTIFICATE_PATH'),
        'timeout' => env('EGISZ_TIMEOUT', 30),
    ],

    'onec' => [
        'api_url' => env('ONEC_API_URL'),
        'username' => env('ONEC_USERNAME'),
        'password' => env('ONEC_PASSWORD'),
        'timeout' => env('ONEC_TIMEOUT', 120),
    ],

    'rate_limiting' => [
        'default' => env('WMS_RATE_LIMIT_DEFAULT', 60),
        'stock_movement' => env('WMS_RATE_LIMIT_STOCK_MOVEMENT', 100),
        'batch_operations' => env('WMS_RATE_LIMIT_BATCH_OPERATIONS', 50),
        'reports' => env('WMS_RATE_LIMIT_REPORTS', 20),
        'integration' => env('WMS_RATE_LIMIT_INTEGRATION', 30),
    ],

    'cache' => [
        'default_ttl' => env('WMS_CACHE_DEFAULT_TTL', 3600),
        'stock_ttl' => env('WMS_CACHE_STOCK_TTL', 300),
        'config_ttl' => env('WMS_CACHE_CONFIG_TTL', 7200),
    ],

    'barcode' => [
        'default_type' => env('BARCODE_DEFAULT_TYPE', 'CODE128'),
        'auto_generate' => env('BARCODE_AUTO_GENERATE', true),
    ],

    'compliance' => [
        'fz61_expiry_days_threshold' => env('FZ61_EXPIRY_THRESHOLD', 30),
        'abc_analysis_a_threshold' => 70,
        'abc_analysis_b_threshold' => 90,
        'xyz_x_threshold' => 20,
        'xyz_y_threshold' => 50,
    ],
];
