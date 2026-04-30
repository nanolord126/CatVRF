<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Restaurant CRM Enabled
    |--------------------------------------------------------------------------
    |
    | Включить/выключить модуль ресторанной CRM
    |
    */
    'enabled' => env('CRM_RESTAURANT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Воронки продаж для ресторанов
    |--------------------------------------------------------------------------
    |
    | Конфигурация воронок для разных типов заказов
    |
    */
    'pipelines' => [
        'dine_in' => [
            'name' => 'Заказ в зале',
            'stages' => [
                ['name' => 'Новый заказ', 'order' => 1, 'probability' => 10],
                ['name' => 'Подтверждён', 'order' => 2, 'probability' => 30],
                ['name' => 'Готовится', 'order' => 3, 'probability' => 50],
                ['name' => 'Готов к подаче', 'order' => 4, 'probability' => 80],
                ['name' => 'Подан', 'order' => 5, 'probability' => 100, 'is_won_stage' => true],
            ],
        ],
        'delivery' => [
            'name' => 'Доставка',
            'stages' => [
                ['name' => 'Новый заказ', 'order' => 1, 'probability' => 10],
                ['name' => 'Подтверждён', 'order' => 2, 'probability' => 30],
                ['name' => 'Готовится', 'order' => 3, 'probability' => 50],
                ['name' => 'Готов к доставке', 'order' => 4, 'probability' => 70],
                ['name' => 'Доставляется', 'order' => 5, 'probability' => 90],
                ['name' => 'Доставлен', 'order' => 6, 'probability' => 100, 'is_won_stage' => true],
            ],
        ],
        'pickup' => [
            'name' => 'Самовывоз',
            'stages' => [
                ['name' => 'Новый заказ', 'order' => 1, 'probability' => 10],
                ['name' => 'Подтверждён', 'order' => 2, 'probability' => 30],
                ['name' => 'Готовится', 'order' => 3, 'probability' => 50],
                ['name' => 'Готов к выдаче', 'order' => 4, 'probability' => 100, 'is_won_stage' => true],
            ],
        ],
        'reservation' => [
            'name' => 'Бронирование',
            'stages' => [
                ['name' => 'Запрос на бронь', 'order' => 1, 'probability' => 20],
                ['name' => 'Подтверждён', 'order' => 2, 'probability' => 50],
                ['name' => 'Гость пришёл', 'order' => 3, 'probability' => 80],
                ['name' => 'Завершено', 'order' => 4, 'probability' => 100, 'is_won_stage' => true],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Автоматизации
    |--------------------------------------------------------------------------
    |
    | Настройки автоматических действий
    |
    */
    'automations' => [
        // Автоматическое начисление баллов лояльности
        'loyalty_points_on_order_complete' => true,
        
        // Автоматическое освобождение столика
        'auto_release_table_on_complete' => true,
        
        // Уведомление официанту о готовности
        'notify_waiter_on_ready' => true,
        
        // Отправка заказа на кухню при подтверждении
        'send_to_kitchen_on_confirm' => true,
        
        // Напоминание о бронировании (за N минут)
        'reservation_reminder_minutes' => 30,
        
        // Follow-up после визита (через N дней)
        'follow_up_after_visit_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки лояльности по умолчанию
    |--------------------------------------------------------------------------
    |
    | Значения по умолчанию для программы лояльности (% бонусов на кошелек)
    |
    */
    'loyalty' => [
        'bonus_percentage' => 1.00, // 1% от суммы заказа
        'signup_bonus_amount' => 50.00, // 50 рублей бонус при регистрации
        'birthday_bonus_amount' => 100.00, // 100 рублей бонус на день рождения
        
        'tiers' => [
            'bronze' => [
                'threshold' => 1000, // Сумма трат в рублях
                'bonus_multiplier' => 1.0,
                'benefits' => ['free_drink_after_5_visits'],
            ],
            'silver' => [
                'threshold' => 5000,
                'bonus_multiplier' => 1.2, // +20% к бонусу
                'benefits' => ['10%_discount', 'priority_seating'],
            ],
            'gold' => [
                'threshold' => 20000,
                'bonus_multiplier' => 1.5, // +50% к бонусу
                'benefits' => ['15%_discount', 'free_appetizer', 'vip_events'],
            ],
            'platinum' => [
                'threshold' => 50000,
                'bonus_multiplier' => 2.0, // +100% к бонусу (удвоение)
                'benefits' => ['20%_discount', 'free_dessert', 'personal_manager'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки KDS (Kitchen Display System)
    |--------------------------------------------------------------------------
    |
    | Конфигурация кухонного дисплея
    |
    */
    'kds' => [
        // Автообновление (секунды)
        'refresh_interval' => 30,
        
        // Показывать просроченные заказы
        'show_overdue' => true,
        
        // Цветовая кодировка по времени ожидания
        'time_thresholds' => [
            'warning' => 15,  // минут
            'critical' => 30, // минут
        ],
        
        // Группировка заказов
        'group_by' => 'status', // 'status', 'table', 'time'
    ],

    /*
    |--------------------------------------------------------------------------
    | Интеграция с маркетплейсом CatVRF
    |--------------------------------------------------------------------------
    |
    | Настройки синхронизации с маркетплейсом
    |
    */
    'marketplace_integration' => [
        'enabled' => true,
        
        // Автоматическое создание сделки из заказа маркетплейса
        'auto_create_deal' => true,
        
        // Синхронизация статусов
        'sync_order_status' => true,
        
        // Синхронизация меню
        'sync_menu' => true,
        
        // Webhook URL для уведомлений
        'webhook_url' => env('CRM_RESTAURANT_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки уведомлений
    |--------------------------------------------------------------------------
    |
    | Каналы для отправки уведомлений
    |
    */
    'notifications' => [
        'channels' => [
            'push' => true,
            'telegram' => env('TELEGRAM_ENABLED', false),
            'vk' => env('VK_ENABLED', false),
            'sms' => env('SMS_ENABLED', false),
            'email' => true,
        ],
        
        // Шаблоны уведомлений
        'templates' => [
            'order_confirmed' => 'Ваш заказ #{{order_number}} подтверждён. Ожидаемое время: {{estimated_time}}',
            'order_ready' => 'Ваш заказ #{{order_number}} готов!',
            'order_delivered' => 'Ваш заказ #{{order_number}} доставлен',
            'loyalty_points_earned' => 'Вы получили {{points}} баллов! Ваш баланс: {{balance}}',
            'reservation_reminder' => 'Напоминание: бронь на {{date}} в {{time}}',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fraud Detection
    |--------------------------------------------------------------------------
    |
    | Настройки защиты от мошенничества
    |
    */
    'fraud_detection' => [
        'enabled' => true,
        
        // Максимальное количество заказов с одного IP за час
        'max_orders_per_ip_per_hour' => 10,
        
        // Максимальная сумма заказа без верификации
        'max_amount_without_verification' => 10000,
        
        // Проверка на подозрительные паттерны бронирования
        'detect_suspicious_reservations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Аналитика и отчёты
    |--------------------------------------------------------------------------
    |
    | Настройки сбора аналитики
    |
    */
    'analytics' => [
        // Хранение данных в ClickHouse
        'clickhouse_enabled' => env('CLICKHOUSE_ENABLED', false),
        
        // Агрегация по временным периодам
        'aggregation_periods' => ['hourly', 'daily', 'weekly', 'monthly'],
        
        // Метрики для отслеживания
        'metrics' => [
            'orders_count',
            'revenue',
            'average_check',
            'preparation_time',
            'table_occupancy',
            'loyalty_points_issued',
            'customer_retention',
        ],
    ],

];
