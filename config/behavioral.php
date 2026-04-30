<?php

declare(strict_types=1);

/**
 * Behavioral Biometrics Configuration
 *
 * CatVRF 2026 Enterprise Security - Continuous Authentication
 * 
 * This configuration controls the behavioral biometrics system for:
 * - Keystroke dynamics (typing rhythm, hold times, flight times)
 * - Mouse dynamics (velocity, acceleration, curvature, click patterns)
 * - Touch gestures (pressure, swipe patterns, pinch/zoom)
 * - Session behavior (duration, navigation patterns, time-of-day)
 *
 * Privacy Compliance (152-ФЗ, GDPR):
 * - Only aggregated features stored, never raw keystrokes
 * - User consent required via behavioral_consent flag
 * - Data minimization: 30-day retention for raw data points
 * - Right to deletion: profiles can be reset on request
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Behavioral Biometrics
    |--------------------------------------------------------------------------
    |
    | Global enable/disable for the entire behavioral biometrics system.
    | Set to false to disable all behavioral collection and analysis.
    |
    */
    'enabled' => env('BEHAVIORAL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Consent Management
    |--------------------------------------------------------------------------
    |
    | User consent settings for behavioral data collection.
    | By default, consent is assumed true for new users (opt-out model).
    |
    */
    'consent' => [
        'default_enabled' => env('BEHAVIORAL_CONSENT_DEFAULT', true),
        'require_explicit_consent' => env('BEHAVIORAL_REQUIRE_EXPLICIT_CONSENT', false),
        'consent_version' => '1.0', // Update when privacy policy changes
    ],

    /*
    |--------------------------------------------------------------------------
    | Baseline Building
    |--------------------------------------------------------------------------
    |
    | Settings for building user behavioral baselines during enrollment.
    |
    */
    'baseline' => [
        'min_samples_typing' => env('BEHAVIORAL_MIN_SAMPLES_TYPING', 20),
        'min_samples_mouse' => env('BEHAVIORAL_MIN_SAMPLES_MOUSE', 20),
        'min_samples_touch' => env('BEHAVIORAL_MIN_SAMPLES_TOUCH', 15),
        'min_samples_session' => env('BEHAVIORAL_MIN_SAMPLES_SESSION', 10),
        'enrollment_days' => env('BEHAVIORAL_ENROLLMENT_DAYS', 14), // Days to collect baseline
        'max_samples_stored' => env('BEHAVIORAL_MAX_SAMPLES_STORED', 100), // Per modality
    ],

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    |
    | Similarity score thresholds for anomaly detection.
    | Scores range from 0.0 (completely different) to 1.0 (identical).
    |
    */
    'thresholds' => [
        'similarity' => [
            'anomaly' => env('BEHAVIORAL_THRESHOLD_ANOMALY', 0.30), // Below this = anomaly
            'step_up' => env('BEHAVIORAL_THRESHOLD_STEP_UP', 0.50), // Below this = require re-auth
            'critical' => env('BEHAVIORAL_THRESHOLD_CRITICAL', 0.15), // Below this = block action
        ],
        'risk_score' => [
            'low' => env('BEHAVIORAL_RISK_LOW', 0.3),
            'medium' => env('BEHAVIORAL_RISK_MEDIUM', 0.5),
            'high' => env('BEHAVIORAL_RISK_HIGH', 0.7),
            'critical' => env('BEHAVIORAL_RISK_CRITICAL', 0.85),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modality Weights
    |--------------------------------------------------------------------------
    |
    | Weights for fusing different biometric modalities.
    | Weights are normalized to sum to 1.0 automatically.
    |
    */
    'weights' => [
        'typing' => env('BEHAVIORAL_WEIGHT_TYPING', 0.35), // Keystroke dynamics
        'mouse' => env('BEHAVIORAL_WEIGHT_MOUSE', 0.30), // Mouse movement
        'touch' => env('BEHAVIORAL_WEIGHT_TOUCH', 0.20), // Touch gestures
        'session' => env('BEHAVIORAL_WEIGHT_SESSION', 0.15), // Session patterns
    ],

    /*
    |--------------------------------------------------------------------------
    | Signal Collection
    |--------------------------------------------------------------------------
    |
    | Frontend signal collection settings.
    |
    */
    'collection' => [
        'batch_interval_ms' => env('BEHAVIORAL_BATCH_INTERVAL', 30000), // Send batch every 30s
        'throttle_ms' => env('BEHAVIORAL_THROTTLE_MS', 50), // Throttle events to 50ms
        'max_buffer_size' => env('BEHAVIORAL_MAX_BUFFER_SIZE', 100), // Max events in buffer
        'collect_on_actions' => [
            'login',
            'registration',
            'client_search',
            'client_view',
            'order_create',
            'payment_process',
            'kycb_submit',
            'admin_access',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Retention policies for behavioral data.
    |
    */
    'retention' => [
        'raw_data_points_days' => env('BEHAVIORAL_RETENTION_RAW_DAYS', 30), // Raw events
        'samples_days' => env('BEHAVIORAL_RETENTION_SAMPLES_DAYS', 90), // Feature samples
        'baselines_days' => env('BEHAVIORAL_RETENTION_BASELINES_DAYS', 365), // Baseline profiles
        'anomaly_logs_days' => env('BEHAVIORAL_RETENTION_ANOMALY_DAYS', 180), // Anomaly records
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy & Compliance
    |--------------------------------------------------------------------------
    |
    | Privacy settings for compliance with 152-ФЗ and GDPR.
    |
    */
    'privacy' => [
        'anonymize_before_storage' => true, // Always anonymize raw data
        'store_raw_keystrokes' => false, // Never store actual keystrokes
        'hash_device_fingerprints' => true, // Hash device fingerprints
        'mask_ip_addresses' => true, // Mask last octet of IPs in logs
        'data_export_format' => 'json', // Format for GDPR data export
    ],

    /*
    |--------------------------------------------------------------------------
    | Insider Threat Detection
    |--------------------------------------------------------------------------
    |
    | Enhanced monitoring for staff/admin roles.
    |
    */
    'insider_threat' => [
        'enabled' => env('BEHAVIORAL_INSIDER_ENABLED', true),
        'staff_weight_multiplier' => 1.5, // Increase weight for staff anomalies
        'sensitive_actions' => [
            'client_data_view',
            'client_data_search',
            'client_data_export',
            'user_management',
            'financial_adjustment',
            'wallet_change',
        ],
        'max_client_views_per_hour' => env('BEHAVIORAL_MAX_CLIENT_VIEWS_HOUR', 50),
        'max_client_views_per_day' => env('BEHAVIORAL_MAX_CLIENT_VIEWS_DAY', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Continuous Authentication
    |--------------------------------------------------------------------------
    |
    | Settings for continuous authentication after login.
    |
    */
    'continuous_auth' => [
        'enabled' => env('BEHAVIORAL_CONTINUOUS_AUTH_ENABLED', true),
        'check_interval_seconds' => env('BEHAVIORAL_CHECK_INTERVAL', 60), // Check every 60s
        'session_timeout_on_anomaly' => env('BEHAVIORAL_SESSION_TIMEOUT_ANOMALY', true),
        'require_passkey_on_step_up' => env('BEHAVIORAL_REQUIRE_PASSKEY_STEP_UP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration
    |--------------------------------------------------------------------------
    |
    | Integration settings with other security systems.
    |
    */
    'integration' => [
        'cooldown' => [
            'enabled' => true,
            'trigger_on_severity' => ['high', 'critical'],
            'default_duration_hours' => 1,
        ],
        'fraud_control' => [
            'enabled' => true,
            'weight_in_fraud_score' => 0.2, // 20% weight in overall fraud score
        ],
        'audit' => [
            'log_all_anomalies' => true,
            'log_baseline_changes' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    |
    | Performance optimization settings.
    |
    */
    'performance' => [
        'use_redis_cache' => env('BEHAVIORAL_USE_REDIS_CACHE', true),
        'cache_ttl_seconds' => env('BEHAVIORAL_CACHE_TTL', 3600), // 1 hour
        'async_analysis' => env('BEHAVIORAL_ASYNC_ANALYSIS', true), // Use queue for analysis
        'queue_name' => env('BEHAVIORAL_QUEUE', 'behavioral'),
        'max_analysis_time_ms' => env('BEHAVIORAL_MAX_ANALYSIS_TIME', 500), // Fail fast if slow
    ],

    /*
    |--------------------------------------------------------------------------
    | Machine Learning
    |--------------------------------------------------------------------------
    |
    | ML model settings for advanced anomaly detection.
    |
    */
    'ml' => [
        'enabled' => env('BEHAVIORAL_ML_ENABLED', false), // Disabled by default
        'model_type' => 'statistical', // statistical, oneclass_svm, autoencoder
        'retrain_interval_days' => 30,
        'python_bridge_enabled' => false,
    ],

];
