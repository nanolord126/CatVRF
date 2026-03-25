<?php declare(strict_types=1);

return [
    // Cache configuration
    'cache' => [
        'ttl_dynamic' => 60, // 1 minute for volatile stock
        'ttl_stable' => 300, // 5 minutes for stable items
        'prefix' => 'inventory:stock:',
    ],

    // Low stock monitoring
    'low_stock' => [
        'check_interval' => 'daily',
        'check_time' => '08:00',
        'notification_enabled' => true,
        'alert_days_ahead' => 3, // forecast 3 days ahead
    ],

    // Stock movement tracking
    'tracking' => [
        'log_all_movements' => true,
        'retention_days' => 1095, // 3 years per law
    ],

    // Demand forecasting
    'forecasting' => [
        'enabled' => true,
        'provider' => 'ml',
        'forecast_days_ahead' => 30,
    ],

    // Quality metrics
    'quality' => [
        'target_accuracy' => 0.995, // 99.5% accuracy
        'max_discrepancy_percent' => 0.5,
        'discrepancy_alert_threshold' => 10,
        'audit_interval' => 'quarterly',
    ],

    // Rate limiting
    'rate_limiting' => [
        'enabled' => true,
        'requests_per_minute' => 1000,
        'bulk_operations_per_minute' => 10,
    ],

    // Monitoring
    'monitoring' => [
        'log_channel' => 'inventory',
        'sentry_enabled' => true,
    ],
];
