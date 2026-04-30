<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CatCRM Vet & Grooming Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the veterinary and grooming CRM module.
    | Includes vaccination schedules, reminder settings, and integration options.
    |
    */

    'vaccination' => [
        /*
         * WSAVA 2024 Compliance Settings
         */
        'wsava_2024_compliant' => env('VET_WSAVA_2024_COMPLIANT', true),

        /*
         * Default vaccination intervals (in days) for different vaccine types
         * Can be overridden per tenant or per clinic
         */
        'intervals' => [
            'rabies' => 365, // 1 year - mandatory by Russian law
            'core_dhp' => 1095, // 3 years after first booster
            'core_fpv' => 1095, // 3 years after first booster
            'core_fcv_fhv1' => 1095, // 3 years after first booster
            'leptospirosis' => 365,
            'borrelia' => 365,
            'felv' => 365,
            'kennel_cough' => 365,
        ],

        /*
         * Reminder settings
         */
        'reminders' => [
            'enabled' => env('VET_VACCINATION_REMINDERS_ENABLED', true),
            'days_before_due' => [30, 14, 7, 1], // Send reminders at these intervals
            'overdue_reminder_days' => [1, 7, 30], // Send overdue reminders at these intervals
        ],
    ],

    'medical_records' => [
        /*
         * Medical data encryption settings
         * All medical data should be encrypted at rest per 152-ФЗ
         */
        'encryption_enabled' => env('VET_MEDICAL_ENCRYPTION_ENABLED', true),
        'encryption_key' => env('VET_MEDICAL_ENCRYPTION_KEY'),

        /*
         * Document storage settings
         */
        'storage_disk' => env('VET_MEDICAL_STORAGE_DISK', 's3'),
        'max_file_size_mb' => env('VET_MEDICAL_MAX_FILE_SIZE_MB', 50),

        /*
         * Allowed document types
         */
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/tiff',
            'application/dicom',
        ],
    ],

    'grooming' => [
        /*
         * Photo settings
         */
        'photos' => [
            'max_before_photos' => 10,
            'max_after_photos' => 10,
            'storage_disk' => env('GROOMING_PHOTO_STORAGE_DISK', 's3'),
            'auto_compress' => env('GROOMING_PHOTO_AUTO_COMPRESS', true),
            'compress_quality' => 85,
        ],

        /*
         * Service duration estimates (in minutes)
         */
        'durations' => [
            'bath' => 30,
            'haircut' => 60,
            'trimming' => 45,
            'nail_trimming' => 15,
            'ear_cleaning' => 15,
            'teeth_cleaning' => 20,
            'spa' => 90,
            'full_grooming' => 120,
        ],

        /*
         * Behavior tracking
         */
        'behavior_tracking' => [
            'enabled' => true,
            'require_notes_for_aggressive' => true,
            'alert_groomers_for_history' => true,
        ],
    ],

    'integration' => [
        /*
         * Payment integration
         */
        'payment' => [
            'enabled' => env('VET_PAYMENT_INTEGRATION_ENABLED', true),
            'auto_invoice' => env('VET_AUTO_INVOICE', true),
        ],

        /*
         * Loyalty program integration
         */
        'loyalty' => [
            'enabled' => env('VET_LOYALTY_INTEGRATION_ENABLED', true),
            'points_per_vaccination' => env('VET_LOYALTY_POINTS_VACCINATION', 100),
            'points_per_grooming' => env('VET_LOYALTY_POINTS_GROOMING', 50),
        ],

        /*
         * Notification channels
         */
        'notifications' => [
            'channels' => ['database', 'push'], // Available: database, push, sms, email, telegram
            'sms_enabled' => env('VET_SMS_ENABLED', false),
            'email_enabled' => env('VET_EMAIL_ENABLED', true),
            'telegram_enabled' => env('VET_TELEGRAM_ENABLED', false),
        ],
    ],

    'compliance' => [
        /*
         * Russian Federation compliance settings
         */
        'russia' => [
            'rabies_mandatory' => true,
            'require_veterinary_license' => true,
            'audit_log_enabled' => true,
            'data_retention_years' => 10, // Medical records retention per Russian law
        ],

        /*
         * Audit logging
         */
        'audit' => [
            'enabled' => env('VET_AUDIT_LOG_ENABLED', true),
            'log_medical_access' => true,
            'log_vaccination_changes' => true,
            'log_document_access' => true,
        ],
    ],
];
