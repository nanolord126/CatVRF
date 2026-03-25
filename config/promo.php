<?php declare(strict_types=1);

return [
    // Cache configuration
    'cache' => [
        'ttl_dynamic' => 300, // 5 minutes
        'ttl_stable' => 86400, // 1 day
        'prefix' => 'promo:active:',
    ],

    // Promo types
    'types' => [
        'discount_percent' => ['min' => 5, 'max' => 50],
        'fixed_amount' => ['min' => 100, 'max' => 100000],
        'bundle' => ['items_min' => 2, 'items_max' => 10],
        'buy_x_get_y' => ['enabled' => true],
        'gift_card' => ['margin_percent' => 3],
        'referral_bonus' => ['amount' => 100000],
        'turnover_bonus' => ['threshold' => 5000000, 'amount' => 200000],
    ],

    // Rate limiting
    'rate_limiting' => [
        'enabled' => true,
        'attempts_per_minute' => 50,
        'invalid_code_attempts' => 5,
    ],

    // Monitoring
    'monitoring' => [
        'log_channel' => 'promo',
        'alert_budget_threshold' => 0.90,
        'alert_fraud_threshold' => 0.005,
    ],

    // Quality metrics
    'quality' => [
        'target_ctr' => 0.18,
        'target_roi' => 1.50,
        'target_fraud_rate' => 0.005,
    ],
];
