<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация для продвинутой аналитики Phase 7
    | Включает: KPI thresholds, forecast settings, export limits
    */

    'cache_ttl' => [
        'metrics' => 3600,      // 1 hour
        'forecast' => 86400,    // 24 hours
        'kpis' => 1800,         // 30 minutes
    ],

    'forecast' => [
        'max_days_ahead' => 90,
        'confidence_threshold' => 0.75,
        'sample_data_points' => 30,
    ],

    'export' => [
        'max_file_size_mb' => 100,
        'allowed_formats' => ['csv', 'json', 'excel', 'pdf'],
        'retention_days' => 30,
    ],

    'segments' => [
        'high_value_threshold' => 50000,      // LTV > 50000 = High-Value
        'medium_value_threshold' => 10000,    // 10000 < LTV < 50000 = Medium
        'churn_risk_threshold' => 90,         // days without purchase
        'dormant_threshold' => 90,            // days without any activity
    ],

    'reporting' => [
        'frequencies' => ['daily', 'weekly', 'monthly'],
        'default_recipients' => 1,
        'max_recipients' => 10,
        'retention_days' => 90,
    ],

    'kpi' => [
        'revenue_target' => 500000,           // Monthly target
        'conversion_target' => 0.05,          // 5%
        'ltv_target' => 50000,
        'churn_risk_threshold' => 0.20,      // 20%
    ],

    'features' => [
        'enable_ml_forecasting' => false,     // Disabled by default
        'enable_predictive_segments' => false,
        'enable_anomaly_detection' => false,
    ],
];
