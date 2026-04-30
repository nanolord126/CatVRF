<?php

declare(strict_types=1);

return [
    'rules' => [
        'referral' => [
            'max_amount' => 50000, // 500.00 USD
            'turnover_threshold' => 1000000, // 10,000.00 USD
        ],
        'turnover' => [
            'max_amount' => 200000, // 2,000.00 USD
        ],
        'promo' => [
            'max_amount' => 10000, // 100.00 USD
        ],
        'loyalty' => [
            'max_amount' => 5000, // 50.00 USD
        ],
        'manual' => [
            'max_amount' => 1000000, // 10,000.00 USD
        ],
    ],

    'loyalty_levels' => [
        'bronze' => [
            'points_threshold' => 0,
            'discount_percentage' => 0,
        ],
        'silver' => [
            'points_threshold' => 1000,
            'discount_percentage' => 5,
        ],
        'gold' => [
            'points_threshold' => 5000,
            'discount_percentage' => 10,
        ],
        'platinum' => [
            'points_threshold' => 10000,
            'discount_percentage' => 15,
        ],
    ],
];
