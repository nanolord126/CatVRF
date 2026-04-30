<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring services including failed job alerts,
    | Prometheus metrics, and observability features.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Failed Job Alerts
    |--------------------------------------------------------------------------
    |
    | Configuration for failed job alerting service.
    |
    */
    'failed_job_alert_enabled' => env('FAILED_JOB_ALERT_ENABLED', true),
    'failed_job_alert_threshold' => env('FAILED_JOB_ALERT_THRESHOLD', 10),
    'failed_job_alert_window_minutes' => env('FAILED_JOB_ALERT_WINDOW_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Slack Notifications
    |--------------------------------------------------------------------------
    |
    | Slack webhook URL for sending monitoring alerts.
    |
    */
    'slack_webhook_url' => env('SLACK_WEBHOOK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Telegram Notifications
    |--------------------------------------------------------------------------
    |
    | Telegram bot token and chat ID for sending monitoring alerts.
    |
    */
    'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'telegram_chat_id' => env('TELEGRAM_CHAT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Prometheus Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for Prometheus metrics endpoint.
    |
    */
    'prometheus_token' => env('PROMETHEUS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | PII Masking
    |--------------------------------------------------------------------------
    |
    | Enable/disable PII masking in job payloads and logs.
    |
    */
    'pii' => [
        'masking_enabled' => env('PII_MASKING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quota Check
    |--------------------------------------------------------------------------
    |
    | Enable/disable quota checking in job middleware.
    |
    */
    'quota' => [
        'check_enabled' => env('QUOTA_CHECK_ENABLED', true),
        'retry_delay' => env('QUOTA_RETRY_DELAY', 300), // 5 minutes
    ],
];
