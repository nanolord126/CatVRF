<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DataDog Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configure DataDog for metrics, logs, and APM
    |
    */

    'enabled' => env('DATADOG_ENABLED', false),

    'api_key' => env('DATADOG_API_KEY'),

    'app_key' => env('DATADOG_APP_KEY'),

    'site' => env('DATADOG_SITE', 'datadoghq.com'),

    /*
    |--------------------------------------------------------------------------
    | StatsD Configuration
    |--------------------------------------------------------------------------
    */

    'statsd' => [
        'host' => env('DATADOG_STATSD_HOST', 'localhost'),
        'port' => (int) env('DATADOG_STATSD_PORT', 8125),
        'namespace' => env('DATADOG_STATSD_NAMESPACE', 'catvrf'),
    ],

    /*
    |--------------------------------------------------------------------------
    | APM Configuration
    |--------------------------------------------------------------------------
    */

    'apm' => [
        'enabled' => env('DATADOG_APM_ENABLED', false),
        'sample_rate' => (float) env('DATADOG_APM_SAMPLE_RATE', 0.1),
        'trace_header_sampling' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('DATADOG_LOGGING_ENABLED', false),
        'json_format' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tags
    |--------------------------------------------------------------------------
    */

    'tags' => [
        'env' => env('APP_ENV', 'production'),
        'version' => env('APP_VERSION'),
        'service' => 'catvrf',
    ],

];
