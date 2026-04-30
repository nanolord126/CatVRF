<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | OpenTelemetry Configuration for BigData
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenTelemetry tracing and metrics export.
    | Tempo is the recommended backend for traces.
    | Loki is the recommended backend for logs.
    */

    'enabled' => env('OTEL_ENABLED', false),

    'service' => [
        'name' => env('OTEL_SERVICE_NAME', 'catvrf-bigdata'),
        'version' => env('OTEL_SERVICE_VERSION', '1.0.0'),
        'environment' => env('APP_ENV', 'production'),
    ],

    'traces' => [
        'enabled' => env('OTEL_TRACES_ENABLED', false),
        'exporter' => env('OTEL_TRACES_EXPORTER', 'otlp'), // otlp|none|logging
        'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://tempo:4317'),
        'protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'grpc'), // grpc|http
        'sample_rate' => env('OTEL_TRACES_SAMPLE_RATE', 0.1), // 10% sampling for production
    ],

    'metrics' => [
        'enabled' => env('OTEL_METRICS_ENABLED', false),
        'exporter' => env('OTEL_METRICS_EXPORTER', 'otlp'),
        'endpoint' => env('OTEL_EXPORTER_OTLP_METRICS_ENDPOINT', 'http://tempo:4317'),
        'export_interval' => env('OTEL_METRIC_EXPORT_INTERVAL', 15000), // ms
    ],

    'logs' => [
        'enabled' => env('OTEL_LOGS_ENABLED', false),
        'loki_endpoint' => env('LOKI_ENDPOINT', 'http://loki:3100'),
        'loki_tenant' => env('LOKI_TENANT_ID', null),
    ],

    'propagation' => [
        'formats' => env('OTEL_PROPAGATORS', 'tracecontext,baggage'),
    ],

    'bigdata' => [
        // Which BigData operations to trace
        'trace_event_tracking' => env('OTEL_BIGDATA_TRACE_EVENTS', true),
        'trace_kafka_produce' => env('OTEL_BIGDATA_TRACE_KAFKA_PRODUCE', true),
        'trace_kafka_consume' => env('OTEL_BIGDATA_TRACE_KAFKA_CONSUME', true),
        'trace_clickhouse_queries' => env('OTEL_BIGDATA_TRACE_CH_QUERIES', true),
        'trace_spark_jobs' => env('OTEL_BIGDATA_TRACE_SPARK', true),

        // Correlation ID header
        'correlation_header' => env('OTEL_BIGDATA_CORRELATION_HEADER', 'X-Correlation-ID'),
    ],

    'resource' => [
        'deployment.environment' => env('APP_ENV', 'production'),
        'service.namespace' => env('OTEL_RESOURCE_NAMESPACE', 'catvrf'),
        'service.instance.id' => env('HOSTNAME', gethostname()),
    ],
];
