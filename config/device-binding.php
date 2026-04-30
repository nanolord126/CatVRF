<?php

declare(strict_types=1);

/**
 * Device Binding Configuration
 *
 * CatVRF 2026 Enterprise Security - Half-Key Device Binding
 *
 * This configuration controls device attestation and binding for the split key mechanism.
 * Supports multiple attestation methods with fallback hierarchy for maximum device coverage.
 *
 * Attestation Methods Priority:
 * 1. TPM 2.0 / fTPM (Windows/Linux) - Hardware root of trust
 * 2. Secure Enclave (iOS/macOS) - Apple's hardware-backed keystore
 * 3. StrongBox / Titan M2 (Android) - Hardware-backed keystore
 * 4. WebAuthn Platform Authenticator - Browser-based attestation
 * 5. Software + Behavioral Biometrics - Fallback for legacy devices
 *
 * Privacy Compliance (152-ФЗ, GDPR):
 * - Device fingerprints are hashed and salted
 * - No PII stored in device binding data
 * - Attestation certificates are verified but not stored
 * - User can revoke device bindings at any time
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Device Binding
    |--------------------------------------------------------------------------
    |
    | Global enable/disable for device binding in split key mechanism.
    | Set to false to disable device attestation (NOT RECOMMENDED for production).
    |
    */
    'enabled' => env('DEVICE_BINDING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Attestation Methods Priority
    |--------------------------------------------------------------------------
    |
    | Ordered list of attestation methods to try during device binding.
    | The system will try each method in order until one succeeds.
    |
    */
    'attestation_priority' => [
        'tpm',           // TPM 2.0 / fTPM (Windows/Linux)
        'secure_enclave', // Apple Secure Enclave (iOS/macOS)
        'strongbox',     // Android StrongBox / Titan M2
        'webauthn',      // WebAuthn Platform Authenticator
        'software',      // Software + Behavioral Biometrics (fallback)
    ],

    /*
    |--------------------------------------------------------------------------
    | TPM Attestation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for TPM 2.0 / fTPM attestation on Windows/Linux.
    |
    */
    'tpm' => [
        'enabled' => env('TPM_ATTESTATION_ENABLED', true),
        'require_tpm_20' => true, // Require TPM 2.0 (not 1.2)
        'endorsement_key_check' => true, // Verify EK certificate
        'attestation_key_check' => true, // Verify AK certificate
        'allow_snp' => true, // Allow AMD SEV-SNP
        'allow_tdx' => true, // Allow Intel TDX
        'trusted_root_certs' => [
            // Microsoft TPM Root Certificate Authority
            'microsoft_tpm_root' => env('TPM_MICROSOFT_ROOT_CERT'),
            // Google Cloud TPM Root
            'google_tpm_root' => env('TPM_GOOGLE_ROOT_CERT'),
            // Custom root certificates
            'custom_roots' => explode(',', env('TPM_CUSTOM_ROOT_CERTS', '')),
        ],
        'min_tpm_version' => '2.0',
        'timeout_seconds' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Secure Enclave Attestation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for Apple Secure Enclave attestation (iOS/macOS).
    |
    */
    'secure_enclave' => [
        'enabled' => env('SECURE_ENCLAVE_ATTESTATION_ENABLED', true),
        'require_app_attest' => true, // Use App Attest API
        'require_device_check' => true, // Use DeviceCheck API
        'apple_root_certs' => [
            'apple_root_ca_g3' => env('APPLE_ROOT_CA_G3'),
            'apple_attestation_root' => env('APPLE_ATTESTATION_ROOT'),
        ],
        'min_ios_version' => '14.0',
        'min_macos_version' => '12.0',
        'allow_simulator' => false, // Reject simulator in production
        'timeout_seconds' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | StrongBox Attestation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for Android StrongBox / Titan M2 attestation.
    |
    */
    'strongbox' => [
        'enabled' => env('STRONGBOX_ATTESTATION_ENABLED', true),
        'require_strongbox' => true, // Prefer StrongBox over TEE
        'require_play_integrity' => true, // Use Play Integrity API
        'google_root_certs' => [
            'google_root_ca' => env('GOOGLE_ROOT_CA'),
            'android_attestation_root' => env('ANDROID_ATTESTATION_ROOT'),
        ],
        'min_android_version' => '9.0', // Android Pie
        'allow_tee_fallback' => true, // Allow TEE if StrongBox unavailable
        'timeout_seconds' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Attestation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for WebAuthn Platform Authenticator attestation.
    |
    */
    'webauthn' => [
        'enabled' => env('WEBAUTHN_ATTESTATION_ENABLED', true),
        'attestation_formats' => [
            'none',      // No attestation (privacy-preserving)
            'indirect',  // Anonymized CA
            'direct',    // Direct attestation (production)
        ],
        'require_user_verification' => true, // Require biometrics/PIN
        'resident_key' => 'preferred', // Allow resident keys for better UX
        'authenticator_attachment' => 'platform', // Platform authenticator only
        'timeout_seconds' => 60, // WebAuthn has longer timeout
        'allowed_aaguids' => [
            // Windows Hello
            'windows_hello' => env('WEBAUTHN_WINDOWS_HELLO_AAGUID'),
            // Apple Face ID / Touch ID
            'apple_faceid' => env('WEBAUTHN_APPLE_FACEID_AAGUID'),
            'apple_touchid' => env('WEBAUTHN_APPLE_TOUCHID_AAGUID'),
            // Android Biometrics
            'android_biometric' => env('WEBAUTHN_ANDROID_BIOMETRIC_AAGUID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Software Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for software-based device binding (fallback only).
    | Combines device fingerprinting with behavioral biometrics.
    |
    */
    'software' => [
        'enabled' => env('SOFTWARE_DEVICE_BINDING_ENABLED', true),
        'require_behavioral_biometrics' => true, // Require behavioral baseline
        'min_confidence_score' => 0.7, // Minimum confidence for device binding
        'device_fingerprint_components' => [
            'canvas',      // Canvas fingerprint
            'webgl',       // WebGL renderer
            'fonts',       // Available fonts
            'screen',      // Screen resolution
            'timezone',    // Timezone
            'language',    // Browser language
            'hardware_concurrency', // CPU cores
            'device_memory', // RAM
        ],
        'fingerprint_salt' => env('DEVICE_FINGERPRINT_SALT'),
        'fingerprint_ttl_days' => 30, // Re-generate fingerprint monthly
        'risk_threshold' => 0.5, // Above this requires re-authentication
    ],

    /*
    |--------------------------------------------------------------------------
    | Challenge-Response Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for challenge-response signature verification.
    |
    */
    'challenge' => [
        'ttl_seconds' => 300, // 5 minutes
        'length_bytes' => 32, // 256-bit challenge
        'signature_algorithm' => 'ES256', // ECDSA with P-256
        'allow_algorithm_negotiation' => true, // Allow client to propose algorithm
        'max_replay_window_seconds' => 60, // Prevent replay attacks
    ],

    /*
    |--------------------------------------------------------------------------
    | Key Rotation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automatic key rotation based on activity.
    |
    */
    'rotation' => [
        'activity_window_days' => 7, // 7-day active period
        'inactivity_threshold_days' => 7, // Rotate after 7 days inactive
        'force_rotation_on_risk' => true, // Rotate immediately on high risk
        'rotation_grace_period_minutes' => 5, // Allow 5 min grace during rotation
        'notify_user_on_rotation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk-Based Invalidations
    |--------------------------------------------------------------------------
    |
    | Settings for immediate invalidation based on risk signals.
    |
    */
    'risk_invalidation' => [
        'enabled' => true,
        'fraud_threshold' => 0.8, // Fraud score above this invalidates key
        'behavioral_threshold' => 0.85, // Behavioral anomaly above this invalidates key
        'insider_threshold' => 0.7, // Insider threat score above this invalidates key
        'geo_anomaly_threshold' => 0.9, // Geo anomaly above this invalidates key
        'device_change_threshold' => 3, // 3+ device changes in 24h invalidates key
        'trigger_cooldown' => true, // Trigger cooldown on invalidation
        'cooldown_duration_hours' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration with Security Services
    |--------------------------------------------------------------------------
    |
    | Integration settings with other security services.
    |
    */
    'integration' => [
        'behavioral_biometrics' => [
            'enabled' => true,
            'weight_in_device_confidence' => 0.3, // 30% weight in device confidence
            'require_baseline' => true,
            'min_baseline_samples' => 20,
        ],
        'fraud_control' => [
            'enabled' => true,
            'check_on_generation' => true,
            'check_on_validation' => true,
        ],
        'insider_threat' => [
            'enabled' => true,
            'enhanced_monitoring_for_staff' => true,
        ],
        'cooldown' => [
            'enabled' => true,
            'trigger_on_critical_risk' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Hardening
    |--------------------------------------------------------------------------
    |
    | Additional security measures for device binding.
    |
    */
    'hardening' => [
        'rate_limit_attempts' => 5, // Max attestation attempts per minute
        'rate_limit_window_seconds' => 60,
        'max_devices_per_user' => 5, // Maximum active devices per user
        'device_binding_ttl_days' => 90, // Auto-expire old device bindings
        'require_fresh_attestation' => true, // Require fresh attestation on rotation
        'encrypt_device_metadata' => true, // Encrypt device metadata in DB
        'log_all_attestation_failures' => true,
        'alert_on_suspicious_patterns' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance & Privacy
    |--------------------------------------------------------------------------
    |
    | Settings for compliance with 152-ФЗ, GDPR, and other regulations.
    |
    */
    'compliance' => [
        'anonymize_device_data' => true, // Hash device identifiers
        'data_retention_days' => 365, // Keep device binding data for 1 year
        'allow_user_export' => true, // Allow users to export their device data
        'allow_user_deletion' => true, // Allow users to delete device bindings
        'audit_log_enabled' => true, // Log all device binding operations
        'audit_log_retention_days' => 730, // 2 years for audit logs
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance & Caching
    |--------------------------------------------------------------------------
    |
    | Performance optimization settings.
    |
    */
    'performance' => [
        'cache_device_bindings' => true,
        'cache_ttl_seconds' => 3600, // 1 hour
        'use_redis_cache' => env('DEVICE_BINDING_USE_REDIS', true),
        'async_attestation_verification' => true, // Verify attestation in background
        'queue_name' => 'device-attestation',
        'max_verification_time_ms' => 1000, // Fail fast if verification takes too long
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Telemetry
    |--------------------------------------------------------------------------
    |
    | Monitoring and alerting settings.
    |
    */
    'monitoring' => [
        'enabled' => true,
        'log_attestation_success' => true,
        'log_attestation_failures' => true,
        'log_rotation_events' => true,
        'log_invalidation_events' => true,
        'metrics_ttl_seconds' => 86400, // 24 hours
        'prometheus_endpoint' => env('DEVICE_BINDING_PROMETHEUS_ENDPOINT', '/metrics/device-binding'),
        'alert_on_high_failure_rate' => true,
        'failure_rate_threshold_percent' => 10, // Alert if >10% failure rate
    ],

];
