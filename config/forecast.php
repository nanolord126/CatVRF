<?php declare(strict_types=1);

return [
    // Model configuration
    'model' => [
        'type' => env('FORECAST_MODEL_TYPE', 'xgboost'),
        'path' => storage_path('models/demand'),
        'train_interval' => 'daily',
        'train_time' => '04:30',
    ],

    // Forecast horizons
    'horizons' => [
        'short_term' => 7, // days
        'medium_term' => 30, // days
        'long_term' => 90, // days
    ],

    // Data retention
    'data' => [
        'historical_days' => 365,
        'training_split' => 0.8,
        'validation_split' => 0.1,
        'test_split' => 0.1,
    ],

    // Quality metrics
    'quality' => [
        'target_mape' => 0.15, // 15%
        'target_mae_percent' => 0.10, // 10% of avg demand
        'target_coverage' => 0.95, // 95% of items
        'min_mape_for_update' => 0.25,
    ],

    // Features
    'features' => [
        'lags' => [1, 7, 30],
        'seasonality' => ['day_of_week', 'month', 'quarter'],
        'external' => ['weather', 'holidays', 'events'],
    ],

    // Cache configuration
    'cache' => [
        'ttl_short' => 3600, // 1 hour
        'ttl_long' => 86400, // 1 day
        'prefix' => 'demand_forecast:',
    ],

    // Monitoring
    'monitoring' => [
        'log_channel' => 'forecast',
        'sentry_enabled' => true,
        'alert_mape_threshold' => 0.25,
        'daily_report_time' => '09:00',
    ],
];
