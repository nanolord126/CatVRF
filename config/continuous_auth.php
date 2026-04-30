<?php

declare(strict_types=1);

return [
    // Enable/disable continuous authentication
    'enabled' => env('CONTINUOUS_AUTH_ENABLED', true),

    // Scoring interval (seconds)
    'scoring_interval' => env('CONTINUOUS_AUTH_SCORING_INTERVAL', 300), // 5 minutes

    // Risk thresholds
    'risk_thresholds' => [
        'medium' => env('CONTINUOUS_AUTH_THRESHOLD_MEDIUM', 30),
        'high' => env('CONTINUOUS_AUTH_THRESHOLD_HIGH', 60),
        'critical' => env('CONTINUOUS_AUTH_THRESHOLD_CRITICAL', 80),
    ],

    // Trust decay
    'trust_decay' => [
        'enabled' => env('CONTINUOUS_AUTH_TRUST_DECAY_ENABLED', true),
        'decay_rate' => env('CONTINUOUS_AUTH_DECAY_RATE', 0.1), // per hour
        'reset_on_reauth' => true,
        'reauth_threshold' => env('CONTINUOUS_AUTH_REAUTH_THRESHOLD', 50), // trust score below this requires re-auth
    ],

    // Behavioral data collection
    'behavioral_collection' => [
        'typing_pattern' => true,
        'mouse_dynamics' => true,
        'touch_gestures' => true,
        'device_fingerprint' => true,
    ],

    // Data retention
    'retention' => [
        'behavioral_data_days' => env('CONTINUOUS_AUTH_RETENTION_BEHAVIORAL', 30),
        'risk_scores_days' => env('CONTINUOUS_AUTH_RETENTION_RISK_SCORES', 90),
    ],

    // Challenge types
    'challenges' => [
        'medium_risk' => 'passkey_only',
        'high_risk' => 'passkey_liveness',
        'critical_risk' => 'passkey_liveness_voice',
    ],

    // Shadow mode (collect data without enforcement)
    'shadow_mode' => env('CONTINUOUS_AUTH_SHADOW_MODE', false),
];
