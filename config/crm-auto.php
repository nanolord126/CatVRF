<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Auto CRM Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация CRM модуля для вертикали автотранспорта (Auto)
    | Включает настройки воронок продаж, заказов, автоматизаций,
    | интеграций с маркетплейсом CatVRF, кэширования и очередей.
    |
    */

    // Основные настройки модуля
    'enabled' => env('AUTO_ENABLED', true),
    
    'default_currency' => env('AUTO_CURRENCY', 'RUB'),
    
    'timezone' => env('AUTO_TIMEZONE', 'Europe/Moscow'),

    // Настройки заказов
    'orders' => [
        // Автоматическое подтверждение заказа
        'auto_confirm' => env('AUTO_AUTO_CONFIRM', false),
        
        // Требование предоплаты
        'require_deposit' => env('AUTO_REQUIRE_DEPOSIT', true),
        
        // Минимальный размер депозита (% от суммы)
        'deposit_percentage' => env('AUTO_DEPOSIT_PERCENTAGE', 20),
        
        // Время на удержание слота (в минутах)
        'hold_time_minutes' => env('AUTO_HOLD_TIME', 30),
        
        // Максимальное количество услуг в заказе
        'max_services_per_order' => env('AUTO_MAX_SERVICES', 10),
    ],

    // Статусы заказов
    'order_statuses' => [
        'pending' => [
            'label' => 'Ожидает',
            'color' => 'warning',
            'icon' => 'clock',
            'next_status' => 'confirmed',
        ],
        'confirmed' => [
            'label' => 'Подтверждён',
            'color' => 'info',
            'icon' => 'check-circle',
            'next_status' => 'in_progress',
        ],
        'in_progress' => [
            'label' => 'В работе',
            'color' => 'primary',
            'icon' => 'cog',
            'next_status' => 'ready',
        ],
        'ready' => [
            'label' => 'Готов к выдаче',
            'color' => 'success',
            'icon' => 'check-badge',
            'next_status' => 'completed',
        ],
        'completed' => [
            'label' => 'Завершён',
            'color' => 'success',
            'icon' => 'check-circle',
            'next_status' => null,
        ],
        'cancelled' => [
            'label' => 'Отменён',
            'color' => 'danger',
            'icon' => 'x-circle',
            'next_status' => null,
        ],
        'refunded' => [
            'label' => 'Возврат',
            'color' => 'secondary',
            'icon' => 'arrow-uturn-left',
            'next_status' => null,
        ],
    ],

    // Настройки воронок продаж
    'sales_funnel' => [
        'stages' => [
            'lead' => [
                'label' => 'Лид',
                'probability' => 10,
                'next_stage' => 'qualified',
            ],
            'qualified' => [
                'label' => 'Квалифицирован',
                'probability' => 30,
                'next_stage' => 'inspection',
            ],
            'inspection' => [
                'label' => 'Осмотр',
                'probability' => 50,
                'next_stage' => 'quote',
            ],
            'quote' => [
                'label' => 'Коммерческое предложение',
                'probability' => 70,
                'next_stage' => 'negotiation',
            ],
            'negotiation' => [
                'label' => 'Переговоры',
                'probability' => 80,
                'next_stage' => 'closed',
            ],
            'closed' => [
                'label' => 'Сделка закрыта',
                'probability' => 100,
                'next_stage' => null,
            ],
            'lost' => [
                'label' => 'Потерян',
                'probability' => 0,
                'next_stage' => null,
            ],
        ],
        
        // Автоматический переход по воронке
        'auto_stage_transition' => env('AUTO_AUTO_STAGE_TRANSITION', true),
    ],

    // Настройки услуг
    'services' => [
        // Категории услуг
        'categories' => [
            'maintenance' => 'Техническое обслуживание',
            'repair' => 'Ремонт',
            'diagnostics' => 'Диагностика',
            'parts' => 'Запчасти',
            'detailing' => 'Детейлинг',
            'tires' => 'Шиномонтаж',
        ],
        
        // Типы обслуживания
        'maintenance_types' => [
            'oil_change' => 'Замена масла',
            'brake_service' => 'Обслуживание тормозов',
            'filter_change' => 'Замена фильтров',
            'fluid_change' => 'Замена жидкостей',
            'inspection' => 'Техосмотр',
        ],
        
        // Статусы услуг
        'statuses' => [
            'available' => 'Доступна',
            'scheduled' => 'Запланирована',
            'in_progress' => 'В работе',
            'completed' => 'Выполнена',
            'cancelled' => 'Отменена',
        ],
        
        // Управление инвентарём запчастей
        'inventory' => [
            'auto_low_stock_alert' => true,
            'low_stock_threshold' => 3,
            'auto_reorder' => false,
        ],
    ],

    // Настройки автомобилей
    'vehicles' => [
        // Типы автомобилей
        'types' => [
            'sedan' => 'Седан',
            'suv' => 'Внедорожник',
            'hatchback' => 'Хэтчбек',
            'coupe' => 'Купе',
            'truck' => 'Грузовик',
            'van' => 'Фургон',
            'motorcycle' => 'Мотоцикл',
        ],
        
        // Статусы автомобилей
        'statuses' => [
            'in_service' => 'На обслуживании',
            'ready' => 'Готов',
            'waiting' => 'Ожидает',
            'diagnosing' => 'Диагностика',
        ],
    ],

    // Настройки автоматизаций
    'automations' => [
        // Автоматические уведомления
        'notifications' => [
            'order_created' => true,
            'order_confirmed' => true,
            'service_started' => true,
            'service_ready' => true,
            'maintenance_reminder' => [
                'enabled' => true,
                'days_before' => 7,
            ],
            'inspection_reminder' => [
                'enabled' => true,
                'days_before' => 30,
            ],
        ],
        
        // Автоматическое изменение статусов
        'auto_status_changes' => [
            'confirm_orders' => true,
            'start_services' => true,
            'complete_services' => true,
        ],
        
        // Рекомендации по обслуживанию
        'recommendations' => [
            'enabled' => true,
            'based_on_mileage' => true,
            'based_on_time' => true,
            'manufacturer_schedule' => true,
        ],
    ],

    // Интеграция с маркетплейсом CatVRF
    'marketplace' => [
        'enabled' => env('AUTO_MARKETPLACE_ENABLED', true),
        
        'sync' => [
            'services' => true,
            'orders' => true,
            'prices' => true,
            'availability' => true,
        ],
        
        // Интервал синхронизации (в минутах)
        'sync_interval_minutes' => env('AUTO_SYNC_INTERVAL', 15),
        
        // Webhook для уведомлений от маркетплейса
        'webhook_secret' => env('AUTO_WEBHOOK_SECRET'),
    ],

    // Настройки Fraud Detection
    'fraud_detection' => [
        'enabled' => env('AUTO_FRAUD_DETECTION_ENABLED', true),
        
        // Пороговый score для блокировки
        'threshold' => env('AUTO_FRAUD_THRESHOLD', 0.7),
        
        // Проверка истории заказов
        'check_order_history' => true,
        
        // Проверка VIN номера
        'check_vin' => env('AUTO_CHECK_VIN', true),
    ],

    // Настройки кэширования
    'cache' => [
        'enabled' => env('AUTO_CACHE_ENABLED', true),
        
        'ttl' => [
            'services' => env('AUTO_CACHE_TTL_SERVICES', 3600), // 1 час
            'orders' => env('AUTO_CACHE_TTL_ORDERS', 300), // 5 минут
            'inventory' => env('AUTO_CACHE_TTL_INVENTORY', 180), // 3 минуты
            'prices' => env('AUTO_CACHE_TTL_PRICES', 600), // 10 минут
        ],
        
        'tags' => [
            'services' => 'auto:services',
            'orders' => 'auto:orders',
            'inventory' => 'auto:inventory',
        ],
    ],

    // Настройки очередей
    'queues' => [
        'order_created' => env('AUTO_QUEUE_ORDER_CREATED', 'auto'),
        'order_confirmed' => env('AUTO_QUEUE_ORDER_CONFIRMED', 'auto'),
        'sync_marketplace' => env('AUTO_QUEUE_SYNC', 'auto-sync'),
        'notifications' => env('AUTO_QUEUE_NOTIFICATIONS', 'auto-notifications'),
        'fraud_check' => env('AUTO_QUEUE_FRAUD', 'auto-fraud'),
    ],

    // Настройки аналитики
    'analytics' => [
        'enabled' => env('AUTO_ANALYTICS_ENABLED', true),
        
        // Отслеживаемые метрики
        'metrics' => [
            'conversion_rate' => true,
            'average_order_value' => true,
            'service_frequency' => true,
            'popular_services' => true,
            'customer_retention' => true,
        ],
        
        // Хранение аналитики (в днях)
        'retention_days' => env('AUTO_ANALYTICS_RETENTION', 365),
    ],

    // Настройки интеграций
    'integrations' => [
        // CRM системы
        'crm' => [
            'enabled' => env('AUTO_CRM_INTEGRATION', false),
            'provider' => env('AUTO_CRM_PROVIDER'), // amocrm, bitrix24
            'api_key' => env('AUTO_CRM_API_KEY'),
        ],
        
        // Диагностическое оборудование
        'diagnostics' => [
            'enabled' => env('AUTO_DIAGNOSTICS_ENABLED', true),
            'providers' => [
                'obd' => env('AUTO_OBD_ENABLED', true),
                'manufacturer' => env('AUTO_MANUFACTURER_API_ENABLED', false),
            ],
        ],
        
        // Каталог запчастей
        'parts_catalog' => [
            'enabled' => env('AUTO_PARTS_CATALOG_ENABLED', true),
            'providers' => [
                ' TecDoc' => env('AUTO_TECDOC_ENABLED', false),
                'emex' => env('AUTO_EMEX_ENABLED', false),
            ],
        ],
    ],

    // Настройки безопасности
    'security' => [
        // Двухфакторная аутентификация для крупных заказов
        'require_2fa_for_large_orders' => env('AUTO_REQUIRE_2FA_LARGE_ORDERS', false),
        
        // Порог для крупного заказа (в рублях)
        'large_order_threshold' => env('AUTO_LARGE_ORDER_THRESHOLD', 100000),
        
        // Ограничение на количество заказов за день
        'max_orders_per_day' => env('AUTO_MAX_ORDERS_DAY', 10),
        
        // Ограничение на сумму заказа без доп. проверки
        'max_amount_without_verification' => env('AUTO_MAX_AMOUNT_NO_VERIFICATION', 50000), // 50 тыс руб
    ],
];
