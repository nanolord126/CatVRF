<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | OpenTelemetry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenTelemetry distributed tracing.
    |
    */

    'enabled' => env('TELEMETRY_ENABLED', true),

    'service_name' => env('TELEMETRY_SERVICE_NAME', 'catvrf'),

    'service_version' => env('TELEMETRY_SERVICE_VERSION', '1.0.0'),

    'deployment_environment' => env('TELEMETRY_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Exporter Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where to send telemetry data.
    |
    */

    'exporter' => env('TELEMETRY_EXPORTER', 'otlp'),

    'otlp' => [
        'endpoint' => env('TELEMETRY_OTLP_ENDPOINT', 'http://localhost:4318'),
        'protocol' => env('TELEMETRY_OTLP_PROTOCOL', 'http/protobuf'),
        'headers' => [
            'X-Header' => env('TELEMETRY_OTLP_HEADER'),
        ],
    ],

    'jaeger' => [
        'endpoint' => env('TELEMETRY_JAEGER_ENDPOINT', 'http://localhost:14268/api/traces'),
        'username' => env('TELEMETRY_JAEGER_USERNAME'),
        'password' => env('TELEMETRY_JAEGER_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sampling
    |--------------------------------------------------------------------------
    |
    | Configure sampling strategy.
    |
    */

    'sampler' => [
        'type' => env('TELEMETRY_SAMPLER_TYPE', 'parentbased_always_on'),
        'ratio' => (float) env('TELEMETRY_SAMPLER_RATIO', 1.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Attributes
    |--------------------------------------------------------------------------
    |
    | Additional resource attributes to include in all spans.
    |
    */

    'resource_attributes' => [
        'service.namespace' => env('TELEMETRY_SERVICE_NAMESPACE', 'catvrf'),
        'host.name' => gethostname(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vertical-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for specific verticals.
    |
    */

    'verticals' => [
        'supermarket' => [
            'enabled' => true,
            'tracing' => [
                'checkout' => true,
                'order_create' => true,
                'payment' => true,
                'return' => true,
                'subscription' => true,
            ],
        ],
    ],
];
