<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Voice Biometrics Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for voice biometric authentication including enrollment,
    | verification, and anti-spoofing features.
    |
    */

    'provider' => env('VOICE_BIOMETRICS_PROVIDER', 'azure'), // azure, aws, local

    'azure' => [
        'api_key' => env('AZURE_VOICE_ID_API_KEY'),
        'api_url' => env('AZURE_VOICE_ID_API_URL', 'https://westus.api.cognitive.microsoft.com/voice/v1.0'),
        'region' => env('AZURE_VOICE_ID_REGION', 'westus'),
    ],

    'aws' => [
        'api_key' => env('AWS_VOICE_ID_API_KEY'),
        'api_url' => env('AWS_VOICE_ID_API_URL', 'https://voice-id.amazonaws.com'),
        'region' => env('AWS_VOICE_ID_REGION', 'us-east-1'),
    ],

    'enrollment' => [
        'min_samples' => 3,
        'min_duration_seconds' => 5,
        'max_duration_seconds' => 30,
        'threshold' => 0.85,
    ],

    'verification' => [
        'threshold' => 0.90,
        'max_attempts' => 3,
        'lockout_duration_minutes' => 15,
    ],

    'anti_spoofing' => [
        'enabled' => true,
        'synthetic_detection_threshold' => 0.7,
        'playback_detection_threshold' => 0.7,
        'liveness_check' => true,
    ],

    'encryption' => [
        'enabled' => true,
        'key' => env('VOICE_BIOMETRICS_ENCRYPTION_KEY'),
        'algorithm' => 'AES-256-GCM',
    ],

    'storage' => [
        'disk' => 'secure',
        'path' => 'voice-profiles',
    ],

    'quality' => [
        'min_quality_score' => 0.6,
        'check_snr' => true,
        'check_clarity' => true,
        'check_background_noise' => true,
    ],
];
