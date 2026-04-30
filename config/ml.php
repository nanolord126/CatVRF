<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | ML Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Python ML service integration
    |
    */

    'url' => env('ML_SERVICE_URL', 'http://localhost:8000'),
    'timeout' => env('ML_SERVICE_TIMEOUT', 5),
    'enabled' => env('ML_SERVICE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | A/B Testing
    |--------------------------------------------------------------------------
    |
    | Configuration for A/B testing ML models
    |
    */

    'ab_testing' => [
        'enabled' => env('ML_AB_TESTING_ENABLED', false),
        'percentage' => env('ML_AB_TESTING_PERCENTAGE', 50), // 50% traffic to new model
    ],

    /*
    |--------------------------------------------------------------------------
    | Shadow Mode
    |--------------------------------------------------------------------------
    |
    | Run ML predictions in parallel with heuristics without affecting decisions
    |
    */

    'shadow_mode' => [
        'enabled' => env('ML_SHADOW_MODE_ENABLED', false),
        'log_predictions' => true,
    ],
];
