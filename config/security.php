<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI Face Verification Provider
    |--------------------------------------------------------------------------
    |
    | Supported providers: yandex, faceio, aws, mock
    | - yandex: Yandex Vision (recommended for RF)
    | - faceio: FACEIO
    | - aws: AWS Rekognition
    | - mock: Mock implementation for testing
    |
    */

    'ai_face_provider' => env('AI_FACE_PROVIDER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Yandex Vision Configuration
    |--------------------------------------------------------------------------
    */

    'yandex_vision_api_key' => env('YANDEX_VISION_API_KEY'),
    'yandex_folder_id' => env('YANDEX_FOLDER_ID'),

    /*
    |--------------------------------------------------------------------------
    | FACEIO Configuration
    |--------------------------------------------------------------------------
    */

    'faceio_api_key' => env('FACEIO_API_KEY'),
    'faceio_application_id' => env('FACEIO_APPLICATION_ID'),

    /*
    |--------------------------------------------------------------------------
    | AWS Rekognition Configuration
    |--------------------------------------------------------------------------
    */

    'aws_access_key' => env('AWS_ACCESS_KEY'),
    'aws_secret_key' => env('AWS_SECRET_KEY'),
    'aws_region' => env('AWS_REGION', 'us-east-1'),

    /*
    |--------------------------------------------------------------------------
    | Account Protection Settings
    |--------------------------------------------------------------------------
    */

    'account_protection' => [
        'max_failed_attempts' => env('MAX_FAILED_AUTH_ATTEMPTS', 5),
        'lock_duration_hours' => env('ACCOUNT_LOCK_DURATION_HOURS', 24),
        'velocity_window_seconds' => env('VELOCITY_WINDOW_SECONDS', 300),
        'max_requests_per_window' => env('MAX_REQUESTS_PER_WINDOW', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recovery Settings
    |--------------------------------------------------------------------------
    */

    'recovery' => [
        'otp_ttl_seconds' => env('RECOVERY_OTP_TTL_SECONDS', 600),
        'cooldown_hours' => env('RECOVERY_COOLDOWN_HOURS', 24),
        'backup_codes_count' => env('BACKUP_CODES_COUNT', 10),
        'backup_code_length' => env('BACKUP_CODE_LENGTH', 8),
        'high_risk_threshold' => env('HIGH_RISK_THRESHOLD', 0.80),
        'medium_risk_threshold' => env('MEDIUM_RISK_THRESHOLD', 0.40),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deepfake Detection Thresholds
    |--------------------------------------------------------------------------
    */

    'deepfake_detection' => [
        'min_liveness_score' => env('MIN_LIVENESS_SCORE', 0.85),
        'min_face_match_score' => env('MIN_FACE_MATCH_SCORE', 0.90),
        'max_deepfake_score' => env('MAX_DEEPFAKE_SCORE', 0.30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Management
    |--------------------------------------------------------------------------
    */

    'device_management' => [
        'auto_trust_after_days' => env('AUTO_TRUST_DEVICE_AFTER_DAYS', 30),
        'max_trusted_devices' => env('MAX_TRUSTED_DEVICES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */

    'audit' => [
        'log_to_clickhouse' => env('AUDIT_LOG_TO_CLICKHOUSE', false),
        'clickhouse_table' => env('AUDIT_CLICKHOUSE_TABLE', 'security_events'),
        'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limiting' => [
        'recovery_init_per_hour' => env('RATE_LIMIT_RECOVERY_INIT', 3),
        'recovery_verify_per_hour' => env('RATE_LIMIT_RECOVERY_VERIFY', 10),
        'face_verify_per_hour' => env('RATE_LIMIT_FACE_VERIFY', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Brute Force Protection
    |--------------------------------------------------------------------------
    |
    | Configuration for brute-force attack prevention including rate limits,
    | HIBP checks, and account lockout policies.
    |
    */

    'brute_force' => [
        'enabled' => env('BRUTE_FORCE_PROTECTION_ENABLED', true),

        // Login attempt limits
        'login' => [
            'max_attempts' => env('BRUTE_FORCE_MAX_ATTEMPTS', 5),
            'window_minutes' => env('BRUTE_FORCE_WINDOW_MINUTES', 5),
            'lock_after_attempts' => env('BRUTE_FORCE_LOCK_AFTER_ATTEMPTS', 5),
            'lock_duration_minutes' => env('BRUTE_FORCE_LOCK_DURATION_MINUTES', 60),
        ],

        // Registration attempt limits
        'register' => [
            'max_attempts' => env('REGISTER_MAX_ATTEMPTS', 3),
            'window_minutes' => env('REGISTER_WINDOW_MINUTES', 60),
        ],

        // Password recovery limits
        'recovery' => [
            'max_attempts' => env('RECOVERY_MAX_ATTEMPTS', 3),
            'window_minutes' => env('RECOVERY_WINDOW_MINUTES', 60),
        ],

        // HIBP (Have I Been Pwned) password check
        'enable_hibp' => env('ENABLE_HIBP_CHECK', true),
        'hibp_timeout_seconds' => env('HIBP_TIMEOUT_SECONDS', 5),

        // Velocity check (ML-based)
        'enable_velocity_check' => env('ENABLE_VELOCITY_CHECK', true),
        'velocity_window_minutes' => env('VELOCITY_WINDOW_MINUTES', 10),
        'velocity_max_unique_ips' => env('VELOCITY_MAX_UNIQUE_IPS', 3),
        'velocity_max_attempts' => env('VELOCITY_MAX_ATTEMPTS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Insider Threat Protection
    |--------------------------------------------------------------------------
    |
    | Configuration for insider threat detection and employee deprovisioning.
    |
    */

    'insider_threat' => [
        'enabled' => env('INSIDER_THREAT_PROTECTION_ENABLED', true),

        // Anomaly detection thresholds
        'alert_threshold' => env('INSIDER_ALERT_THRESHOLD', 0.7), // 70%
        'block_threshold' => env('INSIDER_BLOCK_THRESHOLD', 0.85), // 85%

        // Business hours (for unusual time detection)
        'business_hours_start' => env('BUSINESS_HOURS_START', 9),
        'business_hours_end' => env('BUSINESS_HOURS_END', 18),

        // Frequency detection
        'frequency_threshold' => env('INSIDER_FREQUENCY_THRESHOLD', 10), // actions per 5 minutes

        // Mass operation thresholds
        'mass_operation_threshold' => env('MASS_OPERATION_THRESHOLD', 100),

        // Financial manipulation thresholds
        'financial_threshold' => env('FINANCIAL_THRESHOLD', 10000),

        // Cool-down period after revocation (days)
        'cooldown_days' => env('EMPLOYEE_COOLDOWN_DAYS', 30),

        // Multi-owner safeguard
        'require_multi_owner_confirmation' => env('REQUIRE_MULTI_OWNER_CONFIRMATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passkey Authentication
    |--------------------------------------------------------------------------
    |
    | Configuration for WebAuthn/Passkey authentication.
    |
    */

    'passkey' => [
        'enabled' => env('PASSKEY_AUTH_ENABLED', true),
        'relying_party_id' => env('PASSKEY_RELYING_PARTY_ID', env('APP_URL')),
        'relying_party_name' => env('PASSKEY_RELYING_PARTY_NAME', env('APP_NAME')),
        'challenge_ttl_seconds' => env('PASSKEY_CHALLENGE_TTL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    |
    | Configuration for session security and management.
    |
    */

    'session' => [
        'max_concurrent_sessions' => env('MAX_CONCURRENT_SESSIONS', 5),
        'session_timeout_minutes' => env('SESSION_TIMEOUT_MINUTES', 60),
        'remember_me_days' => env('REMEMBER_ME_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Fingerprinting
    |--------------------------------------------------------------------------
    |
    | Configuration for device fingerprinting and tracking.
    |
    */

    'device_fingerprint' => [
        'enabled' => env('DEVICE_FINGERPRINT_ENABLED', true),
        'trusted_device_days' => env('TRUSTED_DEVICE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Behavioral Biometrics (NEW 2026)
    |--------------------------------------------------------------------------
    |
    | Configuration for behavioral biometrics analysis including typing rhythm,
    | mouse movements, touch patterns, and session behavior.
    |
    */

    'behavioral_biometrics' => [
        'enabled' => env('BEHAVIORAL_BIOMETRICS_ENABLED', true),

        // Minimum samples required before profile is considered mature
        'min_samples_for_profile' => env('BEHAVIORAL_MIN_SAMPLES', 5),

        // Similarity thresholds
        'similarity_threshold' => env('BEHAVIORAL_SIMILARITY_THRESHOLD', 0.75),
        'anomaly_threshold' => env('BEHAVIORAL_ANOMALY_THRESHOLD', 0.40),

        // Cache TTL for session scores
        'cache_ttl_hours' => env('BEHAVIORAL_CACHE_TTL_HOURS', 24),

        // Signal collection settings
        'typing' => [
            'enabled' => env('BEHAVIORAL_TYPING_ENABLED', true),
            'sample_size' => env('BEHAVIORAL_TYPING_SAMPLE_SIZE', 100),
        ],

        'mouse' => [
            'enabled' => env('BEHAVIORAL_MOUSE_ENABLED', true),
            'sample_size' => env('BEHAVIORAL_MOUSE_SAMPLE_SIZE', 50),
        ],

        'touch' => [
            'enabled' => env('BEHAVIORAL_TOUCH_ENABLED', true),
            'sample_size' => env('BEHAVIORAL_TOUCH_SAMPLE_SIZE', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Adaptive Authentication (NEW 2026)
    |--------------------------------------------------------------------------
    |
    | Configuration for risk-based step-up authentication using multiple
    | signals: behavioral, device, geo-velocity, time patterns, ML.
    |
    */

    'adaptive_auth' => [
        'enabled' => env('ADAPTIVE_AUTH_ENABLED', true),

        // Risk thresholds
        'low_risk_threshold' => env('ADAPTIVE_LOW_RISK_THRESHOLD', 0.30),
        'medium_risk_threshold' => env('ADAPTIVE_MEDIUM_RISK_THRESHOLD', 0.50),
        'high_risk_threshold' => env('ADAPTIVE_HIGH_RISK_THRESHOLD', 0.70),
        'critical_risk_threshold' => env('ADAPTIVE_CRITICAL_RISK_THRESHOLD', 0.85),

        // Step-up challenge TTL
        'step_up_challenge_ttl' => env('ADAPTIVE_STEP_UP_TTL', 300), // 5 minutes

        // Result cache TTL
        'cache_ttl_minutes' => env('ADAPTIVE_CACHE_TTL_MINUTES', 15),

        // Component weights (must sum to 1.0)
        'weights' => [
            'behavioral' => env('ADAPTIVE_WEIGHT_BEHAVIORAL', 0.25),
            'device' => env('ADAPTIVE_WEIGHT_DEVICE', 0.20),
            'geo_velocity' => env('ADAPTIVE_WEIGHT_GEO_VELOCITY', 0.15),
            'time_pattern' => env('ADAPTIVE_WEIGHT_TIME_PATTERN', 0.10),
            'auth_history' => env('ADAPTIVE_WEIGHT_AUTH_HISTORY', 0.10),
            'ml' => env('ADAPTIVE_WEIGHT_ML', 0.20),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Continuous Authentication (NEW 2026)
    |--------------------------------------------------------------------------
    |
    | Configuration for silent behavioral monitoring during authenticated sessions.
    |
    */

    'continuous_auth' => [
        'enabled' => env('CONTINUOUS_AUTH_ENABLED', true),

        // Check interval (minutes between analyses)
        'check_interval_minutes' => env('CONTINUOUS_AUTH_CHECK_INTERVAL', 5),

        // Max consecutive anomalies before logout
        'max_consecutive_anomalies' => env('CONTINUOUS_AUTH_MAX_ANOMALIES', 3),

        // Session risk threshold
        'session_risk_threshold' => env('CONTINUOUS_AUTH_RISK_THRESHOLD', 0.70),

        // Cache TTL
        'cache_ttl_hours' => env('CONTINUOUS_AUTH_CACHE_TTL_HOURS', 24),

        // Skip paths (health checks, etc.)
        'skip_paths' => [
            'health',
            'metrics',
            'api/health',
            'api/octane/health',
            'sanctum/csrf-cookie',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Whitelist
    |--------------------------------------------------------------------------
    |
    | Configuration for IP-based access control for webhooks and admin endpoints.
    |
    */

    'ip_whitelist' => [
        'webhook' => [
            // Tinkoff payment gateway IPs
            '85.143.0.0/16',
            // Sberbank payment gateway IPs
            '77.244.0.0/14',
            // SBP (Система быстрых платежей) IPs
            '195.68.0.0/14',
        ],
        'admin' => [
            // Internal corporate network
            '195.0.0.0/8',
        ],
        'partner' => [
            // Partner network IPs (configure per partner)
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 2026 Security Standards
    |--------------------------------------------------------------------------
    |
    | Global security standards for CatVRF 2026 enterprise deployment.
    |
    */

    'standards' => [
        // Passwordless-first authentication
        'passwordless_first' => env('SECURITY_PASSWORDLESS_FIRST', true),

        // Phishing-resistant MFA required
        'phishing_resistant_mfa' => env('SECURITY_PHISHING_RESISTANT_MFA', true),

        // Behavioral biometrics enabled
        'behavioral_biometrics_enabled' => env('SECURITY_BEHAVIORAL_BIOMETRICS', true),

        // Continuous authentication enabled
        'continuous_auth_enabled' => env('SECURITY_CONTINUOUS_AUTH', true),

        // Deepfake detection required for sensitive operations
        'deepfake_required_sensitive' => env('SECURITY_DEEPFAKE_REQUIRED', true),

        // Instant deprovisioning for ex-employees
        'instant_deprovisioning' => env('SECURITY_INSTANT_DEPROVISIONING', true),

        // Zero Trust architecture
        'zero_trust' => env('SECURITY_ZERO_TRUST', true),

        // Multi-tenancy isolation required
        'multi_tenancy_isolation' => env('SECURITY_MULTI_TENANCY_ISOLATION', true),

        // Audit logging to ClickHouse
        'audit_clickhouse' => env('SECURITY_AUDIT_CLICKHOUSE', false),

        // PII anonymization (152-ФZ compliance)
        'pii_anonymization' => env('SECURITY_PII_ANONYMIZATION', true),
    ],
];
