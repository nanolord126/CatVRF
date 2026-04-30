<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AML/CTF Configuration - ФЗ-115 Compliance
    |--------------------------------------------------------------------------
    |
    | Configuration for Anti-Money Laundering and Counter-Terrorism Financing
    | compliance per Russian Federal Law No. 115-FZ (ФЗ-115).
    |
    */

    'enabled' => env('AML_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Risk Thresholds (ФЗ-115)
    |--------------------------------------------------------------------------
    */
    'risk_thresholds' => [
        'block' => 85,                    // Critical risk - automatic block
        'enhanced_kyc' => 70,            // High risk - requires Enhanced KYC
        'standard_kyc' => 40,            // Medium risk - requires Standard KYC
        'low_risk' => 20,                // Low risk threshold
    ],

    /*
    |--------------------------------------------------------------------------
    | Amount Thresholds (ФЗ-115)
    |--------------------------------------------------------------------------
    */
    'amount_thresholds' => [
        'simplified' => 15000,           // 15,000 RUB - simplified KYC
        'standard' => 60000,             // 60,000 RUB - standard KYC
        'enhanced' => 100000,            // 100,000 RUB - enhanced KYC (mandatory reporting)
        'reporting' => 100000,           // 100,000 RUB - Rosfinmonitoring reporting threshold
    ],

    /*
    |--------------------------------------------------------------------------
    | KYC Configuration
    |--------------------------------------------------------------------------
    */
    'kyc' => [
        'retention_years' => 5,          // ФЗ-115: 5-year data retention
        'document_max_size_mb' => 10,
        'allowed_document_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'auto_upgrade_enabled' => true,
        'verification_timeout_hours' => 72,
    ],

    /*
    |--------------------------------------------------------------------------
    | Velocity Controls
    |--------------------------------------------------------------------------
    */
    'velocity' => [
        'enabled' => true,
        'window_hours' => 24,
        'max_operations' => 5,
        'block_on_exceed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl_minutes' => 15,
        'key_prefix' => 'aml:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rosfinmonitoring Integration (ФЗ-115)
    |--------------------------------------------------------------------------
    */
    'rosfinmonitoring' => [
        'enabled' => env('ROSFINMONITORING_ENABLED', false),
        'api_key' => env('ROSFINMONITORING_API_KEY'),
        'api_url' => env('ROSFINMONITORING_API_URL', 'https://api.rosfinmonitoring.ru'),
        'timeout' => env('ROSFINMONITORING_TIMEOUT', 30),
        'reporting_deadline_days' => 3,  // 3 business days per ФЗ-115
        'auto_report_enabled' => env('AML_AUTO_REPORT', false),
        'test_mode' => env('ROSFINMONITORING_TEST_MODE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sanctions Screening
    |--------------------------------------------------------------------------
    */
    'sanctions' => [
        'enabled' => env('SANCTIONS_SCREENING_ENABLED', true),
        'providers' => [
            'ofac' => [
                'enabled' => env('OFAC_ENABLED', false),
                'api_key' => env('OFAC_API_KEY'),
                'api_url' => env('OFAC_API_URL', 'https://api.trade.gov.gov/v1/sanctions'),
                'timeout' => 30,
            ],
            'eu_sanctions' => [
                'enabled' => env('EU_SANCTIONS_ENABLED', false),
                'api_key' => env('EU_SANCTIONS_API_KEY'),
                'api_url' => env('EU_SANCTIONS_API_URL'),
                'timeout' => 30,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Monitoring Patterns
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => true,
        'patterns' => [
            'structuring' => true,         // Breaking large transactions into smaller ones
            'round_amounts' => true,       // Round numbers (e.g., 100,000 RUB)
            'rapid_succession' => true,    // Multiple transactions in short time
            'cross_border' => true,        // International transactions
            'high_velocity' => true,       // High frequency transactions
            'unusual_time' => true,        // Transactions at unusual hours
            'geo_mismatch' => true,        // Geographic inconsistency
        ],
        'thresholds' => [
            'structuring_count' => 10,
            'structuring_hours' => 24,
            'rapid_succession_minutes' => 5,
            'unusual_time_start' => 2,     // 2 AM
            'unusual_time_end' => 5,       // 5 AM
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Configuration
    |--------------------------------------------------------------------------
    */
    'reporting' => [
        'enabled' => true,
        'sar_auto_submit' => env('AML_SAR_AUTO_SUBMIT', false),
        'sar_review_required' => true,
        'reporting_authority' => env('AML_REPORTING_AUTHORITY', 'rosfinmonitoring'),
        'notification_channels' => [
            'telegram' => env('AML_TELEGRAM_ENABLED', false),
            'slack' => env('AML_SLACK_ENABLED', false),
            'email' => env('AML_EMAIL_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Freeze Configuration
    |--------------------------------------------------------------------------
    */
    'wallet_freeze' => [
        'enabled' => true,
        'auto_freeze_critical' => true,   // Auto-freeze on critical risk
        'auto_freeze_high' => false,      // Manual review for high risk
        'manual_approval_unfreeze' => true,
        'freeze_reason' => 'aml_investigation',
        'freeze_duration_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Four-Eyes Approval (Dual Control)
    |--------------------------------------------------------------------------
    */
    'four_eyes_approval' => [
        'enabled' => env('AML_FOUR_EYES_ENABLED', true),
        'threshold_rub' => 100000,
        'require_different_users' => true,
        'approval_window_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Factor Weights
    |--------------------------------------------------------------------------
    */
    'risk_factors' => [
        'amount_over_100k' => 40,
        'velocity_24h' => 35,
        'geo_mismatch' => 25,
        'clv_low' => 20,
        'new_account' => 20,
        'high_risk_category' => 30,
        'unusual_time' => 15,
        'device_fingerprint' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | High-Risk Categories
    |--------------------------------------------------------------------------
    */
    'high_risk_categories' => [
        'crypto',
        'gambling',
        'cash_out',
        'p2p_transfer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'suspicious_activity' => [
            'enabled' => true,
            'channels' => ['email', 'slack'],
        ],
        'kyc_required' => [
            'enabled' => true,
            'channels' => ['email', 'in_app'],
        ],
        'operation_blocked' => [
            'enabled' => true,
            'channels' => ['email', 'in_app'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => true,
        'log_all_checks' => true,
        'log_risk_factors' => true,
        'log_kyc_changes' => true,
        'log_suspicious_operations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | BigData Integration
    |--------------------------------------------------------------------------
    */
    'bigdata' => [
        'enabled' => env('AML_BIGDATA_ENABLED', true),
        'track_all_checks' => true,
        'track_risk_scores' => true,
        'track_kyc_levels' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fraud Control Integration
    |--------------------------------------------------------------------------
    */
    'fraud_control' => [
        'enabled' => env('AML_FRAUD_CONTROL_ENABLED', true),
        'block_on_fraud' => true,
        'continue_on_failure' => true,  // Continue AML check if fraud check fails
    ],
];
