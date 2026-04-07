<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Commission Settings
    |--------------------------------------------------------------------------
    |
    | This file is for storing the default commission settings for the application.
    | These values are used when a specific rule for a tenant or vertical
    | is not found.
    |
    */

    'default_rule' => [
        // The default commission rate in basis points (e.g., 1400 for 14%).
        'commission_rate' => env('DEFAULT_COMMISSION_RATE', 1400),
    ],

    'verticals' => [
        'auto' => [
            'commission_rate' => env('AUTO_COMMISSION_RATE', 1500),
        ],
        'beauty' => [
            'commission_rate' => env('BEAUTY_COMMISSION_RATE', 1400),
        ],
        'food' => [
            'commission_rate' => env('FOOD_COMMISSION_RATE', 1400),
        ],
        // Add other verticals here
    ],

    // Settings for integration with the Finances service
    'finances_integration' => [
        'enabled' => env('FINANCES_INTEGRATION_ENABLED', true),
        'endpoint' => env('FINANCES_SERVICE_ENDPOINT'),
        'secret' => env('FINANCES_SERVICE_SECRET'),
    ],
];
