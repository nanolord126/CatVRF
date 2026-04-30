<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Privacy & Consent Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for consent management, PII masking, data deletion,
    | and data retention policies (152-ФЗ / GDPR compliance).
    |
    */

    'consent' => [
        'version' => env('CONSENT_VERSION', '1.0'),
        'auto_check' => true,
        'require_for_registration' => ['medical', 'payment'],
        'check_interval_days' => 30,
    ],

    'data_retention' => [
        'biometric_data_days' => 90,
        'behavioral_data_days' => 180,
        'location_data_days' => 90,
        'marketing_data_days' => 2555, // 7 years
        'analytics_data_days' => 730, // 2 years
        'audit_logs_days' => 2555, // 7 years
    ],

    'data_deletion' => [
        'enabled' => true,
        'default_sla_hours' => 72,
        'max_sla_hours' => 168, // 7 days
        'require_verification' => true,
    ],

    'pii_masking' => [
        'enabled' => true,
        'mask_in_logs' => true,
        'mask_in_api_responses' => false,
        'masking_char' => '*',
    ],

    'gdpr' => [
        'enabled' => true,
        'dpo_email' => env('GDPR_DPO_EMAIL', 'dpo@catvrf.ru'),
        'data_protection_officer' => env('GDPR_DPO_NAME'),
    ],

    'fz152' => [
        'enabled' => true,
        'data_localization' => true,
        'cross_border_transfer' => false,
    ],
];
