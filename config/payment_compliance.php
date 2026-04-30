<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Compliance Configuration (ФЗ-161, ФЗ-115, 54-ФЗ)
    |--------------------------------------------------------------------------
    |
    | Configuration for federal law compliance in the payment vertical.
    | Includes settings for:
    | - ФЗ-161: National Payment System requirements
    | - ФЗ-115: AML/KYC requirements
    | - 54-ФЗ: Fiscalization (KKT) requirements
    |
    */

    'fz161' => [
        /*
        |--------------------------------------------------------------------------
        | ФЗ-161: National Payment System Settings
        |--------------------------------------------------------------------------
        */
        'enabled' => env('FZ161_ENABLED', true),

        // Transaction limits (in kopecks)
        'limits' => [
            'max_amount_per_transaction' => env('FZ161_MAX_AMOUNT', 15_000_000_00), // 15M RUB
            'max_amount_per_day' => env('FZ161_MAX_DAILY_AMOUNT', 50_000_000_00), // 50M RUB
            'max_amount_per_month' => env('FZ161_MAX_MONTHLY_AMOUNT', 500_000_000_00), // 500M RUB
            'max_transactions_per_day' => env('FZ161_MAX_DAILY_TX', 100),
            'max_transactions_per_hour' => env('FZ161_MAX_HOURLY_TX', 10),
        ],

        // Fraud detection settings
        'fraud_detection' => [
            'real_time_check_required' => env('FZ161_REALTIME_FRAUD_CHECK', true),
            'block_suspicious_operations' => env('FZ161_BLOCK_SUSPICIOUS', true),
            'block_duration_hours' => env('FZ161_BLOCK_DURATION', 48), // Up to 2 days
        ],

        // Settlement requirements (ФЗ-161: electronic payment means only to bank accounts)
        'settlement' => [
            'bank_account_only' => env('FZ161_BANK_ACCOUNT_ONLY', true),
            'allowed_settlement_methods' => ['bank_account', 'sbp'], // SBP allowed as per 2024 update
        ],

        // Payment aggregator status
        'aggregator' => [
            'is_aggregator' => env('PAYMENT_IS_AGGREGATOR', true),
            'bank_contract_number' => env('PAYMENT_BANK_CONTRACT_NUMBER'),
            'bank_contract_date' => env('PAYMENT_BANK_CONTRACT_DATE'),
            'authorized_banks' => [
                'tinkoff',
                'tochka',
                'sber',
                'alfa',
                'vtb',
                'gazprombank',
            ],
        ],

        // Monitoring requirements (24/7 for significant systems)
        'monitoring' => [
            'enabled' => env('FZ161_MONITORING_ENABLED', true),
            'alert_on_anomaly' => env('FZ161_ALERT_ANOMALY', true),
            'volume_spike_threshold' => env('FZ161_VOLUME_SPIKE_THRESHOLD', 2.5), // 2.5x normal
            'velocity_check_enabled' => env('FZ161_VELOCITY_CHECK', true),
        ],
    ],

    'fz115' => [
        /*
        |--------------------------------------------------------------------------
        | ФЗ-115: AML/KYC Settings
        |--------------------------------------------------------------------------
        */
        'enabled' => env('FZ115_ENABLED', true),

        // KYC thresholds (in kopecks)
        'kyc' => [
            'simplified_threshold' => env('FZ115_SIMPLIFIED_THRESHOLD', 100_000_00), // 100k RUB
            'full_kyc_threshold' => env('FZ115_FULL_KYC_THRESHOLD', 1_000_000_00), // 1M RUB
            'enhanced_kyc_threshold' => env('FZ115_ENHANCED_THRESHOLD', 5_000_000_00), // 5M RUB
        ],

        // Risk scoring
        'risk_scoring' => [
            'critical_threshold' => env('FZ115_CRITICAL_RISK', 0.85),
            'high_threshold' => env('FZ115_HIGH_RISK', 0.65),
            'medium_threshold' => env('FZ115_MEDIUM_RISK', 0.40),
        ],

        // Transaction monitoring
        'monitoring' => [
            'velocity_24h_limit' => env('FZ115_VELOCITY_24H', 20),
            'velocity_7d_limit' => env('FZ115_VELOCITY_7D', 100),
            'geo_check_enabled' => env('FZ115_GEO_CHECK', true),
            'profile_check_enabled' => env('FZ115_PROFILE_CHECK', true),
            'device_check_enabled' => env('FZ115_DEVICE_CHECK', true),
            'ip_check_enabled' => env('FZ115_IP_CHECK', true),
        ],

        // Data retention (5 years per ФЗ-115)
        'retention' => [
            'aml_checks_years' => env('FZ115_RETENTION_YEARS', 5),
            'kyc_records_years' => env('FZ115_KYC_RETENTION_YEARS', 5),
        ],

        // Rosfinmonitoring reporting
        'rosfinmonitoring' => [
            'enabled' => env('FZ115_ROSFIN_ENABLED', false),
            'api_url' => env('FZ115_ROSFIN_API_URL'),
            'api_key' => env('FZ115_ROSFIN_API_KEY'),
            'organization_inn' => env('FZ115_ORGANIZATION_INN'),
            'report_threshold_amount' => env('FZ115_REPORT_THRESHOLD', 100_000_00), // 1M RUB
            'report_critical_risk' => env('FZ115_REPORT_CRITICAL_RISK', true),
        ],

        // Block/refuse service
        'blocking' => [
            'auto_block_critical' => env('FZ115_AUTO_BLOCK_CRITICAL', true),
            'auto_block_high_risk' => env('FZ115_AUTO_BLOCK_HIGH', false),
            'require_manual_review_high' => env('FZ115_MANUAL_REVIEW_HIGH', true),
        ],
    ],

    'fz54' => [
        /*
        |--------------------------------------------------------------------------
        | 54-ФЗ: Fiscalization Settings
        |--------------------------------------------------------------------------
        */
        'enabled' => env('FZ54_ENABLED', true),

        // Agent information
        'agent' => [
            'type' => env('FZ54_AGENT_TYPE', 'payment_agent'), // payment_agent, bank_agent
            'name' => env('FZ54_AGENT_NAME', 'CatVRF Marketplace'),
            'inn' => env('FZ54_AGENT_INN'),
            'payment_address' => env('FZ54_PAYMENT_ADDRESS', 'https://catvrf.ru'),
            'phone' => env('FZ54_AGENT_PHONE'),
        ],

        // VAT rates
        'vat' => [
            'default_rate' => env('FZ54_DEFAULT_VAT', 20), // 20% VAT
            'rates' => [
                0 => 'vat0',
                10 => 'vat10',
                20 => 'vat20',
                110 => 'vat110',
                120 => 'vat120',
            ],
        ],

        // Receipt settings
        'receipts' => [
            'auto_send_prepayment' => env('FZ54_AUTO_SEND_PREPAYMENT', true),
            'auto_send_full_payment' => env('FZ54_AUTO_SEND_FULL_PAYMENT', true),
            'auto_send_refund' => env('FZ54_AUTO_SEND_REFUND', true),
            'retry_failed' => env('FZ54_RETRY_FAILED', true),
            'max_retries' => env('FZ54_MAX_RETRIES', 3),
            'retry_delay_seconds' => env('FZ54_RETRY_DELAY', 60),
        ],

        // Data retention (5 years per 54-ФЗ)
        'retention' => [
            'receipts_years' => env('FZ54_RETENTION_YEARS', 5),
        ],

        // B2B settlements (no fiscalization required)
        'b2b' => [
            'skip_fiscalization' => env('FZ54_B2B_SKIP_FISCAL', true),
            'seller_inn_required' => env('FZ54_SELLER_INN_REQUIRED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | General Compliance Settings
    |--------------------------------------------------------------------------
    */
    'general' => [
        'audit_logging' => [
            'enabled' => env('COMPLIANCE_AUDIT_ENABLED', true),
            'log_all_checks' => env('COMPLIANCE_LOG_ALL_CHECKS', true),
            'log_sensitive_data' => env('COMPLIANCE_LOG_SENSITIVE', false),
        ],

        'cache' => [
            'enabled' => env('COMPLIANCE_CACHE_ENABLED', true),
            'ttl_seconds' => env('COMPLIANCE_CACHE_TTL', 300),
        ],

        'bigdata_integration' => [
            'enabled' => env('COMPLIANCE_BIGDATA_ENABLED', true),
            'track_all_checks' => env('COMPLIANCE_TRACK_CHECKS', true),
            'track_violations' => env('COMPLIANCE_TRACK_VIOLATIONS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Tasks
    |--------------------------------------------------------------------------
    */
    'scheduled_tasks' => [
        'aml_cleanup' => [
            'enabled' => env('COMPLIANCE_AML_CLEANUP_ENABLED', true),
            'schedule' => env('COMPLIANCE_AML_CLEANUP_SCHEDULE', '0 2 * * *'), // Daily at 2 AM
        ],

        'fiscal_cleanup' => [
            'enabled' => env('COMPLIANCE_FISCAL_CLEANUP_ENABLED', true),
            'schedule' => env('COMPLIANCE_FISCAL_CLEANUP_SCHEDULE', '0 3 * * *'), // Daily at 3 AM
        ],

        'rosfinmonitoring_report' => [
            'enabled' => env('COMPLIANCE_ROSFIN_REPORT_ENABLED', false),
            'schedule' => env('COMPLIANCE_ROSFIN_REPORT_SCHEDULE', '0 4 * * *'), // Daily at 4 AM
        ],

        'pending_receipts' => [
            'enabled' => env('COMPLIANCE_PENDING_RECEIPTS_ENABLED', true),
            'schedule' => env('COMPLIANCE_PENDING_RECEIPTS_SCHEDULE', '*/5 * * * *'), // Every 5 minutes
        ],
    ],
];
