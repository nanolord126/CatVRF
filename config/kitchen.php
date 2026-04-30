<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Kitchen Display System (KDS) Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация для интеграции с кухонным оборудованием:
    | - KDS дисплеи
    | - Кухонные принтеры
    | - Сигнальные системы
    | - MQTT/WebSocket
    | - Generic HTTP драйверы
    |
    */

    'default_drivers' => [
        'kds_display',
        'kitchen_printer',
    ],

    /*
    |--------------------------------------------------------------------------
    | KDS Display Driver (Internal WebSocket)
    |--------------------------------------------------------------------------
    */
    'kds_display' => [
        'enabled' => env('KDS_DISPLAY_ENABLED', true),
        'refresh_interval' => env('KDS_REFRESH_INTERVAL', 5), // секунды
        'auto_refresh' => env('KDS_AUTO_REFRESH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kitchen Printer Driver (ESC/POS)
    |--------------------------------------------------------------------------
    */
    'kitchen_printer' => [
        'enabled' => env('KITCHEN_PRINTER_ENABLED', false),
        'printers' => [
            // Пример конфигурации принтеров по станциям
            // '1' => [
            //     'name' => 'Холодный цех принтер',
            //     'type' => 'network', // network, usb, bluetooth
            //     'host' => '192.168.1.100',
            //     'port' => 9100,
            //     'model' => 'star_tsp143', // star_mcprint3, epson_tm_t88, citizen_ct_s310
            // ],
            // '2' => [
            //     'name' => 'Горячий цех принтер',
            //     'type' => 'network',
            //     'host' => '192.168.1.101',
            //     'port' => 9100,
            //     'model' => 'epson_tm_t88',
            // ],
            // '3' => [
            //     'name' => 'Бар принтер',
            //     'type' => 'usb',
            //     'device' => '/dev/usb/lp0',
            //     'model' => 'star_tsp143',
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Signal System Driver (Sound/Light Alerts)
    |--------------------------------------------------------------------------
    */
    'signal_system' => [
        'enabled' => env('SIGNAL_SYSTEM_ENABLED', false),
        'api_url' => env('SIGNAL_SYSTEM_API_URL'),
        'api_key' => env('SIGNAL_SYSTEM_API_KEY'),
        'sound_enabled' => env('SIGNAL_SOUND_ENABLED', true),
        'light_enabled' => env('SIGNAL_LIGHT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | MQTT Driver (for tablets)
    |--------------------------------------------------------------------------
    */
    'mqtt' => [
        'enabled' => env('KITCHEN_MQTT_ENABLED', false),
        'host' => env('KITCHEN_MQTT_HOST', 'localhost'),
        'port' => env('KITCHEN_MQTT_PORT', 1883),
        'username' => env('KITCHEN_MQTT_USERNAME'),
        'password' => env('KITCHEN_MQTT_PASSWORD'),
        'client_id' => env('KITCHEN_MQTT_CLIENT_ID', 'catvrf-kds'),
        'use_tls' => env('KITCHEN_MQTT_TLS', false),
        'tls_self_signed' => env('KITCHEN_MQTT_TLS_SELF_SIGNED', true),
        'subscribed_stations' => [], // Автоматически подписываемся на станции
    ],

    /*
    |--------------------------------------------------------------------------
    | Browser Print Driver
    |--------------------------------------------------------------------------
    */
    'browser_print' => [
        'enabled' => env('BROWSER_PRINT_ENABLED', false),
        'cache_ttl' => 1800, // 30 минут
    ],

    /*
    |--------------------------------------------------------------------------
    | Generic HTTP Driver (for custom integrations)
    |--------------------------------------------------------------------------
    */
    'generic_http' => [
        'enabled' => env('GENERIC_HTTP_ENABLED', false),
        'health_check_url' => env('GENERIC_HTTP_HEALTH_CHECK_URL'),
        'auth_type' => env('GENERIC_HTTP_AUTH_TYPE', 'none'), // none, bearer, basic, api_key, digest
        'auth_token' => env('GENERIC_HTTP_AUTH_TOKEN'),
        'auth_username' => env('GENERIC_HTTP_AUTH_USERNAME'),
        'auth_password' => env('GENERIC_HTTP_AUTH_PASSWORD'),
        'auth_key_header' => env('GENERIC_HTTP_AUTH_KEY_HEADER', 'X-API-Key'),
        'auth_key_value' => env('GENERIC_HTTP_AUTH_KEY_VALUE'),
        'endpoints' => [
            // Пример конфигурации эндпоинтов по станциям
            // '1' => [
            //     'name' => 'Холодный цех API',
            //     'url' => 'https://api.example.com/kitchen/orders',
            //     'method' => 'POST',
            //     'timeout' => 10,
            //     'headers' => [
            //         'Content-Type' => 'application/json',
            //         'X-Station-ID' => '1',
            //     ],
            //     'status_update_url' => 'https://api.example.com/kitchen/orders/{order_id}/status',
            //     'cancel_url' => 'https://api.example.com/kitchen/orders/{order_id}/cancel',
            //     'payload_template' => [
            //         'order_id' => '{{order_id}}',
            //         'station_id' => '{{kitchen_station_id}}',
            //         'priority' => '{{priority}}',
            //         'items' => '{{items}}',
            //     ],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preparation Time Settings
    |--------------------------------------------------------------------------
    */
    'preparation_times' => [
        'default' => 15, // минут по умолчанию
        'by_station' => [
            'cold' => 10,
            'hot' => 20,
            'bar' => 5,
            'dessert' => 15,
            'expedition' => 0,
            'grill' => 25,
            'pizza' => 30,
            'sushi' => 20,
        ],
        'by_priority' => [
            'normal' => 1.0,
            'high' => 0.8,
            'urgent' => 0.6,
            'vip' => 0.7,
            'emergency' => 0.5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Overdue Alert Settings
    |--------------------------------------------------------------------------
    */
    'overdue' => [
        'warning_threshold' => 0.8, // 80% от расчётного времени
        'critical_threshold' => 1.0, // 100% от расчётного времени
        'auto_alert' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline Mode Settings
    |--------------------------------------------------------------------------
    */
    'offline_mode' => [
        'enabled' => env('KITCHEN_OFFLINE_MODE', true),
        'sync_on_reconnect' => true,
        'local_storage_ttl' => 3600, // 1 час
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'driver' => env('KITCHEN_CACHE_DRIVER', 'redis'),
        'prefix' => 'kitchen:',
        'ttl' => 300, // 5 минут
    ],
];
