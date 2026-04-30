<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CRM Module Configuration — CatVRF 2026
    |--------------------------------------------------------------------------
    |
    | Настройки CRM-модуля: сегментация, автоматизации, спящие клиенты,
    | вертикали и лимиты.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Включение/выключение модуля CRM
    |--------------------------------------------------------------------------
    */
    'enabled' => env('CRM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Порог «спящих» клиентов (дни неактивности)
    |--------------------------------------------------------------------------
    */
    'sleeping_threshold_days' => [
        'default' => 60,
        'beauty' => 30,
        'hotel' => 90,
        'flowers' => 45,
        'auto' => 90,
        'food' => 14,
        'furniture' => 120,
        'fashion' => 60,
        'fitness' => 30,
        'real_estate' => 120,
        'medical' => 90,
        'dental' => 90,
        'education' => 60,
        'travel' => 120,
        'pet' => 60,
        'vet_grooming' => 45,
        'taxi' => 14,
        'electronics' => 60,
        'events' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Loyalty тиры
    |--------------------------------------------------------------------------
    */
    'loyalty_tiers' => [
        'bronze' => [
            'min_spent' => 0,
            'discount_percent' => 0,
        ],
        'silver' => [
            'min_spent' => 10000,
            'discount_percent' => 3,
        ],
        'gold' => [
            'min_spent' => 50000,
            'discount_percent' => 5,
        ],
        'platinum' => [
            'min_spent' => 150000,
            'discount_percent' => 8,
        ],
        'diamond' => [
            'min_spent' => 500000,
            'discount_percent' => 12,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Источники клиентов (для аналитики)
    |--------------------------------------------------------------------------
    */
    'client_sources' => [
        'marketplace',
        'website',
        'referral',
        'social',
        'ads',
        'offline',
        'b2b_api',
        'import',
        'ai_constructor',
        'call_center',
    ],

    /*
    |--------------------------------------------------------------------------
    | Типы клиентов
    |--------------------------------------------------------------------------
    */
    'client_types' => [
        'individual',
        'business',
        'vip',
        'wholesale',
        'corporate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Типы взаимодействий
    |--------------------------------------------------------------------------
    */
    'interaction_types' => [
        'call',
        'email',
        'sms',
        'visit',
        'order',
        'support',
        'complaint',
        'feedback',
        'referral',
        'note',
        'task',
    ],

    /*
    |--------------------------------------------------------------------------
    | Каналы взаимодействий
    |--------------------------------------------------------------------------
    */
    'interaction_channels' => [
        'phone',
        'email',
        'telegram',
        'whatsapp',
        'in_person',
        'marketplace',
        'app',
        'website',
        'social',
    ],

    /*
    |--------------------------------------------------------------------------
    | Лимиты
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_interactions_per_day' => 100,
        'max_segments_per_tenant' => 50,
        'max_automations_per_tenant' => 30,
        'max_tags_per_client' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Автоматизации
    |--------------------------------------------------------------------------
    */
    'automations' => [
        'queue' => env('CRM_AUTOMATIONS_QUEUE', 'crm-automations'),
        'segments_queue' => env('CRM_SEGMENTS_QUEUE', 'crm-segments'),
        'notifications_queue' => env('CRM_NOTIFICATIONS_QUEUE', 'crm-notifications'),
        'max_batch_size' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Поддерживаемые вертикали CRM
    |--------------------------------------------------------------------------
    */
    'verticals' => [
        'beauty',
        'hotel',
        'flowers',
        'auto',
        'food',
        'furniture',
        'fashion',
        'fitness',
        'real_estate',
        'medical',
        'education',
        'travel',
        'pet',
        'taxi',
        'electronics',
        'events',
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки воронок по вертикалям
    |--------------------------------------------------------------------------
    */
    'pipelines' => [
        'fitness' => [
            'name' => 'Фитнес',
            'stages' => [
                ['name' => 'Лид', 'order' => 1, 'probability' => 10],
                ['name' => 'Консультация', 'order' => 2, 'probability' => 30],
                ['name' => 'Пробное занятие', 'order' => 3, 'probability' => 50],
                ['name' => 'Продажа абонемента', 'order' => 4, 'probability' => 70],
                ['name' => 'Запись', 'order' => 5, 'probability' => 85],
                ['name' => 'Посещение', 'order' => 6, 'probability' => 95],
                ['name' => 'Продление', 'order' => 7, 'probability' => 100, 'is_won_stage' => true],
            ],
        ],
        'dental' => [
            'name' => 'Стоматология',
            'stages' => [
                ['name' => 'Запись', 'order' => 1, 'probability' => 15],
                ['name' => 'Подтверждение', 'order' => 2, 'probability' => 30],
                ['name' => 'Ожидание', 'order' => 3, 'probability' => 50],
                ['name' => 'Прием', 'order' => 4, 'probability' => 75],
                ['name' => 'Лечение', 'order' => 5, 'probability' => 90],
                ['name' => 'Завершено', 'order' => 6, 'probability' => 100, 'is_won_stage' => true],
            ],
        ],
        'real_estate' => [
            'name' => 'Недвижимость',
            'stages' => [
                ['name' => 'Лид', 'order' => 1, 'probability' => 10],
                ['name' => 'Консультация', 'order' => 2, 'probability' => 25],
                ['name' => 'Просмотр', 'order' => 3, 'probability' => 40],
                ['name' => 'Предложение', 'order' => 4, 'probability' => 60],
                ['name' => 'Переговоры', 'order' => 5, 'probability' => 75],
                ['name' => 'Договор', 'order' => 6, 'probability' => 90],
                ['name' => 'Сделка', 'order' => 7, 'probability' => 100, 'is_won_stage' => true],
            ],
        ],
        'default' => [
            'name' => 'Бьюти-салоны',
            'stages' => [
                ['name' => 'Запись', 'order' => 1, 'probability' => 20],
                ['name' => 'Подтверждение', 'order' => 2, 'probability' => 50],
                ['name' => 'Ожидание', 'order' => 3, 'probability' => 70],
                ['name' => 'Услуга', 'order' => 4, 'probability' => 90],
                ['name' => 'Завершено', 'order' => 5, 'probability' => 100, 'is_won_stage' => true],
            ],
        ],
        'flowers' => [
            'name' => 'Флористы',
            'stages' => [
                ['name' => 'Заказ', 'order' => 1, 'probability' => 15],
                ['name' => 'Дизайн', 'order' => 2, 'probability' => 30],
                ['name' => 'Сборка', 'order' => 3, 'probability' => 60],
                ['name' => 'Доставка', 'order' => 4, 'probability' => 85],
                ['name' => 'Доставлено', 'order' => 5, 'probability' => 100, 'is_won_stage' => true],
            ],
        ],
        'taxi' => [
            'name' => 'Такси',
            'stages' => [
                ['name' => 'Новый заказ', 'order' => 1, 'probability' => 10],
                ['name' => 'Назначение водителя', 'order' => 2, 'probability' => 30],
                ['name' => 'Подача', 'order' => 3, 'probability' => 50],
                ['name' => 'Поездка', 'order' => 4, 'probability' => 80],
                ['name' => 'Завершено', 'order' => 5, 'probability' => 100, 'is_won_stage' => true],
            ],
        ],
        'default' => [
            'name' => 'Базовая воронка',
            'stages' => [
                ['name' => 'Лид', 'order' => 1, 'probability' => 10],
                ['name' => 'Квалификация', 'order' => 2, 'probability' => 25],
                ['name' => 'Предложение', 'order' => 3, 'probability' => 50],
                ['name' => 'Переговоры', 'order' => 4, 'probability' => 75],
                ['name' => 'Выиграно', 'order' => 5, 'probability' => 100, 'is_won_stage' => true],
                ['name' => 'Проиграно', 'order' => 6, 'probability' => 0, 'is_lost_stage' => true],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Автоматизации по вертикалям
    |--------------------------------------------------------------------------
    */
    'vertical_automations' => [
        'hotels' => [
            'check_in_reminder' => [
                'enabled' => true,
                'hours_before' => 24,
                'channels' => ['sms', 'email'],
            ],
            'check_out_reminder' => [
                'enabled' => true,
                'hours_before' => 2,
                'channels' => ['sms'],
            ],
        ],
        'beauty' => [
            'appointment_reminder' => [
                'enabled' => true,
                'hours_before' => 2,
                'channels' => ['sms', 'telegram'],
            ],
            'follow_up' => [
                'enabled' => true,
                'days_after' => 7,
                'channels' => ['email'],
            ],
        ],
        'flowers' => [
            'delivery_notification' => [
                'enabled' => true,
                'channels' => ['sms'],
            ],
            'photo_report' => [
                'enabled' => true,
                'hours_after' => 1,
                'channels' => ['telegram', 'whatsapp'],
            ],
        ],
        'taxi' => [
            'driver_assignment' => [
                'enabled' => true,
                'timeout_minutes' => 5,
            ],
            'eta_notification' => [
                'enabled' => true,
                'channels' => ['sms', 'app'],
            ],
        ],
        'vet_grooming' => [
            'appointment_reminder' => [
                'enabled' => true,
                'hours_before' => 24,
                'channels' => ['sms', 'email'],
            ],
            'vaccination_reminder' => [
                'enabled' => true,
                'days_before' => 7,
                'channels' => ['sms', 'email'],
            ],
            'follow_up' => [
                'enabled' => true,
                'days_after' => 3,
                'channels' => ['email'],
            ],
        ],
        'fitness' => [
            'booking_reminder' => [
                'enabled' => true,
                'hours_before' => 2,
                'channels' => ['sms', 'telegram'],
            ],
            'membership_expiry' => [
                'enabled' => true,
                'days_before' => [7, 3, 1],
                'channels' => ['sms', 'email'],
            ],
            'follow_up' => [
                'enabled' => true,
                'days_after' => 1,
                'channels' => ['email'],
            ],
        ],
        'dental' => [
            'appointment_reminder' => [
                'enabled' => true,
                'hours_before' => 24,
                'channels' => ['sms', 'email'],
            ],
            'follow_up_reminder' => [
                'enabled' => true,
                'days_before' => 3,
                'channels' => ['sms', 'email'],
            ],
        ],
        'real_estate' => [
            'viewing_reminder' => [
                'enabled' => true,
                'hours_before' => 24,
                'channels' => ['sms', 'email'],
            ],
            'new_listing_notification' => [
                'enabled' => true,
                'channels' => ['email', 'telegram'],
            ],
            'offer_deadline' => [
                'enabled' => true,
                'days_before' => [7, 3, 1],
                'channels' => ['email', 'sms'],
            ],
        ],
        'auto' => [
            'appointment_reminder' => [
                'enabled' => true,
                'hours_before' => 24,
                'channels' => ['sms', 'email'],
            ],
            'vehicle_ready' => [
                'enabled' => true,
                'channels' => ['sms', 'email'],
            ],
            'warranty_reminder' => [
                'enabled' => true,
                'days_before' => [7, 3],
                'channels' => ['sms'],
            ],
        ],
        'fashion' => [
            'order_ready' => [
                'enabled' => true,
                'channels' => ['sms', 'email'],
            ],
            'try_on_reminder' => [
                'enabled' => true,
                'hours_before' => 24,
                'channels' => ['sms', 'email'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Типы задач в CRM
    |--------------------------------------------------------------------------
    */
    'task_types' => [
        'call' => 'Звонок',
        'email' => 'Email',
        'meeting' => 'Встреча',
        'follow_up' => 'Follow-up',
        'document' => 'Документ',
        'payment' => 'Оплата',
        'delivery' => 'Доставка',
        'custom' => 'Кастомная',
    ],

    /*
    |--------------------------------------------------------------------------
    | Приоритеты задач
    |--------------------------------------------------------------------------
    */
    'task_priorities' => [
        'low' => ['name' => 'Низкий', 'weight' => 1, 'color' => '#94a3b8'],
        'medium' => ['name' => 'Средний', 'weight' => 2, 'color' => '#f59e0b'],
        'high' => ['name' => 'Высокий', 'weight' => 3, 'color' => '#ef4444'],
        'urgent' => ['name' => 'Срочный', 'weight' => 4, 'color' => '#dc2626'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Статусы сделок
    |--------------------------------------------------------------------------
    */
    'deal_statuses' => [
        'new' => 'Новый',
        'in_progress' => 'В работе',
        'negotiation' => 'Переговоры',
        'won' => 'Выигран',
        'lost' => 'Проигран',
        'cancelled' => 'Отменен',
    ],

    /*
    |--------------------------------------------------------------------------
    | Интеграция с заказами CatVRF
    |--------------------------------------------------------------------------
    */
    'integration' => [
        'orders' => [
            'auto_create_deals' => env('CRM_AUTO_CREATE_DEALS_FROM_ORDERS', true),
            'sync_on_order_status_change' => env('CRM_SYNC_ON_ORDER_STATUS_CHANGE', true),
            'sync_on_order_payment' => env('CRM_SYNC_ON_ORDER_PAYMENT', true),
        ],
        'payments' => [
            'auto_update_deal_status' => env('CRM_AUTO_UPDATE_DEAL_ON_PAYMENT', true),
        ],
        'analytics' => [
            'sync_to_clickhouse' => env('CRM_SYNC_TO_CLICKHOUSE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки UI
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'default_pipeline_view' => 'kanban',
        'items_per_page' => 20,
        'enable_drag_and_drop' => true,
        'enable_bulk_actions' => true,
    ],
];
