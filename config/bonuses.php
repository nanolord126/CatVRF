<?php

declare(strict_types=1);

/**
 * CatVRF 2026 — Bonus System Configuration.
 *
 * Defines bonus accrual rules, tiers, expiration policies,
 * and per-vertical bonus multipliers.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Global Bonus Settings
    |--------------------------------------------------------------------------
    */
    'enabled' => (bool) env('BONUSES_ENABLED', true),

    'currency' => 'bonus_rub',

    'max_bonus_balance' => 1_000_000, // Maximum bonus balance per user

    'expiry_days' => 365, // Bonuses expire after 1 year

    'min_payout_amount' => 100, // Minimum amount for B2B bonus withdrawal

    /*
    |--------------------------------------------------------------------------
    | Accrual Rules
    |--------------------------------------------------------------------------
    | Each rule defines when and how bonuses are awarded.
    */
    'rules' => [
        'referral' => [
            'enabled' => true,
            'referrer_amount' => 500,
            'referee_amount' => 300,
            'max_referrals_per_user' => 50,
            'cooldown_hours' => 24,
        ],

        'first_purchase' => [
            'enabled' => true,
            'amount' => 200,
            'min_order_amount' => 1000,
        ],

        'turnover' => [
            'enabled' => true,
            'tiers' => [
                ['min_turnover' => 10_000, 'percentage' => 1.0],
                ['min_turnover' => 50_000, 'percentage' => 2.0],
                ['min_turnover' => 100_000, 'percentage' => 3.0],
                ['min_turnover' => 500_000, 'percentage' => 5.0],
            ],
        ],

        'loyalty' => [
            'enabled' => true,
            'repeat_purchase_percentage' => 1.5,
            'streak_multiplier' => 0.5, // +0.5% per consecutive month
            'max_streak_multiplier' => 5.0,
        ],

        'promo' => [
            'enabled' => true,
            'max_per_user_per_day' => 3,
        ],

        'ai_constructor_usage' => [
            'enabled' => true,
            'amount_per_use' => 50,
            'max_per_day' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | B2C vs B2B Differences
    |--------------------------------------------------------------------------
    */
    'b2c' => [
        'can_withdraw' => false,
        'max_spend_percentage' => 30, // Max 30% of order can be paid with bonuses
    ],

    'b2b' => [
        'can_withdraw' => true,
        'withdrawal_commission' => 5.0, // 5% commission on B2B withdrawal
        'max_spend_percentage' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Per-Vertical Multipliers
    |--------------------------------------------------------------------------
    | Multiplier applied to all bonus accruals in a given vertical.
    */
    'vertical_multipliers' => [
        'beauty' => 1.2,
        'fashion' => 1.1,
        'food' => 1.0,
        'furniture' => 1.3,
        'hotels' => 1.5,
        'travel' => 1.5,
        'fitness' => 1.1,
        'medical' => 0.5,
        'pharmacy' => 0.5,
        'electronics' => 1.0,
        'auto' => 1.2,
        'real_estate' => 2.0,
        'default' => 1.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup & Jobs
    |--------------------------------------------------------------------------
    */
    'cleanup' => [
        'expired_check_interval' => 'daily', // How often CleanupExpiredBonusesJob runs
        'batch_size' => 1000,
    ],
];
