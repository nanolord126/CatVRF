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
    */

    'clickhouse' => [
        'host' => env('CLICKHOUSE_HOST', 'localhost'),
        'port' => env('CLICKHOUSE_PORT', 8123),
        'database' => env('CLICKHOUSE_DATABASE', 'default'),
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
        'alert_threshold_lag' => env('BIGDATA_ALERT_LAG_THRESHOLD', 10000), // messages
        'alert_threshold_error_rate' => env('BIGDATA_ALERT_ERROR_RATE', 0.05), // 5%
        'disk_max_bytes' => env('BIGDATA_DISK_MAX_BYTES', 500_000_000_000), // 500GB
        'consumer_count' => env('BIGDATA_CONSUMER_COUNT', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Monitoring / FinOps Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Big Data cost monitoring, budget enforcement,
    | and auto-optimization. Target: analytics cost ≤ 3-5% of GMV.
    |
    */

    'cost' => [
        'enabled' => env('BIGDATA_COST_MONITORING_ENABLED', true),

        // Cloud provider: aws, gcp, azure, self_hosted
        'cloud_provider' => env('BIGDATA_CLOUD_PROVIDER', 'self_hosted'),

        // Budget thresholds
        'monthly_budget_usd' => env('BIGDATA_MONTHLY_BUDGET_USD', 5000),
        'warning_threshold' => env('BIGDATA_BUDGET_WARNING', 0.80),
        'critical_threshold' => env('BIGDATA_BUDGET_CRITICAL', 1.00),
        'emergency_threshold' => env('BIGDATA_BUDGET_EMERGENCY', 1.20),
        'gmv_max_ratio' => env('BIGDATA_GMV_MAX_RATIO', 0.05),

        // Currency
        'usd_to_rub' => env('BIGDATA_USD_TO_RUB', 95),

        // AWS credentials
        'aws_access_key_id' => env('BIGDATA_AWS_ACCESS_KEY_ID', ''),
        'aws_secret_access_key' => env('BIGDATA_AWS_SECRET_ACCESS_KEY', ''),
        'aws_region' => env('BIGDATA_AWS_REGION', 'us-east-1'),
        'aws_role_arn' => env('BIGDATA_AWS_ROLE_ARN'),

        // GCP credentials
        'gcp_project_id' => env('BIGDATA_GCP_PROJECT_ID', ''),
        'gcp_billing_account_id' => env('BIGDATA_GCP_BILLING_ACCOUNT_ID', ''),
        'gcp_access_token' => env('BIGDATA_GCP_ACCESS_TOKEN', ''),

        // Azure credentials
        'azure_subscription_id' => env('BIGDATA_AZURE_SUBSCRIPTION_ID', ''),
        'azure_tenant_id' => env('BIGDATA_AZURE_TENANT_ID', ''),
        'azure_client_id' => env('BIGDATA_AZURE_CLIENT_ID', ''),
        'azure_client_secret' => env('BIGDATA_AZURE_CLIENT_SECRET', ''),

        // Self-hosted pricing
        'self_hosted_server_count' => env('BIGDATA_SELF_HOSTED_SERVER_COUNT', 3),
        'self_hosted_storage_gb' => env('BIGDATA_SELF_HOSTED_STORAGE_GB', 2000),
        'self_hosted_network_gb_daily' => env('BIGDATA_SELF_HOSTED_NETWORK_GB_DAILY', 50),
        'self_hosted_compute_cost_per_hour' => env('BIGDATA_SELF_HOSTED_COMPUTE_COST_PER_HOUR', 0.10),
        'self_hosted_storage_cost_per_gb_month' => env('BIGDATA_SELF_HOSTED_STORAGE_COST_PER_GB_MONTH', 0.023),
        'self_hosted_network_cost_per_gb' => env('BIGDATA_SELF_HOSTED_NETWORK_COST_PER_GB', 0.01),

        // Kafka retention
        'kafka_retention_days' => env('BIGDATA_KAFKA_RETENTION_DAYS', 7),

        // Estimated costs for optimization calculations
        'estimated_daily_storage_usd' => env('BIGDATA_ESTIMATED_DAILY_STORAGE_USD', 10),
        'estimated_monthly_storage_usd' => env('BIGDATA_ESTIMATED_MONTHLY_STORAGE_USD', 300),

        // Auto-optimization
        'auto_optimize_enabled' => env('BIGDATA_AUTO_OPTIMIZE_ENABLED', true),
        'auto_optimize_min_savings_percent' => env('BIGDATA_AUTO_OPTIMIZE_MIN_SAVINGS', 5),

        // Billing import schedule
        'billing_import_hours_back' => env('BIGDATA_BILLING_IMPORT_HOURS_BACK', 72),

        // Notifications
        'notify_telegram' => env('BIGDATA_COST_NOTIFY_TELEGRAM', false),
        'notify_slack' => env('BIGDATA_COST_NOTIFY_SLACK', false),
        'notify_pagerduty' => env('BIGDATA_COST_NOTIFY_PAGERDUTY', false),
        'telegram_chat_id' => env('BIGDATA_COST_TELEGRAM_CHAT_ID', ''),
        'slack_webhook_url' => env('BIGDATA_COST_SLACK_WEBHOOK', ''),
        'pagerduty_routing_key' => env('BIGDATA_COST_PAGERDUTY_KEY', ''),
    ],
];
