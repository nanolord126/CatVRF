<?php declare(strict_types=1);

return [
    // ML Model configuration
    'ml' => [
        'fraud' => [
            'enabled' => true,
            'model_path' => storage_path('models/fraud'),
            'recalculate_interval' => 'daily',
            'recalculate_time' => '03:00',
            'min_accuracy' => 0.92,
            'min_precision' => 0.85,
            'min_recall' => 0.70,
        ],
    ],

    // Fraud thresholds by operation type
    'thresholds' => [
        'payment_init' => 0.75,
        'card_bind' => 0.80,
        'payout' => 0.85,
        'rating_submit' => 0.70,
        'referral_claim' => 0.65,
        'promo_apply' => 0.60,
        'order_create' => 0.65,
    ],

    // Fallback rules when ML is unavailable
    'fallback_rules' => [
        'max_operations_per_minute' => 5,
        'max_operations_per_hour' => 100,
        'max_amount_per_day' => 1000000, // 10,000 rubles
        'max_card_bind_attempts_per_hour' => 3,
        'device_change_threshold' => 3, // blocks if 3+ devices in 24h
    ],

    // Feature engineering
    'features' => [
        'behavioral' => [
            'operation_count_1m',
            'operation_count_5m',
            'operation_count_15m',
            'operation_count_1h',
            'operation_sum_1d',
            'operation_sum_7d',
            'operation_sum_30d',
            'failed_payments_count_7d',
            'chargebacks_count_30d',
            'account_age_days',
            'successful_payments_count',
        ],
        'geographic' => [
            'geo_distance_from_last',
            'country_change_24h',
            'city_change_24h',
            'timezone_change',
        ],
        'device' => [
            'device_age_days',
            'device_changes_24h',
            'device_changes_7d',
            'browser_agent_hash',
            'ip_reputation_score',
        ],
        'contextual' => [
            'hour_of_day',
            'day_of_week',
            'is_holiday',
            'operation_type_code',
        ],
    ],

    // Monitoring and alerting
    'monitoring' => [
        'alert_threshold' => 100, // alerts if >100 blocks per hour
        'daily_report_time' => '09:00',
        'sentry_enabled' => true,
        'log_channel' => 'fraud_alert',
    ],
];
