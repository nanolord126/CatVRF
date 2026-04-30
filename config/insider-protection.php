<?php

declare(strict_types=1);

/**
 * Insider Protection Configuration
 * 
 * Comprehensive settings for insider threat protection and client data security.
 * This config enforces Zero Trust principles for customer data access.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Data Exfiltration Fortress.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Behavioral Monitoring Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for detecting suspicious staff behavior when accessing client data.
    |
    */
    'behavioral' => [
        // Maximum client profile views per hour before triggering alert
        'max_client_views_per_hour' => 50,
        
        // Maximum client profile views per day before triggering alert
        'max_client_views_per_day' => 200,
        
        // Hunting score threshold (0-1) for automatic blocking
        'hunting_score_threshold' => 0.7,
        
        // Anomaly score threshold (0-1) for immediate lockout
        'anomaly_score_threshold' => 0.85,
        
        // Alert threshold (0-1) for notification to owners
        'alert_threshold' => 0.6,
        
        // Business hours (for unusual time detection)
        'business_hours_start' => 9,
        'business_hours_end' => 18,
        
        // Frequency threshold for same action type (per 5 minutes)
        'frequency_threshold' => 10,
        
        // Mass operation threshold (number of records)
        'mass_operation_threshold' => 100,
        
        // Financial manipulation threshold (in RUB)
        'financial_threshold' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Access Control
    |--------------------------------------------------------------------------
    |
    | Settings for controlling staff access to client data.
    |
    */
    'data_access' => [
        // Maximum records per API request for staff roles
        'max_records_per_request' => [
            'super_admin' => 1000,
            'support_agent' => 500,
            'owner' => 200,
            'manager' => 100,
            'employee' => 50,
            'accountant' => 50,
            'customer' => 20,
        ],
        
        // Maximum export records per request
        'max_export_records' => [
            'super_admin' => 1000,
            'support_agent' => 500,
            'owner' => 200,
            'manager' => 100,
            'employee' => 20,
            'accountant' => 20,
            'customer' => 0, // Customers cannot export
        ],
        
        // Export requires super-admin approval for records above this count
        'export_approval_threshold' => 50,
        
        // Rate limiting (requests per minute)
        'rate_limit_per_minute' => [
            'super_admin' => 60,
            'support_agent' => 30,
            'owner' => 20,
            'manager' => 15,
            'employee' => 10,
            'accountant' => 10,
            'customer' => 5,
        ],
        
        // Enable JIT (Just-In-Time) access elevation
        'enable_jit_access' => true,
        
        // JIT access duration in minutes
        'jit_access_duration_minutes' => 60,
        
        // JIT access requires Passkey verification
        'jit_requires_passkey' => true,
        
        // JIT access requires 2FA
        'jit_requires_2fa' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Masking Rules
    |--------------------------------------------------------------------------
    |
    | Configuration for masking sensitive client data.
    |
    */
    'masking' => [
        // Enable masking by default for all non-admin users
        'mask_by_default' => true,
        
        // Fields to mask in User model
        'masked_user_fields' => [
            'email',
            'phone',
            'inn',
            'first_name',
            'last_name',
            'middle_name',
        ],
        
        // Fields to mask in Order model
        'masked_order_fields' => [
            'delivery_address',
            'inn',
        ],
        
        // Show full data to users with these permissions
        'full_access_permissions' => [
            'viewFullClientData',
            'viewUnmaskedOrders',
        ],
        
        // Super-admin roles that bypass masking
        'unmask_roles' => [
            'super_admin',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export & Bulk Actions Control
    |--------------------------------------------------------------------------
    |
    | Restrictions on data export and bulk operations.
    |
    */
    'export_control' => [
        // Enable export for staff (disabled by default)
        'enable_staff_export' => false,
        
        // Export requires manual approval for staff
        'requires_manual_approval' => true,
        
        // Cooldown period after export (in hours)
        'export_cooldown_hours' => 24,
        
        // Maximum concurrent exports per tenant
        'max_concurrent_exports' => 1,
        
        // Export queue name
        'export_queue' => 'exports',
        
        // Enable honeypot fields for suspicious users
        'enable_honeypot' => true,
        
        // Honeypot field names
        'honeypot_fields' => [
            '_trap_email',
            '_trap_phone',
            '_trap_account',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Deprovisioning & Offboarding
    |--------------------------------------------------------------------------
    |
    | Settings for handling staff offboarding and access revocation.
    |
    */
    'deprovisioning' => [
        // Cooldown period before rejoining tenant (in days)
        'rejoin_cooldown_days' => 30,
        
        // Audit period for ex-employee actions (in days)
        'audit_period_days' => 30,
        
        // Revoke all sessions immediately on offboarding
        'revoke_sessions_immediately' => true,
        
        // Revoke all Passkeys on offboarding
        'revoke_passkeys_immediately' => true,
        
        // Revoke all Sanctum tokens on offboarding
        'revoke_tokens_immediately' => true,
        
        // Send notification to all owners on offboarding
        'notify_owners_on_offboard' => true,
        
        // Send notification to super-admin on offboarding
        'notify_superadmin_on_offboard' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit & Logging
    |--------------------------------------------------------------------------
    |
    | Settings for audit logging and monitoring.
    |
    */
    'audit' => [
        // Enable immutable audit logging to ClickHouse
        'enable_clickhouse_audit' => env('ENABLE_CLICKHOUSE_AUDIT', true),
        
        // Log all client data accesses
        'log_all_client_access' => true,
        
        // Log all export attempts
        'log_all_exports' => true,
        
        // Log all JIT access elevations
        'log_jit_elevations' => true,
        
        // Log all hunting pattern detections
        'log_hunting_patterns' => true,
        
        // Retention period for audit logs (in days)
        'retention_days' => 365,
        
        // Real-time alert channels
        'alert_channels' => [
            'telegram' => env('TELEGRAM_SECURITY_ALERT_ENABLED', false),
            'slack' => env('SLACK_SECURITY_ALERT_ENABLED', false),
            'email' => env('EMAIL_SECURITY_ALERT_ENABLED', true),
        ],
        
        // Weekly report enabled
        'enable_weekly_report' => true,
        
        // Weekly report day of week (1 = Monday, 7 = Sunday)
        'weekly_report_day' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-Based Access Control
    |--------------------------------------------------------------------------
    |
    | Special roles for client data access.
    |
    */
    'roles' => [
        // client-viewer: Can only view masked data
        'client_viewer' => [
            'can_view_masked' => true,
            'can_view_full' => false,
            'can_export' => false,
            'can_search' => false,
        ],
        
        // client-moderator: Can view verification logs only
        'client_moderator' => [
            'can_view_masked' => true,
            'can_view_full' => false,
            'can_export' => false,
            'can_search' => true,
            'can_view_verification' => true,
        ],
        
        // client-support: Can view orders only
        'client_support' => [
            'can_view_masked' => true,
            'can_view_full' => false,
            'can_export' => false,
            'can_search' => true,
            'can_view_orders' => true,
        ],
        
        // client-full-access: Full access (only super-admin and tenant-owner with 2FA)
        'client_full_access' => [
            'requires_2fa' => true,
            'requires_passkey' => true,
            'can_view_masked' => true,
            'can_view_full' => true,
            'can_export' => true,
            'can_search' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-Tenant Protection
    |--------------------------------------------------------------------------
    |
    | Protection against cross-tenant data access.
    |
    */
    'cross_tenant' => [
        // Enable cross-tenant access prevention
        'enabled' => true,
        
        // Block cross-tenant queries
        'block_queries' => true,
        
        // Log cross-tenant attempts
        'log_attempts' => true,
        
        // Alert on cross-tenant attempts
        'alert_on_attempt' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | ClickHouse Integration
    |--------------------------------------------------------------------------
    |
    | Settings for ClickHouse audit logging.
    |
    */
    'clickhouse' => [
        'enabled' => env('CLICKHOUSE_AUDIT_ENABLED', true),
        'connection' => 'clickhouse',
        'table' => 'security_events',
        'batch_size' => 100,
        'flush_interval_seconds' => 5,
    ],
];
