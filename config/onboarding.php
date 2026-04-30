<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | DaData Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for DaData API (INN validation and party lookup).
    |
    */
    'dadata' => [
        'api_key' => env('DADATA_API_KEY'),
        'secret_key' => env('DADATA_SECRET_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Identity Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI-based identity verification (FIO + photo).
    | Supported providers: faceio, aws_rekognition, yandex_vision, mock
    |
    */
    'ai_identity' => [
        'provider' => env('AI_IDENTITY_PROVIDER', 'mock'),
        'api_key' => env('AI_IDENTITY_API_KEY'),
        'api_endpoint' => env('AI_IDENTITY_API_ENDPOINT'),

        'thresholds' => [
            'auto_approve' => (float) env('AI_IDENTITY_AUTO_APPROVE_THRESHOLD', 0.85),
            'pending_review' => (float) env('AI_IDENTITY_PENDING_THRESHOLD', 0.65),
        ],

        'rate_limit' => [
            'max_attempts_per_hour' => (int) env('AI_IDENTITY_MAX_ATTEMPTS_PER_HOUR', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Verification Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for document OCR and verification.
    | Supported providers: tesseract, aws_textract, yandex_ocr, mock
    |
    */
    'document_verification' => [
        'provider' => env('DOCUMENT_VERIFICATION_PROVIDER', 'mock'),
        'api_key' => env('DOCUMENT_VERIFICATION_API_KEY'),
        'api_endpoint' => env('DOCUMENT_VERIFICATION_API_ENDPOINT'),

        'thresholds' => [
            'min_validity_score' => (float) env('DOCUMENT_VERIFICATION_MIN_SCORE', 0.7),
        ],

        'storage' => [
            'disk' => env('DOCUMENT_STORAGE_DISK', 'secure'),
            'path' => env('DOCUMENT_STORAGE_PATH', 'documents'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Onboarding Flow Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for onboarding flows and requirements.
    |
    */
    'flows' => [
        'business_registration' => [
            'require_documents' => env('BUSINESS_REGISTRATION_REQUIRE_DOCUMENTS', true),
            'require_selfie' => env('BUSINESS_REGISTRATION_REQUIRE_SELFIE', true),
            'require_identity_verification' => env('BUSINESS_REGISTRATION_REQUIRE_IDENTITY', true),
            'auto_approve_branches' => env('BUSINESS_REGISTRATION_AUTO_APPROVE_BRANCHES', true),
        ],

        'user_verification' => [
            'require_consent' => env('USER_VERIFICATION_REQUIRE_CONSENT', true),
            'require_photo' => env('USER_VERIFICATION_REQUIRE_PHOTO', true),
            'optional_for_b2c' => env('USER_VERIFICATION_OPTIONAL_B2C', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Moderation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for manual moderation queues and notifications.
    |
    */
    'moderation' => [
        'enabled' => env('MODERATION_ENABLED', true),
        'notification_channels' => explode(',', env('MODERATION_NOTIFICATION_CHANNELS', 'mail,slack')),
        'auto_approve_threshold' => (float) env('MODERATION_AUTO_APPROVE_THRESHOLD', 0.95),
    ],
];
