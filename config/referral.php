<?php declare(strict_types=1);

return [
    // Referral link configuration
    'links' => [
        'code_length' => 8,
        'code_format' => 'alphanumeric_upper',
        'expiration_days' => 365,
    ],

    // Qualification rules
    'qualification' => [
        'thresholds' => [
            'business_turnover' => 5000000, // 50,000 rubles
            'customer_spending' => 1000000, // 10,000 rubles
        ],
        'bonus_amounts' => [
            'business' => 200000, // 2,000 rubles
            'customer' => 100000, // 1,000 rubles
        ],
    ],

    // Migration bonuses
    'migrations' => [
        'dikidi' => ['discount_percent' => 10, 'duration_months' => 4],
        'flowwow' => ['discount_percent' => 10, 'duration_months' => 4],
        'yandex_afisha' => ['discount_percent' => 2, 'duration_months' => 24],
    ],

    // Cache configuration
    'cache' => [
        'ttl' => 3600, // 1 hour
        'prefix' => 'referral:stats:',
    ],

    // Rate limiting
    'rate_limiting' => [
        'enabled' => true,
        'link_generation_per_minute' => 10,
        'fraud_check_threshold' => 10,
    ],

    // Monitoring
    'monitoring' => [
        'log_channel' => 'referral',
        'alert_fraud_threshold' => 0.003,
        'alert_failed_conversion' => 0.50,
    ],

    // Quality metrics
    'quality' => [
        'target_conversion' => 0.25,
        'target_roi' => 3.00,
        'target_fraud_rate' => 0.003,
    ],
];
