<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Fashion CRM Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация CRM модуля для вертикали моды (Fashion)
    | Включает настройки воронок продаж, заказов, автоматизаций,
    | интеграций с маркетплейсом CatVRF, кэширования и очередей.
    |
    */

    // Основные настройки модуля
    'enabled' => env('FASHION_ENABLED', true),
    
    'default_currency' => env('FASHION_CURRENCY', 'RUB'),
    
    'timezone' => env('FASHION_TIMEZONE', 'Europe/Moscow'),

    // Настройки заказов
    'orders' => [
        // Автоматическое подтверждение заказа
        'auto_confirm' => env('FASHION_AUTO_CONFIRM', false),
        
        // Требование предоплаты
        'require_deposit' => env('FASHION_REQUIRE_DEPOSIT', false),
        
        // Минимальный размер депозита (% от суммы)
        'deposit_percentage' => env('FASHION_DEPOSIT_PERCENTAGE', 0),
        
        // Время на удержание товара (в минутах)
        'hold_time_minutes' => env('FASHION_HOLD_TIME', 30),
        
        // Максимальное количество товаров в заказе
        'max_items_per_order' => env('FASHION_MAX_ITEMS', 50),
    ],

    // Статусы заказов
    'order_statuses' => [
        'pending' => [
            'label' => 'Ожидает',
            'color' => 'warning',
            'icon' => 'clock',
            'next_status' => 'processing',
        ],
        'processing' => [
            'label' => 'В обработке',
            'color' => 'info',
            'icon' => 'cog',
            'next_status' => 'ready',
        ],
        'ready' => [
            'label' => 'Готов к выдаче',
            'color' => 'success',
            'icon' => 'check-circle',
            'next_status' => 'completed',
        ],
        'completed' => [
            'label' => 'Завершён',
            'color' => 'success',
            'icon' => 'check-badge',
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
        'returned' => [
            'label' => 'Возвращён',
            'color' => 'warning',
            'icon' => 'arrow-uturn-down',
            'next_status' => null,
        ],
    ],

    // Настройки воронок продаж
    'sales_funnel' => [
        'stages' => [
            'awareness' => [
                'label' => 'Осведомлённость',
                'probability' => 10,
                'next_stage' => 'interest',
            ],
            'interest' => [
                'label' => 'Интерес',
                'probability' => 25,
                'next_stage' => 'consideration',
            ],
            'consideration' => [
                'label' => 'Рассмотрение',
                'probability' => 50,
                'next_stage' => 'intent',
            ],
            'intent' => [
                'label' => 'Намерение',
                'probability' => 70,
                'next_stage' => 'purchase',
            ],
            'purchase' => [
                'label' => 'Покупка',
                'probability' => 100,
                'next_stage' => 'loyalty',
            ],
            'loyalty' => [
                'label' => 'Лояльность',
                'probability' => 100,
                'next_stage' => null,
            ],
            'churned' => [
                'label' => 'Отток',
                'probability' => 0,
                'next_stage' => null,
            ],
        ],
        
        // Автоматический переход по воронке
        'auto_stage_transition' => env('FASHION_AUTO_STAGE_TRANSITION', true),
    ],

    // Настройки товаров
    'products' => [
        // Категории товаров
        'categories' => [
            'clothing' => 'Одежда',
            'shoes' => 'Обувь',
            'accessories' => 'Аксессуары',
            'bags' => 'Сумки',
            'jewelry' => 'Ювелирные изделия',
        ],
        
        // Размеры
        'sizes' => [
            'xs', 's', 'm', 'l', 'xl', 'xxl', 'xxxl',
            '34', '36', '38', '40', '42', '44', '46', '48',
            '35', '36', '37', '38', '39', '40', '41', '42',
        ],
        
        // Статусы товаров
        'statuses' => [
            'available' => 'Доступен',
            'out_of_stock' => 'Нет в наличии',
            'pre_order' => 'Предзаказ',
            'discontinued' => 'Снят с производства',
        ],
        
        // Управление инвентарём
        'inventory' => [
            'auto_low_stock_alert' => true,
            'low_stock_threshold' => 5,
            'auto_reorder' => false,
        ],
    ],

    // Настройки возвратов
    'returns' => [
        // Дни на возврат
        'return_period_days' => env('FASHION_RETURN_PERIOD', 30),
        
        // Требование оригинальной упаковки
        'require_original_packaging' => env('FASHION_REQUIRE_PACKAGING', true),
        
        // Требование не надевать товар
        'require_unworn' => env('FASHION_REQUIRE_UNWORN', true),
        
        // Автоматическое одобрение возвратов
        'auto_approve' => env('FASHION_AUTO_APPROVE_RETURNS', false),
    ],

    // Настройки автоматизаций
    'automations' => [
        // Автоматические уведомления
        'notifications' => [
            'order_created' => true,
            'order_confirmed' => true,
            'order_ready' => true,
            'order_shipped' => [
                'enabled' => true,
                'include_tracking' => true,
            ],
            'delivery_reminder' => [
                'enabled' => true,
                'hours_before' => 2,
            ],
            'return_reminder' => [
                'enabled' => true,
                'days_before_expiry' => 3,
            ],
        ],
        
        // Автоматическое изменение статусов
        'auto_status_changes' => [
            'confirm_orders' => true,
            'mark_ready' => true,
            'complete_orders' => true,
        ],
        
        // Рекомендации
        'recommendations' => [
            'enabled' => true,
            'cross_sell' => true,
            'up_sell' => true,
            'based_on_history' => true,
        ],
    ],

    // Интеграция с маркетплейсом CatVRF
    'marketplace' => [
        'enabled' => env('FASHION_MARKETPLACE_ENABLED', true),
        
        'sync' => [
            'products' => true,
            'orders' => true,
            'prices' => true,
            'inventory' => true,
        ],
        
        // Интервал синхронизации (в минутах)
        'sync_interval_minutes' => env('FASHION_SYNC_INTERVAL', 15),
        
        // Webhook для уведомлений от маркетплейса
        'webhook_secret' => env('FASHION_WEBHOOK_SECRET'),
    ],

    // Настройки Fraud Detection
    'fraud_detection' => [
        'enabled' => env('FASHION_FRAUD_DETECTION_ENABLED', true),
        
        // Пороговый score для блокировки
        'threshold' => env('FASHION_FRAUD_THRESHOLD', 0.7),
        
        // Проверка истории заказов
        'check_order_history' => true,
        
        // Проверка частоты возвратов
        'check_return_frequency' => true,
        
        // Максимальное количество возвратов за период
        'max_returns_per_month' => env('FASHION_MAX_RETURNS_MONTH', 3),
    ],

    // Настройки кэширования
    'cache' => [
        'enabled' => env('FASHION_CACHE_ENABLED', true),
        
        'ttl' => [
            'products' => env('FASHION_CACHE_TTL_PRODUCTS', 3600), // 1 час
            'orders' => env('FASHION_CACHE_TTL_ORDERS', 300), // 5 минут
            'inventory' => env('FASHION_CACHE_TTL_INVENTORY', 180), // 3 минуты
            'prices' => env('FASHION_CACHE_TTL_PRICES', 600), // 10 минут
        ],
        
        'tags' => [
            'products' => 'fashion:products',
            'orders' => 'fashion:orders',
            'inventory' => 'fashion:inventory',
        ],
    ],

    // Настройки очередей
    'queues' => [
        'order_created' => env('FASHION_QUEUE_ORDER_CREATED', 'fashion'),
        'order_confirmed' => env('FASHION_QUEUE_ORDER_CONFIRMED', 'fashion'),
        'sync_marketplace' => env('FASHION_QUEUE_SYNC', 'fashion-sync'),
        'notifications' => env('FASHION_QUEUE_NOTIFICATIONS', 'fashion-notifications'),
        'fraud_check' => env('FASHION_QUEUE_FRAUD', 'fashion-fraud'),
        'returns' => env('FASHION_QUEUE_RETURNS', 'fashion-returns'),
    ],

    // Настройки аналитики
    'analytics' => [
        'enabled' => env('FASHION_ANALYTICS_ENABLED', true),
        
        // Отслеживаемые метрики
        'metrics' => [
            'conversion_rate' => true,
            'average_order_value' => true,
            'return_rate' => true,
            'popular_products' => true,
            'customer_lifetime_value' => true,
            'seasonal_trends' => true,
        ],
        
        // Хранение аналитики (в днях)
        'retention_days' => env('FASHION_ANALYTICS_RETENTION', 365),
    ],

    // Настройки интеграций
    'integrations' => [
        // CRM системы
        'crm' => [
            'enabled' => env('FASHION_CRM_INTEGRATION', false),
            'provider' => env('FASHION_CRM_PROVIDER'), // amocrm, bitrix24
            'api_key' => env('FASHION_CRM_API_KEY'),
        ],
        
        // Доставка
        'shipping' => [
            'enabled' => env('FASHION_SHIPPING_ENABLED', true),
            'providers' => [
                'cdek' => env('FASHION_CDEK_ENABLED', true),
                'russian_post' => env('FASHION_RUSSIAN_POST_ENABLED', true),
                'courier' => env('FASHION_COURIER_ENABLED', true),
            ],
        ],
        
        // Оплата
        'payment' => [
            'enabled' => env('FASHION_PAYMENT_ENABLED', true),
            'providers' => [
                'tinkoff' => env('FASHION_TINKOFF_ENABLED', true),
                'sber' => env('FASHION_SBER_ENABLED', true),
                'sbp' => env('FASHION_SBP_ENABLED', true),
            ],
        ],
    ],

    // Настройки безопасности
    'security' => [
        // Двухфакторная аутентификация для крупных заказов
        'require_2fa_for_large_orders' => env('FASHION_REQUIRE_2FA_LARGE_ORDERS', false),
        
        // Порог для крупного заказа (в рублях)
        'large_order_threshold' => env('FASHION_LARGE_ORDER_THRESHOLD', 50000),
        
        // Ограничение на количество заказов за день
        'max_orders_per_day' => env('FASHION_MAX_ORDERS_DAY', 10),
        
        // Ограничение на сумму заказа без доп. проверки
        'max_amount_without_verification' => env('FASHION_MAX_AMOUNT_NO_VERIFICATION', 100000), // 100 тыс руб
    ],
];
