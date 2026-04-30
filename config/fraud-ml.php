<?php

declare(strict_types=1);

/**
 * Fraud ML Configuration
 * CANON 2026 - Production Ready
 *
 * Multi-layer fraud detection system configuration:
 * - Layer 1: Feature Extraction (Behavioral + Device + Geo + Transaction)
 * - Layer 2: ML Models (XGBoost + Isolation Forest + LSTM Ensemble)
 * - Layer 3: Risk Scoring & Decision Engine
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Extraction Configuration
    |--------------------------------------------------------------------------
    */
    'features' => [
        'behavioral' => [
            'enabled' => env('FRAUD_ML_BEHAVIORAL_ENABLED', true),
            'weight' => 0.35, // 35% of total score
            'min_samples_for_profile' => 5,
            'similarity_threshold' => 0.75,
            'anomaly_threshold' => 0.40,
        ],
        'device' => [
            'enabled' => env('FRAUD_ML_DEVICE_ENABLED', true),
            'weight' => 0.20, // 20% of total score
            'fingerprint_ttl_days' => 30,
        ],
        'geo' => [
            'enabled' => env('FRAUD_ML_GEO_ENABLED', true),
            'weight' => 0.15, // 15% of total score
            'vpn_detection_enabled' => true,
            'residential_proxy_detection_enabled' => true,
            'territory_compliance_enabled' => true,
        ],
        'transaction' => [
            'enabled' => env('FRAUD_ML_TRANSACTION_ENABLED', true),
            'weight' => 0.20, // 20% of total score
            'velocity_windows' => [
                '5min' => 5,
                '1h' => 10,
                '24h' => 20,
            ],
        ],
        'profile' => [
            'enabled' => env('FRAUD_ML_PROFILE_ENABLED', true),
            'weight' => 0.10, // 10% of total score
            'history_days' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ML Ensemble Configuration
    |--------------------------------------------------------------------------
    */
    'ensemble' => [
        'enabled' => env('FRAUD_ML_ENSEMBLE_ENABLED', true),
        'weights' => [
            'xgboost' => 0.5,           // Supervised - primary model
            'isolation_forest' => 0.3,  // Unsupervised - anomaly detection
            'lstm' => 0.2,              // Sequential - session patterns
        ],
        'models' => [
            'xgboost' => [
                'enabled' => true,
                'model_path' => storage_path('models/fraud/xgboost_v1.joblib'),
                'fallback_to_rules' => true,
            ],
            'isolation_forest' => [
                'enabled' => true,
                'model_path' => storage_path('models/fraud/isolation_forest_v1.joblib'),
                'contamination' => 0.1,
            ],
            'lstm' => [
                'enabled' => true,
                'model_path' => storage_path('models/fraud/lstm_v1.pt'),
                'sequence_length' => 10,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Thresholds per Operation Type
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'login' => [
            'low' => 0.4,     // 0.4-0.7: review
            'medium' => 0.7,  // 0.7-0.85: challenge (soft Cooldown)
            'high' => 0.85,   // >= 0.85: block
        ],
        'register' => [
            'low' => 0.3,
            'medium' => 0.6,
            'high' => 0.8,
        ],
        'kyb' => [
            'low' => 0.3,
            'medium' => 0.65,
            'high' => 0.85,
        ],
        'payout' => [
            'low' => 0.3,
            'medium' => 0.6,
            'high' => 0.8,
        ],
        'bank_change' => [
            'low' => 0.3,
            'medium' => 0.6,
            'high' => 0.8,
        ],
        'payment_init' => [
            'low' => 0.4,
            'medium' => 0.7,
            'high' => 0.85,
        ],
        'default' => [
            'low' => 0.4,
            'medium' => 0.7,
            'high' => 0.85,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Decision Actions
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'allow' => [
            'cooldown' => false,
            'split_key_invalidation' => false,
            'notification' => false,
        ],
        'review' => [
            'cooldown' => false,
            'split_key_invalidation' => false,
            'notification' => true,
            'notification_level' => 'info',
        ],
        'challenge' => [
            'cooldown' => true,
            'cooldown_hours' => 1,
            'cooldown_action' => 'SOFT_CHALLENGE',
            'split_key_invalidation' => false,
            'notification' => true,
            'notification_level' => 'warning',
        ],
        'block' => [
            'cooldown' => true,
            'cooldown_hours' => 24,
            'cooldown_action' => 'FRAUD_BLOCK',
            'split_key_invalidation' => true,
            'notification' => true,
            'notification_level' => 'critical',
            'logout_all_sessions' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | VPN Handling (Precision Tuning)
    |--------------------------------------------------------------------------
    | VPN alone is NOT enough to block - requires combination with other signals
    */
    'vpn_handling' => [
        'vpn_only_penalty' => -0.15,  // Reduce score for VPN without behavioral anomaly
        'corporate_vpn_whitelist' => true,
        'corporate_vpn_bonus' => -0.20,
        'vpn_with_behavioral_anomaly_boost' => 0.30,
        'vpn_with_geo_mismatch_boost' => 0.30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'inference_timeout_ms' => 50,  // Target < 50ms
        'enable_caching' => true,
        'cache_ttl_seconds' => 3600,
        'async_inference' => false,  // Set to true for Python microservice
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Store Configuration
    |--------------------------------------------------------------------------
    */
    'feature_store' => [
        'redis_enabled' => true,
        'redis_prefix' => 'fraudml:features:',
        'redis_ttl_seconds' => 86400,  // 24 hours
        'clickhouse_enabled' => true,
        'clickhouse_table' => 'fraud_features_online',
    ],

    /*
    |--------------------------------------------------------------------------
    | Explainability (SHAP)
    |--------------------------------------------------------------------------
    */
    'explainability' => [
        'enabled' => true,
        'shap_threshold' => 0.7,  // Only explain for high-risk predictions
        'top_features_count' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Telemetry
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => true,
        'log_predictions' => true,
        'log_features' => false,  // Set to true for debugging only
        'track_drift' => true,
        'drift_check_interval_hours' => 24,
        'alert_on_drift' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Drift Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Comprehensive drift monitoring for ML models:
    | - Data Drift (Feature Drift): Changes in input feature distributions
    | - Concept Drift (Label Drift): Changes in feature -> target relationship
    | - Model Drift (Prediction Drift): Changes in prediction distribution
    |
    | Thresholds (production standards from Ozon/Amazon):
    | - PSI: < 0.1 (OK), 0.1-0.25 (WARNING), > 0.25 (CRITICAL)
    | - KS p-value: > 0.05 (OK), ≤ 0.05 (DRIFT DETECTED)
    | - Accuracy Decay: < 5% (OK), 5-8% (WARNING), > 8% (CRITICAL)
    */
    'drift' => [
        'enabled' => env('FRAUD_ML_DRIFT_ENABLED', true),
        'real_time_enabled' => env('FRAUD_ML_DRIFT_REALTIME_ENABLED', true),
        'daily_analysis_enabled' => env('FRAUD_ML_DRIFT_DAILY_ENABLED', true),

        // Real-time monitoring (every prediction)
        'real_time' => [
            'sample_window_size' => 10000,  // Last 10K predictions for real-time drift
            'check_interval_seconds' => 300,  // Check every 5 minutes
            'overhead_threshold_ms' => 100,  // Max 100ms overhead
        ],

        // Daily analysis (full window comparison)
        'daily' => [
            'reference_window_days' => 7,  // 7 days baseline
            'current_window_days' => 1,  // Last 24h for comparison
            'scheduled_time' => '02:00',  // Run at 2 AM UTC
        ],

        // Thresholds for different drift types
        'thresholds' => [
            'data_drift' => [
                'psi_warning' => 0.1,
                'psi_critical' => 0.25,
                'ks_alpha_warning' => 0.05,
                'ks_alpha_critical' => 0.01,
                'js_warning' => 0.1,
                'js_critical' => 0.3,
            ],
            'concept_drift' => [
                'accuracy_decay_warning' => 0.05,  // 5%
                'accuracy_decay_critical' => 0.08,  // 8%
                'f1_decay_warning' => 0.05,
                'f1_decay_critical' => 0.08,
            ],
            'model_drift' => [
                'prediction_distribution_shift' => 0.15,
                'score_variance_change' => 0.2,
            ],
        ],

        // Model-specific monitoring
        'models' => [
            'behavioral_biometrics' => [
                'enabled' => true,
                'critical_features' => [
                    'keystroke_timing',
                    'mouse_velocity',
                    'session_duration',
                    'device_fingerprint',
                ],
                'sensitivity' => 'high',  // Most sensitive to drift
            ],
            'fraud_ml_ensemble' => [
                'enabled' => true,
                'critical_features' => [
                    'amount_log',
                    'transaction_velocity',
                    'geo_risk_score',
                    'device_risk_score',
                    'ip_risk_score',
                ],
                'sensitivity' => 'medium',
            ],
            'insider_threat' => [
                'enabled' => true,
                'critical_features' => [
                    'access_pattern',
                    'data_volume',
                    'time_anomaly',
                ],
                'sensitivity' => 'high',
            ],
            'vpn_proxy_detection' => [
                'enabled' => true,
                'critical_features' => [
                    'vpn_ratio',
                    'residential_proxy_ratio',
                    'asn_change_frequency',
                ],
                'sensitivity' => 'medium',
            ],
        ],

        // Alerting configuration
        'alerting' => [
            'enabled' => true,
            'channels' => [
                'telegram' => env('FRAUD_ML_DRIFT_TELEGRAM_ENABLED', false),
                'slack' => env('FRAUD_ML_DRIFT_SLACK_ENABLED', false),
                'email' => env('FRAUD_ML_DRIFT_EMAIL_ENABLED', true),
            ],
            'recipients' => [
                'data_scientists' => env('FRAUD_ML_DRIFT_DS_EMAILS', ''),
                'ml_engineers' => env('FRAUD_ML_DRIFT_MLE_EMAILS', ''),
                'security_team' => env('FRAUD_ML_DRIFT_SEC_EMAILS', ''),
            ],
            'cooldown_minutes' => 60,  // Don't alert more than once per hour per model
        ],

        // Auto-retrain configuration
        'auto_retrain' => [
            'enabled' => env('FRAUD_ML_DRIFT_AUTORETRAIN_ENABLED', false),
            'require_human_approval' => true,  // Always require approval for production
            'min_labeled_samples' => 1000,  // Minimum samples for retraining
            'canary_percentage' => 10,  // 10% canary deployment
            'canary_duration_hours' => 24,
        ],

        // Baseline management
        'baseline' => [
            'update_interval_days' => 7,  // Update baseline every 7 days
            'auto_update_on_retrain' => true,
            'storage' => 'redis',  // redis or clickhouse
            'redis_prefix' => 'fraudml:drift:baseline:',
        ],

        // Explainability (SHAP)
        'explainability' => [
            'enabled' => true,
            'top_features_count' => 5,
            'compute_on_drift' => true,  // Compute SHAP when drift detected
        ],

        // Compliance (152-ФЗ, ФЗ-323)
        'compliance' => [
            'anonymize_features' => true,
            'audit_log_enabled' => true,
            'retention_days' => 365,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Retraining Configuration
    |--------------------------------------------------------------------------
    */
    'retraining' => [
        'enabled' => false,  // Enable when Python training pipeline is ready
        'schedule' => 'weekly',  // daily, weekly, monthly
        'min_samples' => 1000,
        'auto_deploy' => false,
        'canary_deploy' => true,
        'canary_percentage' => 10,  // 10% traffic to new model
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance (152-ФЗ, ФЗ-323)
    |--------------------------------------------------------------------------
    */
    'compliance' => [
        'anonymize_pii' => true,
        'audit_log_enabled' => true,
        'audit_log_retention_days' => 365,
        'medical_data_masking' => true,
    ],
];
