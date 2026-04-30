<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Flowers CRM Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Flowers/Floristry vertical of CatVRF
    |
    */

    'enabled' => env('FLOWERS_CRM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Order Funnel Configuration
    |--------------------------------------------------------------------------
    */
    'funnel' => [
        'stages' => [
            'lead' => [
                'name' => 'Лид',
                'color' => '#6366f1',
                'auto_convert_timeout' => 24, // hours
            ],
            'new_order' => [
                'name' => 'Новый заказ',
                'color' => '#8b5cf6',
                'auto_confirm' => false,
            ],
            'confirmed' => [
                'name' => 'Подтверждён',
                'color' => '#3b82f6',
            ],
            'in_assembly' => [
                'name' => 'В сборке',
                'color' => '#f59e0b',
                'max_assembly_time' => 60, // minutes
            ],
            'assembled' => [
                'name' => 'Собран',
                'color' => '#10b981',
            ],
            'quality_checked' => [
                'name' => 'Проверен',
                'color' => '#059669',
            ],
            'ready_for_delivery' => [
                'name' => 'Готов к доставке',
                'color' => '#06b6d4',
            ],
            'out_for_delivery' => [
                'name' => 'В пути',
                'color' => '#0ea5e9',
            ],
            'delivered' => [
                'name' => 'Доставлен',
                'color' => '#22c55e',
            ],
            'cancelled' => [
                'name' => 'Отменён',
                'color' => '#ef4444',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation Rules
    |--------------------------------------------------------------------------
    */
    'automation' => [
        'auto_assign_florist' => env('FLOWERS_AUTO_ASSIGN_FLORIST', true),
        'auto_assign_strategy' => env('FLOWERS_AUTO_ASSIGN_STRATEGY', 'best_score'), // best_score, round_robin, least_busy
        'auto_confirm_orders' => env('FLOWERS_AUTO_CONFIRM_ORDERS', false),
        'auto_send_notifications' => env('FLOWERS_AUTO_SEND_NOTIFICATIONS', true),
        'auto_update_freshness' => env('FLOWERS_AUTO_UPDATE_FRESHNESS', true),
        'freshness_check_interval' => env('FLOWERS_FRESHNESS_CHECK_INTERVAL', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Freshness Management
    |--------------------------------------------------------------------------
    */
    'freshness' => [
        'thresholds' => [
            'expiring_soon_days' => 3,
            'aging_days' => 7,
            'good_days' => 14,
        ],
        'auto_discount' => [
            'enabled' => env('FLOWERS_AUTO_DISCOUNT', true),
            'expiring_soon_discount' => 20, // percentage
            'aging_discount' => 10, // percentage
        ],
        'alerts' => [
            'low_stock_threshold' => 10,
            'expiry_warning_days' => 3,
            'expiry_critical_days' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Configuration
    |--------------------------------------------------------------------------
    */
    'delivery' => [
        'default_radius_km' => env('FLOWERS_DEFAULT_DELIVERY_RADIUS', 15),
        'default_fee' => env('FLOWERS_DEFAULT_DELIVERY_FEE', 500),
        'free_delivery_threshold' => env('FLOWERS_FREE_DELIVERY_THRESHOLD', 3000),
        'slot_duration_minutes' => 60,
        'max_orders_per_slot' => 5,
        'preparation_time_minutes' => 30,
        'urgent_preparation_time_minutes' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Loyalty Program
    |--------------------------------------------------------------------------
    */
    'loyalty' => [
        'enabled' => env('FLOWERS_LOYALTY_ENABLED', true),
        'points_per_ruble' => 0.01, // 1 point per 100 RUB
        'point_value_rubles' => 1, // 1 point = 1 RUB
        'tiers' => [
            'bronze' => [
                'min_orders' => 0,
                'min_spent' => 0,
                'discount_percent' => 0,
            ],
            'silver' => [
                'min_orders' => 5,
                'min_spent' => 10000,
                'discount_percent' => 5,
            ],
            'gold' => [
                'min_orders' => 10,
                'min_spent' => 20000,
                'discount_percent' => 10,
            ],
            'platinum' => [
                'min_orders' => 20,
                'min_spent' => 50000,
                'discount_percent' => 15,
            ],
        ],
        'birthday_bonus_points' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Florist Assignment
    |--------------------------------------------------------------------------
    */
    'florist_assignment' => [
        'max_concurrent_orders' => 10,
        'rating_weight' => 0.3,
        'experience_weight' => 0.25,
        'availability_weight' => 0.2,
        'workload_weight' => 0.25,
        'urgent_order_bonus' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'channels' => [
            'email' => env('FLOWERS_NOTIFY_EMAIL', true),
            'sms' => env('FLOWERS_NOTIFY_SMS', true),
            'push' => env('FLOWERS_NOTIFY_PUSH', true),
            'telegram' => env('FLOWERS_NOTIFY_TELEGRAM', false),
        ],
        'events' => [
            'order_created' => ['email', 'sms'],
            'order_confirmed' => ['email', 'sms'],
            'order_in_assembly' => ['push'],
            'order_ready' => ['email', 'sms', 'push'],
            'order_delivered' => ['email'],
            'order_cancelled' => ['email', 'sms'],
            'freshness_alert' => ['email', 'push'],
            'low_stock_alert' => ['email', 'push'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Photo Management
    |--------------------------------------------------------------------------
    */
    'photos' => [
        'enabled' => env('FLOWERS_PHOTOS_ENABLED', true),
        'require_photo_on_delivery' => env('FLOWERS_REQUIRE_PHOTO_ON_DELIVERY', true),
        'require_photo_on_assembly' => env('FLOWERS_REQUIRE_PHOTO_ON_ASSEMBLY', false),
        'storage_disk' => env('FLOWERS_PHOTO_STORAGE_DISK', 'public'),
        'max_file_size_kb' => 10240, // 10MB
        'allowed_formats' => ['jpg', 'jpeg', 'png', 'webp'],
        'thumbnail_width' => 300,
        'thumbnail_height' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'marketplace' => [
            'enabled' => env('FLOWERS_MARKETPLACE_INTEGRATION', true),
            'sync_products' => env('FLOWERS_SYNC_PRODUCTS', true),
            'sync_orders' => env('FLOWERS_SYNC_ORDERS', true),
        ],
        'kds' => [
            'enabled' => env('FLOWERS_KDS_INTEGRATION', true),
            'auto_push_orders' => true,
        ],
        'delivery_service' => [
            'enabled' => env('FLOWERS_DELIVERY_SERVICE_INTEGRATION', true),
            'provider' => env('FLOWERS_DELIVERY_PROVIDER', 'catvrf'), // catvrf, yandex, courier
        ],
        'iot' => [
            'enabled' => env('FLOWERS_IOT_INTEGRATION', false),
            'smart_fridges' => false,
            'temperature_monitoring' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting & Analytics
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'track_conversion_rate' => true,
        'track_average_order_value' => true,
        'track_repeat_purchase_rate' => true,
        'track_florist_performance' => true,
        'track_freshness_metrics' => true,
        'retention_days' => 90, // days to retain detailed analytics
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Compliance
    |--------------------------------------------------------------------------
    */
    'security' => [
        'anonymize_client_data' => env('FLOWERS_ANONYMIZE_CLIENT_DATA', false),
        'retention_period_days' => env('FLOWERS_DATA_RETENTION_DAYS', 365),
        'gdpr_compliant' => env('FLOWERS_GDPR_COMPLIANT', false),
        'audit_log_enabled' => env('FLOWERS_AUDIT_LOG_ENABLED', true),
    ],
];
