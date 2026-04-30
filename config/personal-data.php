<?php

declare(strict_types=1);

/**
 * Personal Data Configuration for 152-FZ Compliance
 * 
 * This configuration file defines settings for personal data processing,
 * localization, protection levels, and retention periods in accordance
 * with Russian Federal Law No. 152-FZ "On Personal Data".
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Operator Information
    |--------------------------------------------------------------------------
    |
    | Information about the personal data operator as required by 152-FZ.
    | This information is displayed in consent forms and privacy policy.
    |
    */

    'operator_name' => env('PERSONAL_DATA_OPERATOR_NAME', 'CatVRF LLC'),
    'operator_address' => env('PERSONAL_DATA_OPERATOR_ADDRESS', 'Russia, Moscow, 123456'),
    'operator_contact' => env('PERSONAL_DATA_OPERATOR_CONTACT', 'privacy@catvrf.ru'),
    'operator_inn' => env('PERSONAL_DATA_OPERATOR_INN', ''),
    'operator_ogrn' => env('PERSONAL_DATA_OPERATOR_OGRN', ''),

    /*
    |--------------------------------------------------------------------------
    | Data Localization
    |--------------------------------------------------------------------------
    |
    | 152-FZ requires that personal data of Russian citizens be stored
    | on servers located within the Russian Federation or in systems
    | that provide equivalent protection.
    |
    */

    'localization' => [
        'enabled' => env('PERSONAL_DATA_LOCALIZATION_ENABLED', true),
        'region' => env('PERSONAL_DATA_REGION', 'RU'),
        'database_location' => env('PERSONAL_DATA_DB_LOCATION', 'Russia'),
        'backup_location' => env('PERSONAL_DATA_BACKUP_LOCATION', 'Russia'),
        'cross_border_transfer_allowed' => env('PERSONAL_DATA_CROSS_BORDER_ALLOWED', false),
        'cross_border_approval_number' => env('PERSONAL_DATA_CROSS_BORDER_APPROVAL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Protection Level (ИСПДн) по Постановлению №1119 и ФСТЭК №21
    |--------------------------------------------------------------------------
    |
    | Уровень защищённости ИСПДн определяется по Постановлению Правительства РФ №1119.
    | Для CatVRF:
    | - Биометрические ПДн → УЗ-3 (объём > 100 000 субъектов)
    | - Иные ПДн → УЗ-2 или УЗ-3 (зависит от типа данных)
    |
    | ФСТЭК №21 определяет 15 групп мер защиты (организационные + технические).
    |
    | Levels: 1 (highest), 2, 3, 4 (lowest)
    */

    'protection_level' => env('PERSONAL_DATA_PROTECTION_LEVEL', '3'),

    'protection_levels' => [
        '1' => [
            'name' => 'Уровень 1 (высший)',
            'description' => 'Для систем с биометрическими данными и объёмом > 1 млн субъектов',
            'requires_certification' => true,
            'applies_to' => ['biometric', 'special_category'],
        ],
        '2' => [
            'name' => 'Уровень 2',
            'description' => 'Для систем со специальными категориями ПДн',
            'requires_certification' => true,
            'applies_to' => ['special_category'],
        ],
        '3' => [
            'name' => 'Уровень 3',
            'description' => 'Для систем с биометрическими данными (объём 100 000 - 1 млн субъектов)',
            'requires_certification' => true,
            'applies_to' => ['biometric', 'general'],
        ],
        '4' => [
            'name' => 'Уровень 4',
            'description' => 'Для систем с общими ПДн и минимальным риском',
            'requires_certification' => false,
            'applies_to' => ['general'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 15 Groups of Security Measures (ФСТЭК №21)
    |--------------------------------------------------------------------------
    |
    | Реализация 15 групп мер защиты по Приказу ФСТЭК №21 от 18.02.2013.
    | Каждая группа имеет статус реализации и ссылку на кодовый компонент.
    |
    */

    'fstec21_measures' => [
        // Организационные меры (обязательны для всех УЗ)
        '1_identification_authentication' => [
            'name' => 'Идентификация и аутентификация',
            'description' => 'Passkeys + 2FA + behavioral biometrics + userVerification для sensitive действий',
            'implemented' => true,
            'components' => ['PasskeyService', 'TwoFactorAuth', 'BehavioralBiometricsService'],
            'uz_required' => [1, 2, 3, 4],
        ],
        '2_access_control' => [
            'name' => 'Разграничение доступа',
            'description' => 'spatie/laravel-permission + RoleIsolationService + least privilege',
            'implemented' => true,
            'components' => ['SpatiePermission', 'RoleIsolationService', 'JITAccessService'],
            'uz_required' => [1, 2, 3, 4],
        ],
        '3_access_management' => [
            'name' => 'Управление доступом',
            'description' => 'RoleLimitService + InsiderThreatService мониторинг',
            'implemented' => true,
            'components' => ['RoleLimitService', 'InsiderThreatService'],
            'uz_required' => [1, 2, 3, 4],
        ],
        '4_action_registration' => [
            'name' => 'Регистрация и учёт действий',
            'description' => 'Immutable audit в ClickHouse для каждого доступа к ПДн',
            'implemented' => true,
            'components' => ['PersonalDataAccessAudit', 'ClickHouse'],
            'uz_required' => [1, 2, 3, 4],
        ],
        '5_unauthorized_access_protection' => [
            'name' => 'Защита от НСД',
            'description' => 'TenantIsolationMiddleware + global scopes + ownership checks',
            'implemented' => true,
            'components' => ['TenantIsolationMiddleware', 'GlobalScopes'],
            'uz_required' => [1, 2, 3, 4],
        ],
        '6_antivirus' => [
            'name' => 'Антивирусная защита',
            'description' => 'Серверный уровень + мониторинг файлов',
            'implemented' => true,
            'components' => ['ClamAV', 'ServerAntivirus'],
            'uz_required' => [1, 2, 3, 4],
        ],
        '7_software_updates' => [
            'name' => 'Обновление ПО',
            'description' => 'CI/CD с dependency scanning (Snyk, Dependabot)',
            'implemented' => true,
            'components' => ['GitHubActions', 'Dependabot', 'Snyk'],
            'uz_required' => [1, 2, 3, 4],
        ],
        '8_backup' => [
            'name' => 'Резервное копирование',
            'description' => 'Зашифрованные tenant-specific backups + restore testing',
            'implemented' => true,
            'components' => ['EncryptedBackupJob', 'S3/Glacier'],
            'uz_required' => [1, 2, 3, 4],
        ],
        '9_data_destruction' => [
            'name' => 'Уничтожение ПДн',
            'description' => 'PurgePersonalDataJob после отзыва consent (30 дней)',
            'implemented' => true,
            'components' => ['PurgePersonalDataJob', 'ConsentEngine'],
            'uz_required' => [1, 2, 3, 4],
        ],
        '10_action_control' => [
            'name' => 'Контроль за действиями',
            'description' => 'Behavioral Biometrics + FraudControl + alerts',
            'implemented' => true,
            'components' => ['BehavioralBiometricsService', 'FraudControlService'],
            'uz_required' => [1, 2, 3, 4],

        ],
        // Технические меры (по уровню УЗ-3)
        '11_encryption' => [
            'name' => 'Шифрование',
            'description' => 'Column-level encryption + at-rest encryption + key rotation',
            'implemented' => true,
            'components' => ['EncryptedCast', 'AES-256-GCM', 'KeyRotation'],
            'uz_required' => [1, 2, 3],
        ],
        '12_channel_protection' => [
            'name' => 'Защита каналов',
            'description' => 'HTTPS only + HSTS + CSP + mTLS для внутренних сервисов',
            'implemented' => true,
            'components' => ['TLS 1.3', 'HSTS', 'mTLS'],
            'uz_required' => [1, 2, 3],
        ],
        '13_security_tools' => [
            'name' => 'Средства защиты информации',
            'description' => 'WAF (Cloudflare) + database firewall + IDS/IPS',
            'implemented' => true,
            'components' => ['CloudflareWAF', 'PostgreSQLFirewall', 'Suricata'],
            'uz_required' => [1, 2, 3],
        ],
        '14_network_segmentation' => [
            'name' => 'Сегментация сети',
            'description' => 'DMZ для public API + отдельные зоны для tenant DB',
            'implemented' => true,
            'components' => ['VPC', 'SecurityGroups', 'Subnets'],
            'uz_required' => [1, 2, 3],
        ],
        '15_integrity_control' => [
            'name' => 'Контроль целостности',
            'description' => 'File integrity monitoring + hash проверка конфигов',
            'implemented' => true,
            'components' => ['AIDE', 'ConfigHashCheck'],
            'uz_required' => [1, 2, 3],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention Periods
    |--------------------------------------------------------------------------
    |
    | Maximum retention periods for different types of personal data.
    | Data must be destroyed after the purpose of processing is completed
    | or after consent withdrawal (within 30 days per 152-FZ).
    |
    */

    'retention_periods' => [
        'registration_data' => env('RETENTION_REGISTRATION_DAYS', 365), // 1 year
        'order_data' => env('RETENTION_ORDER_DAYS', 1095), // 3 years (tax)
        'communication_data' => env('RETENTION_COMMUNICATION_DAYS', 180), // 6 months
        'biometric_face_id' => env('RETENTION_BIOMETRIC_FACE_DAYS', 365), // 1 year
        'biometric_liveness' => env('RETENTION_BIOMETRIC_LIVENESS_DAYS', 30), // 30 days
        'biometric_behavioral' => env('RETENTION_BIOMETRIC_BEHAVIORAL_DAYS', 90), // 90 days
        'biometric_voice' => env('RETENTION_BIOMETRIC_VOICE_DAYS', 365), // 1 year
        'kyb_verification' => env('RETENTION_KYB_DAYS', 1825), // 5 years
        'document_verification' => env('RETENTION_DOCUMENT_DAYS', 1825), // 5 years
        'long_term_storage' => env('RETENTION_LONG_TERM_DAYS', 2555), // 7 years
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Destruction
    |--------------------------------------------------------------------------
    |
    | Settings for personal data destruction after consent withdrawal
    | or when retention period expires.
    |
    */

    'destruction' => [
        'days_after_consent_withdrawal' => env('DESTRUCTION_DAYS_AFTER_WITHDRAWAL', 30),
        'destruction_method' => env('DESTRUCTION_METHOD', 'overwrite'), // 'delete' or 'overwrite'
        'generate_destruction_certificate' => env('GENERATE_DESTRUCTION_CERTIFICATE', true),
        'notify_user_on_destruction' => env('NOTIFY_USER_ON_DESTRUCTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Consent Management
    |--------------------------------------------------------------------------
    |
    | Settings for consent collection and management.
    |
    */

    'consent' => [
        'version' => env('CONSENT_VERSION', '1.0'),
        'require_explicit_consent' => env('REQUIRE_EXPLICIT_CONSENT', true),
        'biometric_requires_written_form' => env('BIOMETRIC_REQUIRES_WRITTEN', true),
        'biometric_requires_ukep' => env('BIOMETRIC_REQUIRES_UKEP', true),
        'allow_partial_consent' => env('ALLOW_PARTIAL_CONSENT', true),
        'consent_expiry_days' => env('CONSENT_EXPIRY_DAYS', null), // null = no expiry
    ],

    /*
    |--------------------------------------------------------------------------
    | Biometric Data Requirements (ст. 11 152-ФЗ)
    |--------------------------------------------------------------------------
    |
    | Специальные требования для биометрических ПДн по ст. 11 152-ФЗ:
    | - Отдельное письменное согласие (или УКЭП)
    | - Хранение в зашифрованном виде
    | - Уничтожение после отзыва/окончания цели обработки
    |
    */

    'biometric' => [
        'requires_separate_consent' => true,
        'signature_methods' => ['ukep', 'written', 'click'], // УКЭП, письменная форма, клик (для тестов)
        'storage_encrypted' => true,
        'destruction_on_withdrawal' => true,
        'destruction_days' => 30,
        'data_types' => [
            'face_id' => [
                'retention_days' => 365,
                'requires_consent' => 'biometric_face_id',
                'encryption_required' => true,
            ],
            'liveness' => [
                'retention_days' => 30,
                'requires_consent' => 'biometric_liveness',
                'encryption_required' => true,
            ],
            'behavioral' => [
                'retention_days' => 90,
                'requires_consent' => 'biometric_behavioral',
                'encryption_required' => true,
            ],
            'voice' => [
                'retention_days' => 365,
                'requires_consent' => 'biometric_voice',
                'encryption_required' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    |
    | Encryption settings for personal data at rest and in transit.
    |
    */

    'encryption' => [
        'at_rest' => [
            'enabled' => env('ENCRYPTION_AT_REST_ENABLED', true),
            'algorithm' => env('ENCRYPTION_ALGORITHM', 'AES-256-GCM'),
            'key_rotation_days' => env('KEY_ROTATION_DAYS', 90),
        ],
        'in_transit' => [
            'tls_version' => env('TLS_VERSION', '1.3'),
            'hsts_enabled' => env('HSTS_ENABLED', true),
            'hsts_max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
        ],
        'column_level' => [
            'enabled' => env('COLUMN_LEVEL_ENCRYPTION_ENABLED', true),
            'encrypted_columns' => [
                'email',
                'phone',
                'inn',
                'first_name',
                'last_name',
                'middle_name',
                'passport_number',
                'passport_series',
                'biometric_vector',
                'behavioral_profile',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Control
    |--------------------------------------------------------------------------
    |
    | Settings for JIT (Just-In-Time) access and permission checks.
    |
    */

    'access_control' => [
        'jit_access_enabled' => env('JIT_ACCESS_ENABLED', true),
        'jit_requires_passkey' => env('JIT_REQUIRES_PASSKEY', true),
        'jit_requires_2fa' => env('JIT_REQUIRES_2FA', true),
        'jit_access_duration_minutes' => env('JIT_ACCESS_DURATION_MINUTES', 60),
        'audit_all_access' => env('AUDIT_ALL_ACCESS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Settings for audit logging of personal data access.
    |
    */

    'audit' => [
        'enabled' => env('PERSONAL_DATA_AUDIT_ENABLED', true),
        'storage' => env('AUDIT_STORAGE', 'clickhouse'), // 'clickhouse' or 'database'
        'retention_days' => env('AUDIT_RETENTION_DAYS', 2555), // 7 years
        'log_anonymized_access' => env('LOG_ANONYMIZED_ACCESS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Subject Rights
    |--------------------------------------------------------------------------
    |
    | Settings for implementing data subject rights under 152-FZ.
    | Response time is 10 working days per law.
    |
    */

    'subject_rights' => [
        'access_request_response_days' => env('ACCESS_RESPONSE_DAYS', 10),
        'deletion_request_response_days' => env('DELETION_RESPONSE_DAYS', 10),
        'correction_request_response_days' => env('CORRECTION_RESPONSE_DAYS', 10),
        'export_format' => env('EXPORT_FORMAT', 'json'), // 'json', 'pdf', 'csv'
        'allow_data_portability' => env('ALLOW_DATA_PORTABILITY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Roskomnadzor Notification
    |--------------------------------------------------------------------------
    |
    | Settings for notification to Roskomnadzor (Russian data protection authority).
    |
    */

    'roskomnadzor' => [
        'registry_number' => env('ROSKOMNADZOR_REGISTRY_NUMBER', ''),
        'notification_submitted' => env('ROSKOMNADZOR_NOTIFICATION_SUBMITTED', false),
        'notification_date' => env('ROSKOMNADZOR_NOTIFICATION_DATE'),
        'breach_notification_required' => env('BREACH_NOTIFICATION_REQUIRED', true),
        'breach_notification_hours' => env('BREACH_NOTIFICATION_HOURS', 72),
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsible Person
    |--------------------------------------------------------------------------
    |
    | Information about the person responsible for personal data processing.
    |
    */

    'responsible_person' => [
        'name' => env('RESPONSIBLE_PERSON_NAME', ''),
        'position' => env('RESPONSIBLE_PERSON_POSITION', ''),
        'email' => env('RESPONSIBLE_PERSON_EMAIL', ''),
        'phone' => env('RESPONSIBLE_PERSON_PHONE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Storage
    |--------------------------------------------------------------------------
    |
    | Settings for storing compliance documents (policies, consents, etc.).
    |
    */

    'documents' => [
        'storage_path' => env('COMPLIANCE_DOCUMENTS_PATH', storage_path('app/compliance')),
        'privacy_policy_path' => env('PRIVACY_POLICY_PATH', 'public/policy/privacy-policy.pdf'),
        'consent_forms_path' => env('CONSENT_FORMS_PATH', 'public/policy/consent-forms/'),
        'destruction_certificates_path' => env('DESTRUCTION_CERTIFICATES_PATH', storage_path('app/destruction-certificates')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, consent checks are relaxed for testing purposes.
    | MUST be disabled in production.
    |
    */

    'testing_mode' => env('PERSONAL_DATA_TESTING_MODE', false),
];
