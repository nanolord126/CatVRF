<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Big Data Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Big Data vertical: ClickHouse, Kafka, Spark
    | Scale: 50M+ events/day, 500M+ historical records
    |
    | Supports both local and cloud-managed services:
    | - ClickHouse: Local (WSL) or ClickHouse Cloud
    | - Kafka: Confluent Cloud REST Proxy or Redis Streams fallback
    | - Redis: Local or Redis Cloud
    | - Storage: MinIO or AWS S3
    | - Monitoring: Grafana Cloud
    */

    'clickhouse' => [
        'host' => env('CLICKHOUSE_HOST', 'localhost'),
        'port' => env('CLICKHOUSE_PORT', 8123),
        'database' => env('CLICKHOUSE_DATABASE', 'catvrf_bigdata'),
        'username' => env('CLICKHOUSE_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_PASSWORD', ''),
        'connect_timeout' => env('CLICKHOUSE_CONNECT_TIMEOUT', 5),
        'query_timeout' => env('CLICKHOUSE_QUERY_TIMEOUT', 30),
    ],

    'kafka' => [
        'enabled' => env('KAFKA_ENABLED', true),
        'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),
        'topic' => env('KAFKA_TOPIC', 'bigdata_events'),
        'consumer_group_id' => env('KAFKA_CONSUMER_GROUP_ID', 'bigdata_consumer'),
        'username' => env('KAFKA_USERNAME'),
        'password' => env('KAFKA_PASSWORD'),
        'timeout_ms' => env('KAFKA_TIMEOUT_MS', 10000),
        'max_messages' => env('KAFKA_MAX_MESSAGES', 1000),

        // Confluent Cloud REST Proxy
        'rest_proxy_url' => env('KAFKA_REST_PROXY_URL'),
        'cluster_id' => env('KAFKA_CLUSTER_ID'),

        // Fallback mode: 'rest_proxy' or 'redis_streams'
        'mode' => env('KAFKA_MODE', 'redis_streams'),
    ],

    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
        'stream_prefix' => env('BIGDATA_REDIS_STREAM_PREFIX', 'bigdata:events:'),
        'dlq_prefix' => env('BIGDATA_REDIS_DLQ_PREFIX', 'bigdata:dlq:'),
    ],

    'storage' => [
        'driver' => env('BIGDATA_STORAGE_DRIVER', 'local'), // 'local', 's3', 'minio'
        's3' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('BIGDATA_S3_BUCKET', 'catvrf-bigdata'),
            'endpoint' => env('AWS_ENDPOINT_URL'), // For MinIO or custom S3
        ],
        'minio' => [
            'endpoint' => env('MINIO_ENDPOINT', 'http://localhost:9000'),
            'key' => env('MINIO_KEY', 'minioadmin'),
            'secret' => env('MINIO_SECRET', 'minioadmin'),
            'bucket' => env('MINIO_BUCKET', 'catvrf-bigdata'),
        ],
        'local_path' => env('BIGDATA_LOCAL_PATH', storage_path('app/bigdata')),
    ],

    'spark' => [
        'enabled' => env('SPARK_ENABLED', false),
        'master' => env('SPARK_MASTER', 'local[*]'),
        'app_name' => env('SPARK_APP_NAME', 'CatVRF-BigData'),
        'memory' => env('SPARK_MEMORY', '4g'),
        'cores' => env('SPARK_CORES', 4),
    ],

    'retention' => [
        'raw_events_days' => env('BIGDATA_RETENTION_RAW_DAYS', 90),
        'daily_metrics_days' => env('BIGDATA_RETENTION_DAILY_DAYS', 730),
        'clv_predictions_days' => env('BIGDATA_RETENTION_CLV_DAYS', 365),
        'abtest_days' => env('BIGDATA_RETENTION_ABTEST_DAYS', 365),
    ],

    'cache' => [
        'ttl' => env('BIGDATA_CACHE_TTL', 3600), // 1 hour
    ],

    'queues' => [
        'kafka_consumer' => env('BIGDATA_QUEUE_KAFKA', 'bigdata-kafka'),
        'spark_jobs' => env('BIGDATA_QUEUE_SPARK', 'bigdata-spark'),
    ],

    'monitoring' => [
        'enabled' => env('BIGDATA_MONITORING_ENABLED', true),
        'alert_threshold_lag' => env('BIGDATA_ALERT_LAG_THRESHOLD', 10000),
        'alert_threshold_error_rate' => env('BIGDATA_ALERT_ERROR_RATE', 0.05),

        // Grafana Cloud
        'grafana' => [
            'url' => env('GRAFANA_URL'),
            'api_key' => env('GRAFANA_API_KEY'),
            'datasource_id' => env('GRAFANA_DATASOURCE_ID'),
        ],

        // Prometheus (local or Grafana Cloud agent)
        'prometheus' => [
            'url' => env('PROMETHEUS_URL', 'http://localhost:9090'),
        ],
    ],
];
