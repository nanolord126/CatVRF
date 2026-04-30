<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Zero-Duplicate Contact Isolation
    |--------------------------------------------------------------------------
    |
    | Enforces one email/phone = one profile (client OR business).
    | Contacts are hashed for 152-ФZ / GDPR compliance.
    |
    */

    'strict_isolation' => env('PROTECTION_STRICT_ISOLATION', true),

    /*
    |--------------------------------------------------------------------------
    | Contact Hashing
    |--------------------------------------------------------------------------
    |
    | Pepper for Argon2id hashing of email/phone (152-ФZ compliance).
    | Change this in production to a strong random string.
    |
    */

    'contact_hash_pepper' => env('PROTECTION_CONTACT_HASH_PEPPER', 'catvrf-2026-production-pepper-change-me'),

    /*
    |--------------------------------------------------------------------------
    | Contact Retention
    |--------------------------------------------------------------------------
    |
    | Days to retain soft-deleted contacts before reuse (152-ФZ compliance).
    | Default: 90 days
    |
    */

    'contact_retention_days' => env('PROTECTION_CONTACT_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Role Limits
    |--------------------------------------------------------------------------
    |
    | Maximum number of roles per user and per tenant to prevent privilege
    | escalation and ensure proper access control.
    |
    */

    'max_roles_per_user' => env('PROTECTION_MAX_ROLES_PER_USER', 5),

    'max_owners_per_tenant' => env('PROTECTION_MAX_OWNERS_PER_TENANT', 3),

    'max_staff_per_tenant' => env('PROTECTION_MAX_STAFF_PER_TENANT', 10),

    /*
    |--------------------------------------------------------------------------
    | Profile Transition
    |--------------------------------------------------------------------------
    |
    | Settings for client <-> business profile transitions.
    | Transitions require KYB flow with manual moderation.
    |
    */

    'profile_transition' => [
        'enabled' => env('PROTECTION_PROFILE_TRANSITION_ENABLED', false),
        'cooldown_hours' => env('PROTECTION_PROFILE_TRANSITION_COOLDOWN', 72),
        'require_kyb' => env('PROTECTION_PROFILE_TRANSITION_REQUIRE_KYB', true),
        'require_moderation' => env('PROTECTION_PROFILE_TRANSITION_REQUIRE_MODERATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Change Cooldown
    |--------------------------------------------------------------------------
    |
    | Cooldown period for email/phone changes to prevent abuse.
    | Default: 72 hours
    |
    */

    'contact_change_cooldown_hours' => env('PROTECTION_CONTACT_CHANGE_COOLDOWN', 72),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting for contact availability checks to prevent enumeration.
    |
    */

    'rate_limiting' => [
        'contact_check_per_minute' => env('PROTECTION_CONTACT_CHECK_RATE_LIMIT', 3),
        'contact_check_per_hour' => env('PROTECTION_CONTACT_CHECK_RATE_LIMIT_HOUR', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Enable audit logging for all isolation violations and enforcement actions.
    |
    */

    'audit' => [
        'log_violations' => env('PROTECTION_AUDIT_VIOLATIONS', true),
        'log_enforcements' => env('PROTECTION_AUDIT_ENFORCEMENTS', true),
        'log_to_clickhouse' => env('PROTECTION_AUDIT_CLICKHOUSE', false),
        'clickhouse_table' => env('PROTECTION_AUDIT_CLICKHOUSE_TABLE', 'protection_events'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Send notifications to owners when role limits are approached/exceeded.
    |
    */

    'notifications' => [
        'role_limit_warning_threshold' => env('PROTECTION_ROLE_LIMIT_WARNING_THRESHOLD', 0.8), // 80%
        'notify_owners_on_limit_exceeded' => env('PROTECTION_NOTIFY_OWNERS_LIMIT_EXCEEDED', true),
        'notify_support_on_violations' => env('PROTECTION_NOTIFY_SUPPORT_VIOLATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache TTL for contact availability and role count checks.
    |
    */

    'cache' => [
        'contact_availability_ttl_minutes' => env('PROTECTION_CACHE_CONTACT_AVAILABILITY_TTL', 60),
        'role_count_ttl_minutes' => env('PROTECTION_CACHE_ROLE_COUNT_TTL', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fraud Integration
    |--------------------------------------------------------------------------
    |
    | Integration with FraudControlService for duplicate attempt detection.
    |
    */

    'fraud' => [
        'log_duplicate_attempts' => env('PROTECTION_FRAUD_LOG_DUPLICATES', true),
        'block_duplicate_attempts' => env('PROTECTION_FRAUD_BLOCK_DUPLICATES', true),
        'duplicate_attempt_score_increase' => env('PROTECTION_FRAUD_DUPLICATE_SCORE_INCREASE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | 152-ФZ / GDPR Compliance
    |--------------------------------------------------------------------------
    |
    | Settings for compliance with Russian privacy law and GDPR.
    |
    */

    'compliance' => [
        'hash_contacts' => env('PROTECTION_COMPLIANCE_HASH_CONTACTS', true),
        'anonymize_before_llm' => env('PROTECTION_COMPLIANCE_ANONYMIZE_LLM', true),
        'require_consent' => env('PROTECTION_COMPLIANCE_REQUIRE_CONSENT', true),
        'retention_policy_days' => env('PROTECTION_COMPLIANCE_RETENTION_DAYS', 365),
    ],
];
