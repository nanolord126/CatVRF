<?php

return [
    /**
     * ClickHouse server connection settings
     */
    'host' => env('CLICKHOUSE_HOST', 'localhost'),
    'port' => env('CLICKHOUSE_PORT', 8123),
    'username' => env('CLICKHOUSE_USERNAME', 'default'),
    'password' => env('CLICKHOUSE_PASSWORD', ''),
    'database' => env('CLICKHOUSE_DATABASE', 'analytics'),

    /**
     * Connection pool settings
     */
    'pool' => [
        'min' => env('CLICKHOUSE_POOL_MIN', 5),
        'max' => env('CLICKHOUSE_POOL_MAX', 20),
    ],

    /**
     * Timeout settings (seconds)
     */
    'timeouts' => [
        'connect' => env('CLICKHOUSE_CONNECT_TIMEOUT', 10),
        'read' => env('CLICKHOUSE_READ_TIMEOUT', 30),
        'write' => env('CLICKHOUSE_WRITE_TIMEOUT', 30),
        'max_execution_time' => env('CLICKHOUSE_MAX_EXECUTION_TIME', 30),
    ],

    /**
     * Data retention policies (days)
     */
    'retention' => [
        'geo_events' => 730, // 2 years
        'click_events' => 730,
        'aggregates' => 1095, // 3 years
    ],

    /**
     * Sync settings
     */
    'sync' => [
        'interval_minutes' => env('CLICKHOUSE_SYNC_INTERVAL', 5),
        'batch_size' => env('CLICKHOUSE_SYNC_BATCH_SIZE', 10000),
        'enabled' => env('CLICKHOUSE_SYNC_ENABLED', true),
    ],

    /**
     * Cache settings for aggregations
     */
    'cache' => [
        'ttl_hourly' => env('CLICKHOUSE_CACHE_TTL_HOURLY', 5 * 60), // 5 minutes
        'ttl_daily' => env('CLICKHOUSE_CACHE_TTL_DAILY', 60 * 60), // 1 hour
        'ttl_weekly' => env('CLICKHOUSE_CACHE_TTL_WEEKLY', 24 * 60 * 60), // 24 hours
    ],

    /**
     * Feature flags
     */
    'features' => [
        'time_series' => env('CLICKHOUSE_FEATURE_TIMESERIES', true),
        'comparison_mode' => env('CLICKHOUSE_FEATURE_COMPARISON', true),
        'custom_metrics' => env('CLICKHOUSE_FEATURE_CUSTOM_METRICS', false), // Phase 3B
        'anomaly_detection' => env('CLICKHOUSE_FEATURE_ANOMALY', false), // Phase 3B
    ],
];
