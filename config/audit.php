<?php

declare(strict_types=1);

/**
 * Audit Configuration - Production Ready
 * 
 * Centralized configuration for audit logging system.
 * All audit-related settings are managed here for easy maintenance.
 * 
 * Environment Variables Override:
 * - AUDIT_ENABLED
 * - AUDIT_ASYNC
 * - AUDIT_QUEUE
 * - AUDIT_RETENTION_DAYS
 * - AUDIT_ANONYMIZATION_ENABLED
 * - AUDIT_ANONYMIZATION_DAYS
 * - AUDIT_CLICKHOUSE_ENABLED
 * 
 * @see \App\Services\AuditService
 * @see \App\Jobs\AuditLogJob
 * @see \App\Traits\WithAuditLogging
 */
return [
    
    /*
    |--------------------------------------------------------------------------
    | Audit Logging Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for audit logging. When disabled, no audit logs will be
    | recorded regardless of other settings. Useful for development or
    | performance testing.
    |
    */
    'enabled' => env('AUDIT_ENABLED', true),
    
    /*
    |--------------------------------------------------------------------------
    | Async Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, audit logs are written asynchronously via queue jobs.
    | This improves performance but adds slight delay in log visibility.
    | Disable for immediate logging (not recommended in production).
    |
    */
    'async' => env('AUDIT_ASYNC', true),
    
    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue name for async audit log jobs. Use a dedicated queue to prevent
    | audit logging from blocking critical application queues.
    |
    */
    'queue' => env('AUDIT_QUEUE', 'audit-logs'),
    
    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    |
    | Queue connection to use for audit log jobs. Default uses the
    | application's default queue connection.
    |
    */
    'queue_connection' => env('AUDIT_QUEUE_CONNECTION', null),
    
    /*
    |--------------------------------------------------------------------------
    | Job Retries
    |--------------------------------------------------------------------------
    |
    | Number of times to retry failed audit log jobs before giving up.
    | Audit logs should be reliable, but don't want infinite retries.
    |
    */
    'job_retries' => env('AUDIT_JOB_RETRIES', 3),
    
    /*
    |--------------------------------------------------------------------------
    | Job Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds for audit log job execution.
    | Set to reasonable value to prevent hanging jobs.
    |
    */
    'job_timeout' => env('AUDIT_JOB_TIMEOUT', 30),
    
    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to retain audit logs before automatic cleanup.
    | Compliant with 152-FZ requirements (minimum 5 years for some data).
    |
    */
    'retention_days' => env('AUDIT_RETENTION_DAYS', 2555), // 7 years default
    
    /*
    |--------------------------------------------------------------------------
    | Data Anonymization
    |--------------------------------------------------------------------------
    |
    | Enable automatic anonymization of PII in old audit logs.
    | When enabled, logs older than anonymization_days will have
    | sensitive data masked while keeping audit trail intact.
    |
    */
    'anonymization_enabled' => env('AUDIT_ANONYMIZATION_ENABLED', true),
    
    /*
    |--------------------------------------------------------------------------
    | Anonymization Threshold
    |--------------------------------------------------------------------------
    |
    | Number of days after which to anonymize audit logs.
    | Should be less than retention_days to allow gradual cleanup.
    |
    */
    'anonymization_days' => env('AUDIT_ANONYMIZATION_DAYS', 365), // 1 year
    
    /*
    |--------------------------------------------------------------------------
    | ClickHouse Integration
    |--------------------------------------------------------------------------
    |
    | Enable ClickHouse for long-term immutable audit log storage.
    | ClickHouse provides better performance for analytics queries
    | and compressed storage for historical data.
    |
    */
    'clickhouse_enabled' => env('AUDIT_CLICKHOUSE_ENABLED', false),
    
    /*
    |--------------------------------------------------------------------------
    | ClickHouse Connection
    |--------------------------------------------------------------------------
    |
    | Database connection name for ClickHouse.
    | Must be configured in config/database.php.
    |
    */
    'clickhouse_connection' => env('AUDIT_CLICKHOUSE_CONNECTION', 'clickhouse'),
    
    /*
    |--------------------------------------------------------------------------
    | ClickHouse Table
    |--------------------------------------------------------------------------
    |
    | Table name for audit logs in ClickHouse.
    |
    */
    'clickhouse_table' => env('AUDIT_CLICKHOUSE_TABLE', 'audit_logs'),
    
    /*
    |--------------------------------------------------------------------------
    | Sensitive Fields
    |--------------------------------------------------------------------------
    |
    | List of field names that should be anonymized in audit logs.
    | These fields will be replaced with [REDACTED] when anonymizing.
    |
    */
    'sensitive_fields' => [
        'password',
        'email',
        'phone',
        'mobile',
        'name',
        'first_name',
        'last_name',
        'middle_name',
        'passport',
        'passport_number',
        'passport_series',
        'inn',
        'snils',
        'address',
        'card_number',
        'card_holder',
        'cvv',
        'cvc',
        'expiry',
        'expiration',
        'secret',
        'token',
        'api_key',
        'access_token',
        'refresh_token',
        'otp',
        'otp_code',
        'verification_code',
        'pin',
        'ssn',
        'credit_card',
        'bank_account',
        'routing_number',
        'iban',
        'swift',
        'bic',
        'face_image',
        'fingerprint',
        'biometric',
        'voice_recording',
        'signature',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Exclude Actions
    |--------------------------------------------------------------------------
    |
    | List of action patterns to exclude from audit logging.
    | Useful for high-frequency low-value operations.
    | Supports wildcards: 'user.*_view'
    |
    */
    'exclude_actions' => [
        // 'heartbeat',
        // 'ping',
        // 'health_check',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Exclude Subjects
    |--------------------------------------------------------------------------
    |
    | List of subject types to exclude from audit logging.
    | Useful for non-critical entities.
    |
    */
    'exclude_subjects' => [
        // 'Illuminate\\Log\\Events\\MessageLogged',
        // 'Illuminate\\Cache\\Events\\CacheHit',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Sampling Rate
    |--------------------------------------------------------------------------
    |
    | Percentage of audit logs to record (0-100).
    | Useful for reducing volume in high-traffic scenarios.
    | 100 = record all logs, 50 = record half, 0 = disable.
    |
    */
    'sampling_rate' => env('AUDIT_SAMPLING_RATE', 100),
    
    /*
    |--------------------------------------------------------------------------
    | Batch Size for Anonymization
    |--------------------------------------------------------------------------
    |
    | Number of records to process in a single batch during anonymization.
    | Higher values are faster but use more memory.
    |
    */
    'anonymization_batch_size' => env('AUDIT_ANONYMIZATION_BATCH_SIZE', 1000),
    
    /*
    |--------------------------------------------------------------------------
    | Export Limits
    |--------------------------------------------------------------------------
    |
    | Maximum number of records that can be exported in a single operation.
    | Prevents memory issues and long-running exports.
    |
    */
    'export_limit' => env('AUDIT_EXPORT_LIMIT', 10000),
    
    /*
    |--------------------------------------------------------------------------
    | Pagination Default
    |--------------------------------------------------------------------------
    |
    | Default number of records per page for audit log queries.
    |
    */
    'pagination_per_page' => env('AUDIT_PAGINATION_PER_PAGE', 50),
    
    /*
    |--------------------------------------------------------------------------
    | Correlation ID Header
    |--------------------------------------------------------------------------
    |
    | HTTP header name for correlation ID in incoming requests.
    | If present, will be used for audit log correlation.
    |
    */
    'correlation_header' => env('AUDIT_CORRELATION_HEADER', 'X-Correlation-ID'),
    
    /*
    |--------------------------------------------------------------------------
    | User ID Extraction
    |--------------------------------------------------------------------------
    |
    | Method to extract user ID for audit logs.
    | 'auth' - use Laravel auth system
    | 'request' - use request header (specify header name)
    | 'custom' - use custom resolver (specify class)
    |
    */
    'user_id_source' => env('AUDIT_USER_ID_SOURCE', 'auth'),
    
    /*
    |--------------------------------------------------------------------------
    | Custom User ID Resolver
    |--------------------------------------------------------------------------
    |
    | Custom class to resolve user ID if source is 'custom'.
    | Must implement getUserId(): ?int method.
    |
    */
    'user_id_resolver' => env('AUDIT_USER_ID_RESOLVER', null),
    
    /*
    |--------------------------------------------------------------------------
    | Tenant ID Extraction
    |--------------------------------------------------------------------------
    |
    | Method to extract tenant ID for audit logs.
    | 'tenant' - use tenancy package (stancl/tenancy)
    | 'request' - use request header (specify header name)
    | 'custom' - use custom resolver (specify class)
    |
    */
    'tenant_id_source' => env('AUDIT_TENANT_ID_SOURCE', 'tenant'),
    
    /*
    |--------------------------------------------------------------------------
    | Custom Tenant ID Resolver
    |--------------------------------------------------------------------------
    |
    | Custom class to resolve tenant ID if source is 'custom'.
    | Must implement getTenantId(): ?int method.
    |
    */
    'tenant_id_resolver' => env('AUDIT_TENANT_ID_RESOLVER', null),
    
    /*
    |--------------------------------------------------------------------------
    | IP Address Extraction
    |--------------------------------------------------------------------------
    |
    | Method to extract IP address for audit logs.
    | 'request' - use request IP
    | 'header' - use specific header (for behind proxy)
    |
    */
    'ip_address_source' => env('AUDIT_IP_ADDRESS_SOURCE', 'request'),
    
    /*
    |--------------------------------------------------------------------------
    | IP Address Header
    |--------------------------------------------------------------------------
    |
    | Header name for IP address if source is 'header'.
    |
    */
    'ip_address_header' => env('AUDIT_IP_ADDRESS_HEADER', 'X-Forwarded-For'),
    
    /*
    |--------------------------------------------------------------------------
    | Device Fingerprinting
    |--------------------------------------------------------------------------
    |
    | Enable device fingerprinting for fraud detection.
    | Creates SHA256 hash of IP + User-Agent.
    |
    */
    'device_fingerprint_enabled' => env('AUDIT_DEVICE_FINGERPRINT_ENABLED', true),
    
    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | Laravel log channel for audit log entries.
    | Separate from file storage for structured querying.
    |
    */
    'log_channel' => env('AUDIT_LOG_CHANNEL', 'audit'),
    
    /*
    |--------------------------------------------------------------------------
    | Event Listeners
    |--------------------------------------------------------------------------
    |
    | Automatically listen to these Laravel events and log them.
    | Format: 'EventClass' => 'action_name'
    |
    */
    'auto_log_events' => [
        // 'Illuminate\\Auth\\Events\\Login' => 'auth_login',
        // 'Illuminate\\Auth\\Events\\Logout' => 'auth_logout',
        // 'Illuminate\\Auth\\Events\\Failed' => 'auth_failed',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Model Observers
    |--------------------------------------------------------------------------
    |
    | Automatically observe these models and log their changes.
    | Format: 'ModelClass' => true (auto-detect actions)
    |
    */
    'auto_observe_models' => [
        // 'App\\Models\\User' => true,
        // 'App\\Models\\Order' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable performance monitoring for audit operations.
    | Logs slow operations and queue times.
    |
    */
    'performance_monitoring' => env('AUDIT_PERFORMANCE_MONITORING', false),
    
    /*
    |--------------------------------------------------------------------------
    | Slow Operation Threshold (ms)
    |--------------------------------------------------------------------------
    |
    | Threshold in milliseconds for considering an operation slow.
    | Only used if performance_monitoring is enabled.
    |
    */
    'slow_operation_threshold' => env('AUDIT_SLOW_OPERATION_THRESHOLD', 1000),
    
];
