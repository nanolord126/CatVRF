<?php

declare(strict_types=1);

/**
 * Client Data Protection Configuration
 * 
 * CatVRF 2026 Database Security Fortress
 * 
 * Settings for data exfiltration prevention, hunting detection,
 * and client data access controls.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => env('CLIENT_PROTECTION_RATE_LIMIT_ENABLED', true),
        
        // Per-user rate limits
        'per_user' => [
            'max_requests_per_minute' => env('CLIENT_PROTECTION_MAX_REQUESTS_PER_MINUTE', 60),
            'max_requests_per_hour' => env('CLIENT_PROTECTION_MAX_REQUESTS_PER_HOUR', 1000),
        ],
        
        // Per-tenant rate limits
        'per_tenant' => [
            'max_requests_per_minute' => env('CLIENT_PROTECTION_TENANT_MAX_PER_MINUTE', 300),
            'max_requests_per_hour' => env('CLIENT_PROTECTION_TENANT_MAX_PER_HOUR', 5000),
        ],
        
        // Per-IP rate limits
        'per_ip' => [
            'max_requests_per_minute' => env('CLIENT_PROTECTION_IP_MAX_PER_MINUTE', 100),
            'max_requests_per_hour' => env('CLIENT_PROTECTION_IP_MAX_PER_HOUR', 2000),
        ],
        
        // Rate limit window in seconds
        'window_seconds' => env('CLIENT_PROTECTION_RATE_WINDOW_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Result Limits
    |--------------------------------------------------------------------------
    */
    'result_limits' => [
        // Maximum records per query by role
        'customer' => env('CLIENT_PROTECTION_CUSTOMER_LIMIT', 50),
        'staff' => env('CLIENT_PROTECTION_STAFF_LIMIT', 200),
        'super_admin' => env('CLIENT_PROTECTION_SUPER_ADMIN_LIMIT', 1000),
        
        // Maximum records for export
        'export_customer' => env('CLIENT_PROTECTION_EXPORT_CUSTOMER_LIMIT', 50),
        'export_staff' => env('CLIENT_PROTECTION_EXPORT_STAFF_LIMIT', 200),
        'export_super_admin' => env('CLIENT_PROTECTION_EXPORT_SUPER_ADMIN_LIMIT', 1000),
        
        // Require approval for exports larger than this
        'approval_threshold' => env('CLIENT_PROTECTION_APPROVAL_THRESHOLD', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hunting Detection
    |--------------------------------------------------------------------------
    */
    'hunting_detection' => [
        'enabled' => env('HUNTING_DETECTION_ENABLED', true),
        
        // Hunting score threshold (0.0 - 1.0)
        'score_threshold' => env('HUNTING_DETECTION_SCORE_THRESHOLD', 0.7),
        
        // Number of pattern attempts before triggering cooldown
        'pattern_attempt_threshold' => env('HUNTING_DETECTION_PATTERN_THRESHOLD', 3),
        
        // Detection window in minutes
        'detection_window_minutes' => env('HUNTING_DETECTION_WINDOW_MINUTES', 5),
        
        // Cooldown duration in hours when hunting detected
        'cooldown_duration_hours' => env('HUNTING_DETECTION_COOLDOWN_HOURS', 2),
        
        // Score weights
        'weights' => [
            'frequency' => 0.3,
            'pattern' => 0.4,
            'result_size' => 0.2,
            'time_anomaly' => 0.1,
        ],
        
        // Suspicious patterns
        'suspicious_patterns' => [
            'like_without_tenant_id' => true,
            'or_where_without_tenant_id' => true,
            'high_frequency_queries' => true,
            'large_result_sets' => true,
            'unusual_time_access' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Masking
    |--------------------------------------------------------------------------
    */
    'data_masking' => [
        'enabled' => env('DATA_MASKING_ENABLED', true),
        
        // Fields to mask in API responses
        'masked_fields' => [
            'email',
            'phone',
            'inn',
            'first_name',
            'last_name',
            'middle_name',
            'legal_address',
            'actual_address',
        ],
        
        // Masking patterns
        'patterns' => [
            'email' => 'first_char_asterisk_domain',
            'phone' => 'last_4_digits_only',
            'inn' => 'last_4_digits_only',
            'name' => 'first_char_asterisk',
        ],
        
        // Allow unmasked access for super-admins
        'allow_unmasked_for_super_admin' => env('DATA_MASKING_ALLOW_SUPER_ADMIN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Controls
    |--------------------------------------------------------------------------
    */
    'export_controls' => [
        'enabled' => env('EXPORT_CONTROLS_ENABLED', true),
        
        // Allowed export formats
        'allowed_formats' => [
            'csv',
            'xlsx',
            'json',
        ],
        
        // Require authentication for all exports
        'require_auth' => true,
        
        // Require 2FA for exports larger than threshold
        'require_2fa_threshold' => env('EXPORT_2FA_THRESHOLD', 100),
        
        // Require super-admin approval for large exports
        'require_approval_threshold' => env('EXPORT_APPROVAL_THRESHOLD', 500),
        
        // Anonymize data in exports
        'anonymize_by_default' => env('EXPORT_ANONYMIZE_DEFAULT', true),
        
        // Allowed export time windows (24-hour format)
        'allowed_hours' => [
            'start' => env('EXPORT_ALLOWED_START', '09:00'),
            'end' => env('EXPORT_ALLOWED_END', '18:00'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-Tenant Access Control
    |--------------------------------------------------------------------------
    */
    'cross_tenant_control' => [
        'enabled' => env('CROSS_TENANT_CONTROL_ENABLED', true),
        
        // Block cross-tenant access by default
        'block_by_default' => true,
        
        // Allow super-admins to access all tenants
        'allow_super_admin' => true,
        
        // Log all cross-tenant attempts
        'log_all_attempts' => true,
        
        // Require explicit approval for cross-tenant access
        'require_approval' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */
    'audit_logging' => [
        'enabled' => env('CLIENT_PROTECTION_AUDIT_ENABLED', true),
        
        // Log all data access
        'log_all_access' => true,
        
        // Log export attempts
        'log_exports' => true,
        
        // Log hunting detection events
        'log_hunting' => true,
        
        // Log cross-tenant attempts
        'log_cross_tenant' => true,
        
        // ClickHouse integration
        'clickhouse' => [
            'enabled' => env('CLICKHOUSE_AUDIT_ENABLED', true),
            'table' => env('CLICKHOUSE_AUDIT_TABLE', 'ch_security_audit'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration with Existing Services
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        // Cooldown service integration
        'cooldown' => [
            'enabled' => true,
            'actions' => [
                'data_export' => 'DATA_EXPORT',
                'hunting_detected' => 'HUNTING_DETECTED',
            ],
        ],
        
        // Fraud control integration
        'fraud_control' => [
            'enabled' => true,
            'share_hunting_scores' => true,
        ],
        
        // Contact isolation integration
        'contact_isolation' => [
            'enabled' => true,
            'lock_on_hunting_detection' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerts
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('CLIENT_PROTECTION_MONITORING_ENABLED', true),
        
        // Alert thresholds
        'alerts' => [
            'high_hunting_score' => env('ALERT_HIGH_HUNTING_SCORE', 0.8),
            'mass_export_attempt' => env('ALERT_MASS_EXPORT', 1000),
            'cross_tenant_surge' => env('ALERT_CROSS_TENANT_SURGE', 10),
        ],
        
        // Alert channels
        'channels' => [
            'telegram' => env('ALERT_TELEGRAM_ENABLED', false),
            'email' => env('ALERT_EMAIL_ENABLED', true),
            'slack' => env('ALERT_SLACK_ENABLED', false),
        ],
        
        // Alert recipients
        'recipients' => [
            'security_team' => env('ALERT_SECURITY_TEAM_EMAIL', 'security@catvrf.ru'),
            'admin' => env('ALERT_ADMIN_EMAIL', 'admin@catvrf.ru'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance (152-ФЗ)
    |--------------------------------------------------------------------------
    */
    'compliance' => [
        // Data retention period in days
        'retention_days' => env('COMPLIANCE_RETENTION_DAYS', 30),
        
        // Anonymize data before export
        'anonymize_on_export' => true,
        
        // Log consent withdrawals
        'log_consent_withdrawal' => true,
        
        // Auto-delete expired data
        'auto_delete_expired' => env('COMPLIANCE_AUTO_DELETE', true),
        
        // Cleanup job schedule (cron expression)
        'cleanup_schedule' => env('COMPLIANCE_CLEANUP_SCHEDULE', '0 2 * * *'),
    ],
];
