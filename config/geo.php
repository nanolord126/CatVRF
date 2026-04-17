<?php declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Geolocation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for geolocation services including providers,
    | privacy settings, and spatial query optimization.
    |
    */

    // Primary provider: yandex, osm, 2gis
    'primary_provider' => env('GEO_PRIMARY_PROVIDER', 'yandex'),

    'providers' => [
        'yandex' => [
            'api_key' => env('YANDEX_MAPS_API_KEY'),
            'timeout' => env('YANDEX_MAPS_TIMEOUT', 5),
            'retry' => env('YANDEX_MAPS_RETRY', 2),
        ],
        'osm' => [
            'enabled' => env('OSM_ENABLED', true),
            'timeout' => env('OSM_TIMEOUT', 5),
            'retry' => env('OSM_RETRY', 2),
        ],
        '2gis' => [
            'api_key' => env('DGIS_API_KEY'),
            'enabled' => env('DGIS_ENABLED', false),
        ],
    ],

    'circuit_breaker' => [
        'failure_threshold' => 5,
        'timeout_seconds' => 300,
        'half_open_attempts' => 3,
    ],

    'cache' => [
        'route_ttl' => 300, // 5 minutes
        'geocode_ttl' => 3600, // 1 hour
        'distance_ttl' => 300,
    ],

    'privacy' => [
        'enabled' => env('GEO_PRIVACY_ENABLED', true),
        'anonymization_precision' => env('GEO_ANONYMIZATION_PRECISION', 4), // decimal places
        'geohash_precision' => env('GEO_GEOHASH_PRECISION', 7),
        'medical_data_precision' => env('GEO_MEDICAL_PRECISION', 3), // lower precision for medical
        'consent_required' => true,
    ],

    'addresses' => [
        'max_per_user' => env('GEO_MAX_ADDRESSES_PER_USER', 5),
        'per_tenant' => [
            'medical' => 10,
            'delivery' => 5,
            'default' => 5,
        ],
        'per_vertical' => [
            'medical' => 10,
            'delivery' => 5,
            'beauty' => 5,
            'default' => 5,
        ],
    ],

    'tracking' => [
        'update_interval_seconds' => 3,
        'use_redis_streams' => env('GEO_USE_REDIS_STREAMS', true),
        'stream_key' => 'geo:tracking:stream',
        'consumer_group' => 'geo_tracking_consumers',
        'max_history_length' => 1000,
    ],

    'spatial' => [
        'enable_materialized_views' => env('GEO_ENABLE_MATERIALIZED_VIEWS', true),
        'nearest_neighbor_index' => true,
        'refresh_interval_hours' => 1,
    ],

    'rate_limiting' => [
        'geocode_per_minute' => 30,
        'route_per_minute' => 20,
        'autocomplete_per_minute' => 50,
    ],

    'telemetry' => [
        'enabled' => env('GEO_TELEMETRY_ENABLED', true),
        'prometheus_endpoint' => env('GEO_PROMETHEUS_ENDPOINT', '/metrics/geo'),
    ],
];
