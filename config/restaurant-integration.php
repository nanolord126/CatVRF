<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Kitchen Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация интеграций KDS с кухонным оборудованием
    |
    */

    'drivers' => [
        /*
        |--------------------------------------------------------------------------
        | KDS Display Driver (Internal WebSocket)
        |--------------------------------------------------------------------------
        |
        | Внутренний драйвер для отображения заказов на KDS через WebSocket
        |
        */
        'kds_display' => [
            'enabled' => env('KDS_DISPLAY_ENABLED', true),
            
            // Конфигурация дисплеев для станций
            'displays' => [
                // Пример конфигурации:
                // 1 => [
                //     'name' => 'Hot Kitchen Display',
                //     'model' => 'samsung_galaxy_tab_s6', // Модель из DisplayModel enum
                //     'orientation' => 'landscape', // landscape, portrait
                // ],
                //
                // Примеры конфигураций для разных моделей:
                //
                // Samsung Galaxy Tab S6 (Android, 10.4", 2000x1200):
                // 1 => [
                //     'name' => 'Hot Kitchen - Samsung Tab S6',
                //     'model' => 'samsung_galaxy_tab_s6',
                //     'orientation' => 'landscape',
                // ],
                //
                // Microsoft Surface Go 3 (Windows, 10.5", 1920x1280):
                // 2 => [
                //     'name' => 'Bar - Surface Go 3',
                //     'model' => 'microsoft_surface_go3',
                //     'orientation' => 'portrait',
                // ],
                //
                // ELO Touch 1515L (Dedicated, 15", 1024x768):
                // 3 => [
                //     'name' => 'Expedition - ELO 1515L',
                //     'model' => 'elo_touch_1515l',
                //     'orientation' => 'landscape',
                // ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Kitchen Printer Driver
        |--------------------------------------------------------------------------
        |
        | Аппаратные кухонные принтеры (Star Micronics, Epson, Citizen)
        |
        */
        'kitchen_printer' => [
            'enabled' => env('KITCHEN_PRINTER_ENABLED', false),
            'timeout' => env('KITCHEN_PRINTER_TIMEOUT', 10),
            
            // Маппинг станций на принтеры
            'printers' => [
                // Пример конфигурации для каждой станции:
                // 1 => [
                //     'name' => 'Hot Kitchen Printer',
                //     'type' => 'network', // network, usb, bluetooth
                //     'host' => '192.168.1.100',
                //     'port' => 9100,
                //     'device' => '/dev/usb/lp0', // для USB
                // ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Signal System Driver
        |--------------------------------------------------------------------------
        |
        | Звуковая и световая сигнализация для уведомлений
        |
        */
        'signal_system' => [
            'enabled' => env('SIGNAL_SYSTEM_ENABLED', false),
            'api_url' => env('SIGNAL_SYSTEM_API_URL'),
            'api_key' => env('SIGNAL_SYSTEM_API_KEY'),
            'sound_enabled' => env('SIGNAL_SYSTEM_SOUND_ENABLED', true),
            'light_enabled' => env('SIGNAL_SYSTEM_LIGHT_ENABLED', true),
            'timeout' => env('SIGNAL_SYSTEM_TIMEOUT', 5),
        ],

        /*
        |--------------------------------------------------------------------------
        | MQTT Driver
        |--------------------------------------------------------------------------
        |
        | MQTT для связи с планшетами поваров
        |
        */
        'mqtt' => [
            'enabled' => env('MQTT_ENABLED', false),
            'host' => env('MQTT_HOST', 'localhost'),
            'port' => env('MQTT_PORT', 1883),
            'client_id' => env('MQTT_CLIENT_ID', 'catvrf-kds'),
            'username' => env('MQTT_USERNAME'),
            'password' => env('MQTT_PASSWORD'),
            'use_tls' => env('MQTT_USE_TLS', false),
            'tls_self_signed' => env('MQTT_TLS_SELF_SIGNED', true),
            
            // Станции для подписки
            'subscribed_stations' => [],
        ],

        /*
        |--------------------------------------------------------------------------
        | Browser Print Driver
        |--------------------------------------------------------------------------
        |
        | Печать через браузер (для недорогих термопринтеров)
        |
        */
        'browser_print' => [
            'enabled' => env('BROWSER_PRINT_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enabled Drivers for Tenant
    |--------------------------------------------------------------------------
    |
    | Список драйверов, активированных для конкретного tenant
    | Может быть переопределён в настройках tenant
    |
    */
    'enabled_drivers' => [
        'default' => [
            'kds_display',
            'kitchen_printer',
            'signal_system',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Integration
    |--------------------------------------------------------------------------
    |
    | Автоматически отправлять заказы на интеграции при создании
    |
    */
    'auto_send_on_create' => env('KDS_AUTO_SEND_ON_CREATE', true),

    /*
    |--------------------------------------------------------------------------
    | Overdue Alert Threshold
    |--------------------------------------------------------------------------
    |
    | Порог просрочки (в минутах) для автоматического оповещения
    |
    */
    'overdue_alert_threshold' => env('KDS_OVERDUE_ALERT_THRESHOLD', 5),
];
