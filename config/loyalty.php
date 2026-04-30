<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Loyalty System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the cross-vertical loyalty system that works across
    | restaurants, hotels, and other verticals in CatVRF.
    |
    */

    'defaults' => [
        /*
         * Default vertical type if not specified
         */
        'vertical_type' => env('LOYALTY_DEFAULT_VERTICAL', 'restaurant'),

        /*
         * Default points per currency unit
         */
        'base_points_per_currency' => (float) env('LOYALTY_BASE_POINTS_PER_CURRENCY', 1.0),

        /*
         * Default points to currency conversion rate
         */
        'points_to_currency_rate' => (float) env('LOYALTY_POINTS_TO_CURRENCY_RATE', 0.01),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for loyalty data to improve performance.
    |
    */
    'cache' => [
        /*
         * Cache prefix for loyalty keys
         */
        'prefix' => 'loyalty',

        /*
         * Cache TTL in seconds
         */
        'ttl' => [
            'profile_balance' => 300, // 5 minutes
            'program_rules' => 600,   // 10 minutes
            'available_rewards' => 300, // 5 minutes
            'tier_info' => 3600,      // 1 hour
            'order_calculation' => 300, // 5 minutes
            'kds_display' => 60,      // 1 minute
        ],

        /*
         * Enable caching
         */
        'enabled' => env('LOYALTY_CACHE_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Enrollment
    |--------------------------------------------------------------------------
    |
    | Automatically enroll guests in loyalty programs when they make
    | their first order or booking.
    |
    */
    'auto_enrollment' => [
        'enabled' => env('LOYALTY_AUTO_ENROLLMENT_ENABLED', true),
        'on_first_order' => env('LOYALTY_AUTO_ENROLL_ON_ORDER', true),
        'on_first_booking' => env('LOYALTY_AUTO_ENROLL_ON_BOOKING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tier System
    |--------------------------------------------------------------------------
    |
    | Default tier configuration if not overridden by program settings.
    |
    */
    'tiers' => [
        'enabled' => true,
        'default_tiers' => [
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'min_points' => 0,
                'point_multiplier' => 1.0,
                'discount_percentage' => 0.0,
                'color' => '#CD7F32',
                'sort_order' => 1,
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'min_points' => 1000,
                'point_multiplier' => 1.2,
                'discount_percentage' => 0.05,
                'color' => '#C0C0C0',
                'sort_order' => 2,
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'min_points' => 5000,
                'point_multiplier' => 1.5,
                'discount_percentage' => 0.10,
                'color' => '#FFD700',
                'sort_order' => 3,
            ],
            [
                'name' => 'Platinum',
                'slug' => 'platinum',
                'min_points' => 15000,
                'point_multiplier' => 2.0,
                'discount_percentage' => 0.15,
                'color' => '#E5E4E2',
                'sort_order' => 4,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Point Expiration
    |--------------------------------------------------------------------------
    |
    | Default point expiration settings.
    |
    */
    'expiration' => [
        'enabled' => env('LOYALTY_POINTS_EXPIRE', false),
        'default_days' => (int) env('LOYALTY_POINTS_EXPIRATION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Integration settings for different verticals.
    |
    */
    'integrations' => [
        'restaurant' => [
            'enabled' => true,
            'auto_process_on_order_close' => true,
            'show_in_kds' => true,
            'show_in_pos' => true,
        ],
        'hotel' => [
            'enabled' => true,
            'auto_process_on_checkout' => true,
            'show_in_booking_system' => true,
        ],
        'beauty' => [
            'enabled' => true,
            'auto_process_on_appointment_complete' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Notification settings for loyalty events.
    |
    */
    'notifications' => [
        'points_earned' => [
            'enabled' => env('LOYALTY_NOTIFY_POINTS_EARNED', true),
            'channels' => ['database', 'push'], // database, push, sms, email
        ],
        'tier_upgraded' => [
            'enabled' => env('LOYALTY_NOTIFY_TIER_UPGRADE', true),
            'channels' => ['database', 'push', 'email'],
        ],
        'reward_redeemed' => [
            'enabled' => env('LOYALTY_NOTIFY_REWARD_REDEEMED', true),
            'channels' => ['database'],
        ],
        'birthday_bonus' => [
            'enabled' => env('LOYALTY_NOTIFY_BIRTHDAY_BONUS', true),
            'channels' => ['database', 'push', 'email'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fraud Detection
    |--------------------------------------------------------------------------
    |
    | Basic fraud detection settings for loyalty system.
    |
    */
    'fraud_detection' => [
        'enabled' => env('LOYALTY_FRAUD_DETECTION_ENABLED', true),
        'max_points_per_day' => (float) env('LOYALTY_MAX_POINTS_PER_DAY', 10000),
        'max_redemptions_per_day' => (int) env('LOYALTY_MAX_REDEMPTIONS_PER_DAY', 10),
        'suspicious_activity_threshold' => (int) env('LOYALTY_SUSPICIOUS_THRESHOLD', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Queue settings for async loyalty operations.
    |
    */
    'queue' => [
        'enabled' => env('LOYALTY_QUEUE_ENABLED', true),
        'queue_name' => env('LOYALTY_QUEUE_NAME', 'loyalty'),
        'connection' => env('LOYALTY_QUEUE_CONNECTION', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Mode
    |--------------------------------------------------------------------------
    |
    | Testing mode for development and testing.
    |
    */
    'testing' => [
        'enabled' => env('LOYALTY_TESTING_MODE', false),
        'multiply_points' => (int) env('LOYALTY_TESTING_MULTIPLIER', 1),
    ],
];
