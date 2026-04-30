<?php

/**
 * ISPDn Configuration (Information System of Personal Data)
 * 
 * Configuration for CatVRF compliance with 152-FZ and FSTEC #21.
 * Protection Level: УЗ-3 (for biometric data)
 * 
 * Reference:
 * - Federal Law 152-FZ
 * - Government Decree No. 1119
 * - FSTEC Order No. 21
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Protection Level (Уровень защищённости)
    |--------------------------------------------------------------------------
    |
    | УЗ-1: < 1000 subjects, no biometric data
    | УЗ-2: 1000-100000 subjects, no biometric data
    | УЗ-3: > 100000 subjects OR biometric data
    | УЗ-4: State information systems
    |
    */
    'protection_level' => env('ISPDN_PROTECTION_LEVEL', 3),

    /*
    |--------------------------------------------------------------------------
    | Operator Information
    |--------------------------------------------------------------------------
    */
    'operator' => [
        'name' => env('ISPDN_OPERATOR_NAME', 'CatVRF LLC'),
        'inn' => env('ISPDN_OPERATOR_INN', ''),
        'ogrn' => env('ISPDN_OPERATOR_OGRN', ''),
        'address' => env('ISPDN_OPERATOR_ADDRESS', 'Russia, Moscow'),
        'contact' => env('ISPDN_OPERATOR_CONTACT', 'privacy@catvrf.ru'),
        'phone' => env('ISPDN_OPERATOR_PHONE', ''),
        'registry_number' => env('ROSKOMNADZOR_REGISTRY_NUMBER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Subjects Count
    |--------------------------------------------------------------------------
    */
    'subjects_count' => env('ISPDN_SUBJECTS_COUNT', 100000),
    'tenants_count' => env('ISPDN_TENANTS_COUNT', 1000),

    /*
    |--------------------------------------------------------------------------
    | Data Localization
    |--------------------------------------------------------------------------
    |
    | 152-FZ Requirement: Personal data must be stored in Russian Federation
    |
    */
    'data_localization' => env('ISPDN_DATA_LOCALIZATION', 'russia'),

    /*
    |--------------------------------------------------------------------------
    | Data Categories
    |--------------------------------------------------------------------------
    */
    'data_categories' => [
        'personal_data' => [
            'full_name',
            'email',
            'phone',
            'address',
            'date_of_birth',
            'gender',
        ],
        'biometric_data' => [
            'face_id',
            'face_vector',
            'liveness_data',
            'behavioral_patterns',
            'keystroke_dynamics',
            'mouse_patterns',
            'voice_template',
        ],
        'special_categories' => [
            'kyb_documents',
            'director_passport',
            'inn',
            'ogrn',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | FSTEC #21 Protection Measures (15 Groups)
    |--------------------------------------------------------------------------
    |
    | Reference: Order of FSTEC No. 21 dated 18.02.2013
    |
    */
    'fstec21_measures' => [
        'group_1_identification' => [
            'name' => 'Идентификация и аутентификация',
            'status' => 'implemented',
            'components' => ['Passkeys', '2FA', 'Behavioral Biometrics', 'userVerification'],
        ],
        'group_2_access_control' => [
            'name' => 'Разграничение доступа',
            'status' => 'implemented',
            'components' => ['Spatie Permission', 'RoleIsolationService', 'Masked data for staff'],
        ],
        'group_3_access_management' => [
            'name' => 'Управление доступом',
            'status' => 'implemented',
            'components' => ['RoleLimitService', 'InsiderThreatService', 'JIT access'],
        ],
        'group_4_audit' => [
            'name' => 'Регистрация и учёт действий',
            'status' => 'implemented',
            'components' => ['PersonalDataAccessAudit', 'ClickHouse immutable logs'],
        ],
        'group_5_protection' => [
            'name' => 'Защита от НСД',
            'status' => 'implemented',
            'components' => ['TenantIsolationMiddleware', 'Global scopes', 'ExportGuard'],
        ],
        'group_6_antivirus' => [
            'name' => 'Антивирусная защита',
            'status' => 'implemented',
            'components' => ['ClamAV', 'ServerAntivirus'],
        ],
        'group_7_updates' => [
            'name' => 'Обновление ПО',
            'status' => 'implemented',
            'components' => ['GitHub Actions', 'Dependabot', 'Snyk'],
        ],
        'group_8_backup' => [
            'name' => 'Резервное копирование',
            'status' => 'implemented',
            'components' => ['EncryptedBackupJob', 'S3/Glacier'],
        ],
        'group_9_destruction' => [
            'name' => 'Уничтожение ПДн',
            'status' => 'implemented',
            'components' => ['PurgePersonalDataJob', 'ConsentEngine'],
        ],
        'group_10_monitoring' => [
            'name' => 'Контроль за действиями',
            'status' => 'implemented',
            'components' => ['BehavioralBiometrics', 'FraudControl'],
        ],
        'group_11_encryption' => [
            'name' => 'Шифрование',
            'status' => 'implemented',
            'components' => ['EncryptedCast', 'AES-256-GCM', 'Key rotation'],
        ],
        'group_12_channels' => [
            'name' => 'Защита каналов',
            'status' => 'implemented',
            'components' => ['TLS 1.3', 'HSTS', 'mTLS'],
        ],
        'group_13_tools' => [
            'name' => 'Средства защиты информации',
            'status' => 'implemented',
            'components' => ['Cloudflare WAF', 'PostgreSQL Firewall', 'Suricata'],
        ],
        'group_14_segmentation' => [
            'name' => 'Сегментация сети',
            'status' => 'implemented',
            'components' => ['VPC', 'Security Groups', 'Subnets'],
        ],
        'group_15_integrity' => [
            'name' => 'Контроль целостности',
            'status' => 'implemented',
            'components' => ['AIDE', 'ConfigHashCheck'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    */
    'encryption' => [
        'enabled' => env('ISPDN_ENCRYPTION_ENABLED', true),
        'algorithm' => 'AES-256-GCM',
        'key_rotation_days' => env('ISPDN_KEY_ROTATION_DAYS', 90),
        
        'column_level' => [
            'enabled' => env('ISPDN_COLUMN_ENCRYPTION_ENABLED', true),
            'encrypted_columns' => [
                'email',
                'phone',
                'inn',
                'passport_number',
                'passport_series',
                'biometric_face_vector',
                'biometric_voice_template',
                'behavioral_profile',
            ],
        ],
        
        'at_rest' => [
            'enabled' => env('ISPDN_AT_REST_ENCRYPTION_ENABLED', true),
            'disk_encryption' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Biometric Data Settings
    |--------------------------------------------------------------------------
    */
    'biometric' => [
        'requires_separate_consent' => true,
        'signature_methods' => ['ukep', 'written', 'click'],
        'storage_encrypted' => true,
        'destruction_days' => 30, // 152-FZ requirement
        'retention_days' => [
            'face_id' => 365,
            'liveness' => 30,
            'behavioral' => 90,
            'voice' => 365,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Consent Settings
    |--------------------------------------------------------------------------
    */
    'consent' => [
        'version' => '1.0',
        'require_enhanced_form' => [
            'biometric_face_id' => true,
            'biometric_liveness' => true,
            'biometric_behavioral' => true,
            'biometric_voice' => true,
            'kyb_verification' => true,
        ],
        'retention_days' => [
            'registration' => 365,
            'orders' => 1095,
            'communications' => 180,
            'long_term_storage' => 2555,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention Periods (days)
    |--------------------------------------------------------------------------
    */
    'retention_periods' => [
        'email' => 365,
        'phone' => 365,
        'full_name' => 365,
        'address' => 1095,
        'inn' => 1825,
        'passport' => 1825,
        'biometric_face_vector' => 365,
        'biometric_voice_template' => 365,
        'behavioral_profile' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Settings
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'use_clickhouse' => env('AUDIT_USE_CLICKHOUSE', true),
        'use_fallback' => env('AUDIT_USE_FALLBACK', true),
        'retention_days' => 2555, // 7 years
        'log_all_access' => true,
        'log_biometric_collection' => true,
        'log_consent_changes' => true,
        'log_data_destruction' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Destruction Settings
    |--------------------------------------------------------------------------
    */
    'destruction' => [
        'days_after_consent_withdrawal' => 30, // 152-FZ requirement
        'anonymize_before_deletion' => true,
        'verify_destruction' => true,
        'create_destruction_act' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Threat Model Settings
    |--------------------------------------------------------------------------
    */
    'threat_model' => [
        'auto_sync_from_bdu' => env('THREAT_MODEL_AUTO_SYNC', true),
        'sync_interval_days' => env('THREAT_MODEL_SYNC_DAYS', 30),
        'bdu_url' => 'https://bdu.fstec.ru/',
        'include_catvrf_specific' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsible Person
    |--------------------------------------------------------------------------
    */
    'responsible_person' => [
        'name' => env('ISPDN_RESPONSIBLE_NAME', ''),
        'position' => env('ISPDN_RESPONSIBLE_POSITION', 'Information Security Officer'),
        'email' => env('ISPDN_RESPONSIBLE_EMAIL', ''),
        'phone' => env('ISPDN_RESPONSIBLE_PHONE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Paths
    |--------------------------------------------------------------------------
    */
    'documentation' => [
        'privacy_policy' => base_path('docs/compliance/152-fz/privacy_policy_template.md'),
        'consent_form' => base_path('docs/compliance/152-fz/consent_form_template.md'),
        'biometric_consent' => base_path('docs/compliance/152-fz/biometric_consent_form_template.md'),
        'protection_level_act' => base_path('docs/compliance/152-fz/protection_level_act_template.md'),
        'audit_act' => base_path('docs/compliance/152-fz/audit_act_template.md'),
        'regulation' => base_path('docs/compliance/152-fz/protection_regulation_template.md'),
        'threat_model' => base_path('docs/compliance/152-fz/threat_model.md'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, skips some compliance checks for development/testing.
    | NEVER enable in production!
    |
    */
    'testing_mode' => env('ISPDN_TESTING_MODE', false),
];
