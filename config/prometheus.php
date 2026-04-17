<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Prometheus Storage Driver
    |--------------------------------------------------------------------------
    |
    | The storage driver to use for Prometheus metrics.
    | Available: "memory", "redis", "apc", "in-memory"
    |
    | Production: Use "redis" for persistence and distributed scraping
    | Development: Use "memory" for simplicity
    */
    'storage_driver' => env('PROMETHEUS_STORAGE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    |
    | The Redis connection to use for storing metrics.
    | Must be configured in config/database.php
    */
    'redis_connection' => env('PROMETHEUS_REDIS_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Prometheus Metrics Route
    |--------------------------------------------------------------------------
    |
    | The route where Prometheus metrics will be exposed.
    | Protect this endpoint with middleware in production!
    */
    'route' => [
        'enabled' => env('PROMETHEUS_ROUTE_ENABLED', true),
        'prefix' => env('PROMETHEUS_ROUTE_PREFIX', 'metrics'),
        'middleware' => env('PROMETHEUS_ROUTE_MIDDLEWARE', 'auth:landlord,throttle:metrics'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metric Buckets for Histograms
    |--------------------------------------------------------------------------
    |
    | Default buckets for histogram metrics.
    | Customize for your specific use cases (latency, duration, etc.)
    */
    'buckets' => [
        'default' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10],
        'latency' => [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10],
        'duration' => [0.1, 0.5, 1, 2.5, 5, 10, 30, 60, 120, 300, 600, 1800, 3600],
        'score' => [0.0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0],
        'psi' => [0.01, 0.05, 0.1, 0.15, 0.2, 0.25, 0.3, 0.5, 1.0],
        'quota' => [0.0, 0.1, 0.25, 0.5, 0.75, 0.9, 0.95, 1.0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prometheus Namespace
    |--------------------------------------------------------------------------
    |
    | Namespace for all CatVRF metrics.
    | All metrics will be prefixed with this namespace.
    */
    'namespace' => env('PROMETHEUS_NAMESPACE', 'catvrf'),

    /*
    |--------------------------------------------------------------------------
    | Custom Collectors
    |--------------------------------------------------------------------------
    |
    | Spatie Laravel Prometheus uses dynamic metric registration via facade.
    | Metrics are registered on-demand in PrometheusMetricsService.
    | No static collector registration needed.
    */
    'collectors' => [],

    /*
    |--------------------------------------------------------------------------
    | Prometheus Storage Options
    |--------------------------------------------------------------------------
    |
    | Additional options for storage drivers.
    */
    'storage' => [
        'redis' => [
            'prefix' => env('PROMETHEUS_REDIS_PREFIX', 'prometheus:'),
            'ttl' => env('PROMETHEUS_REDIS_TTL', null), // null = no expiration
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Label Cardinality Limits
    |--------------------------------------------------------------------------
    |
    | Protect against high cardinality labels that can cause memory issues.
    | Never include high-cardinality labels like user_id, tenant_id, etc.
    */
    'label_cardinality_limits' => [
        'max_labels_per_metric' => 10,
        'max_label_values_per_metric' => 1000,
        'blocked_labels' => [
            'user_id',
            'tenant_id',
            'request_id',
            'correlation_id', // Only use in logs, not in metrics
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metric Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep metrics in storage before they expire.
    | Only applicable when storage driver supports TTL.
    */
    'retention' => [
        'enabled' => env('PROMETHEUS_RETENTION_ENABLED', false),
        'ttl_days' => env('PROMETHEUS_RETENTION_TTL_DAYS', 7),
    ],
];
