<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Passkey Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WebAuthn/Passkey authentication in CatVRF.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Relying Party (RP) Configuration
    |--------------------------------------------------------------------------
    |
    | The Relying Party is the entity that requests authentication.
    | For CatVRF, this should be your domain.
    |
    */
    'rp_id' => env('PASSKEY_RP_ID', parse_url(config('app.url'), PHP_URL_HOST)),
    'rp_name' => env('PASSKEY_RP_NAME', 'CatVRF Healthcare Marketplace'),
    'rp_origin' => env('PASSKEY_RP_ORIGIN', config('app.url')),

    /*
    |--------------------------------------------------------------------------
    | Challenge Configuration
    |--------------------------------------------------------------------------
    |
    | Challenge TTL and configuration for WebAuthn challenges.
    |
    */
    'challenge_ttl' => env('PASSKEY_CHALLENGE_TTL', 300), // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for passkey authentication.
    |
    */
    'authentication' => [
        'user_verification' => env('PASSKEY_USER_VERIFICATION', 'preferred'), // required, preferred, discouraged
        'timeout' => env('PASSKEY_TIMEOUT', 60000), // 60 seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for passkey registration.
    |
    */
    'registration' => [
        'authenticator_attachment' => env('PASSKEY_AUTHENTICATOR_ATTACHMENT', 'platform'), // platform, cross-platform
        'user_verification' => env('PASSKEY_REGISTRATION_USER_VERIFICATION', 'required'),
        'resident_key' => env('PASSKEY_RESIDENT_KEY', 'preferred'), // required, preferred, discouraged
        'attestation' => env('PASSKEY_ATTESTATION', 'none'), // none, direct, indirect, enterprise
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for passkey operations.
    |
    */
    'security' => [
        'require_user_verification_for_sensitive_operations' => env('PASSKEY_REQUIRE_VERIFICATION_SENSITIVE', true),
        'max_credentials_per_user' => env('PASSKEY_MAX_CREDENTIALS', 10),
        'allow_credential_deletion' => env('PASSKEY_ALLOW_DELETION', true),
        'require_at_least_one_credential' => env('PASSKEY_REQUIRE_AT_LEAST_ONE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | Multi-tenancy settings for passkey credentials.
    |
    */
    'multi_tenancy' => [
        'enabled' => env('PASSKEY_MULTI_TENANCY_ENABLED', true),
        'isolate_credentials' => env('PASSKEY_ISOLATE_CREDENTIALS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Monitoring and observability settings.
    |
    */
    'monitoring' => [
        'log_failures' => env('PASSKEY_LOG_FAILURES', true),
        'log_successes' => env('PASSKEY_LOG_SUCCESSES', true),
        'metrics_enabled' => env('PASSKEY_METRICS_ENABLED', true),
    ],
];
