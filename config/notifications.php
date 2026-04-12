<?php declare(strict_types=1);

/**
 * Конфигурация системы уведомлений CatVRF 2026
 *
 * Настройки каналов доставки, провайдеров, rate-limits,
 * DND и категорий уведомлений.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Каналы доставки
    |--------------------------------------------------------------------------
    |
    | Список всех доступных каналов. Каждый канал имеет статус (enabled/disabled),
    | провайдера, rate-limit и приоритет.
    |
    */
    'channels' => [

        'email' => [
            'enabled'   => (bool) env('NOTIFICATION_EMAIL_ENABLED', true),
            'provider'  => env('NOTIFICATION_EMAIL_PROVIDER', 'mailgun'),
            'from'      => env('MAIL_FROM_ADDRESS', 'noreply@catvrf.ru'),
            'from_name' => env('MAIL_FROM_NAME', 'CatVRF'),
            'rate_limit' => [
                'per_user_per_hour' => 20,
                'per_tenant_per_hour' => 500,
            ],
            'retry' => [
                'max_attempts' => 3,
                'backoff_seconds' => 300,
            ],
            'priority' => 3,
        ],

        'sms' => [
            'enabled'  => (bool) env('NOTIFICATION_SMS_ENABLED', true),
            'provider' => env('NOTIFICATION_SMS_PROVIDER', 'sms_ru'),
            'from'     => env('SMS_FROM', 'CatVRF'),
            'rate_limit' => [
                'per_user_per_hour' => 5,
                'per_tenant_per_hour' => 200,
            ],
            'retry' => [
                'max_attempts' => 2,
                'backoff_seconds' => 600,
            ],
            'priority' => 5,
            'max_length' => 160,
        ],

        'push' => [
            'enabled'  => (bool) env('NOTIFICATION_PUSH_ENABLED', true),
            'provider' => env('NOTIFICATION_PUSH_PROVIDER', 'firebase'),
            'rate_limit' => [
                'per_user_per_hour' => 30,
                'per_tenant_per_hour' => 1000,
            ],
            'retry' => [
                'max_attempts' => 3,
                'backoff_seconds' => 60,
            ],
            'priority' => 2,
            'ttl_seconds' => 86400,
        ],

        'marketplace' => [
            'enabled'  => (bool) env('NOTIFICATION_MARKETPLACE_ENABLED', true),
            'rate_limit' => [
                'per_user_per_hour' => 15,
                'per_tenant_per_hour' => 500,
            ],
            'retry' => [
                'max_attempts' => 3,
                'backoff_seconds' => 60,
            ],
            'priority' => 4,
        ],

        'slack' => [
            'enabled'     => (bool) env('NOTIFICATION_SLACK_ENABLED', true),
            'webhook_url' => env('SLACK_WEBHOOK_URL', ''),
            'channel'     => env('SLACK_CHANNEL', '#alerts'),
            'username'    => env('SLACK_USERNAME', 'CatVRF Bot'),
            'rate_limit' => [
                'per_tenant_per_hour' => 100,
            ],
            'retry' => [
                'max_attempts' => 2,
                'backoff_seconds' => 60,
            ],
            'priority' => 6,
        ],

        'in_app' => [
            'enabled' => true,
            'rate_limit' => [
                'per_user_per_hour' => 50,
                'per_tenant_per_hour' => 2000,
            ],
            'priority' => 1,
            'auto_close_timeout' => 10,
            'max_unread' => 200,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Категории уведомлений
    |--------------------------------------------------------------------------
    |
    | Категории определяют, какие каналы по умолчанию используются
    | для каждого типа уведомления.
    |
    */
    'categories' => [
        'orders' => [
            'label'    => 'Заказы',
            'channels' => ['in_app', 'push', 'email'],
            'user_can_disable' => true,
        ],
        'payments' => [
            'label'    => 'Платежи',
            'channels' => ['in_app', 'push', 'email', 'sms'],
            'user_can_disable' => false,
        ],
        'security' => [
            'label'    => 'Безопасность',
            'channels' => ['in_app', 'push', 'email', 'sms'],
            'user_can_disable' => false,
        ],
        'fraud' => [
            'label'    => 'Фрод-активность',
            'channels' => ['in_app', 'email', 'push', 'sms', 'slack'],
            'user_can_disable' => false,
        ],
        'promotions' => [
            'label'    => 'Промоакции и скидки',
            'channels' => ['in_app', 'push', 'email'],
            'user_can_disable' => true,
        ],
        'delivery' => [
            'label'    => 'Доставка',
            'channels' => ['in_app', 'push'],
            'user_can_disable' => true,
        ],
        'system' => [
            'label'    => 'Системные',
            'channels' => ['in_app'],
            'user_can_disable' => false,
        ],
        'reports' => [
            'label'    => 'Отчёты',
            'channels' => ['email'],
            'user_can_disable' => true,
        ],
        'crm' => [
            'label'    => 'CRM-автоматизации',
            'channels' => ['in_app', 'email', 'push'],
            'user_can_disable' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Do Not Disturb — глобальные настройки
    |--------------------------------------------------------------------------
    */
    'dnd' => [
        'enabled'          => true,
        'default_start'    => '23:00',
        'default_end'      => '07:00',
        'timezone'         => env('APP_TIMEZONE', 'Europe/Moscow'),
        'bypass_channels'  => ['sms'],
        'bypass_categories' => ['security', 'fraud'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Очереди
    |--------------------------------------------------------------------------
    */
    'queues' => [
        'default'  => 'notifications',
        'fraud'    => 'fraud-notifications',
        'priority' => 'notifications-priority',
        'bulk'     => 'notifications-bulk',
    ],

    /*
    |--------------------------------------------------------------------------
    | Хранение и очистка
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'driver'          => 'database',
        'table'           => 'notifications',
        'keep_days'       => 90,
        'archive_to'      => 'clickhouse',
        'cleanup_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Фрод-уведомления — маппинг severity → channels
    |--------------------------------------------------------------------------
    */
    'fraud_severity_channels' => [
        'info'     => [],
        'warning'  => ['in_app', 'email'],
        'high'     => ['in_app', 'email', 'push', 'marketplace'],
        'critical' => ['in_app', 'email', 'push', 'sms', 'marketplace', 'slack'],
    ],
];
