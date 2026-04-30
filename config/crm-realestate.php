<?php

declare(strict_types=1);

use Modules\RealEstate\Enums\BookingStatus;

return [
    /*
    |--------------------------------------------------------------------------
    | RealEstate CRM Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация CRM модуля для вертикали недвижимости (RealEstate)
    | Включает настройки воронок продаж, бронирований, автоматизаций,
    | интеграций с маркетплейсом CatVRF, кэширования и очередей.
    |
    */

    // Основные настройки модуля
    'enabled' => env('REAL_ESTATE_ENABLED', true),
    
    'default_currency' => env('REAL_ESTATE_CURRENCY', 'RUB'),
    
    'timezone' => env('REAL_ESTATE_TIMEZONE', 'Europe/Moscow'),

    // Настройки бронирований
    'bookings' => [
        // Время жизни слота на просмотр (в минутах)
        'slot_lifetime_minutes' => env('REAL_ESTATE_SLOT_LIFETIME', 60),
        
        // Максимальное количество слотов для бронирования
        'max_slots_per_booking' => env('REAL_ESTATE_MAX_SLOTS', 5),
        
        // Минимальное время между слотами (в минутах)
        'min_slot_interval_minutes' => env('REAL_ESTATE_MIN_SLOT_INTERVAL', 30),
        
        // Автоматическое подтверждение бронирования
        'auto_confirm_booking' => env('REAL_ESTATE_AUTO_CONFIRM', false),
        
        // Требование предоплаты для бронирования
        'require_deposit' => env('REAL_ESTATE_REQUIRE_DEPOSIT', true),
        
        // Минимальный размер депозита (% от суммы)
        'deposit_percentage' => env('REAL_ESTATE_DEPOSIT_PERCENTAGE', 10),
    ],

    // Статусы бронирований
    'booking_statuses' => [
        'pending' => [
            'label' => 'Ожидает',
            'color' => 'warning',
            'icon' => 'clock',
            'next_status' => 'confirmed',
        ],
        'confirmed' => [
            'label' => 'Подтверждено',
            'color' => 'info',
            'icon' => 'check-circle',
            'next_status' => 'completed',
        ],
        'completed' => [
            'label' => 'Завершено',
            'color' => 'success',
            'icon' => 'check-badge',
            'next_status' => null,
        ],
        'cancelled' => [
            'label' => 'Отменено',
            'color' => 'danger',
            'icon' => 'x-circle',
            'next_status' => null,
        ],
        'expired' => [
            'label' => 'Истёк',
            'color' => 'gray',
            'icon' => 'hourglass',
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
                'next_stage' => 'viewing_scheduled',
            ],
            'viewing_scheduled' => [
                'label' => 'Просмотр запланирован',
                'probability' => 50,
                'next_stage' => 'viewing_completed',
            ],
            'viewing_completed' => [
                'label' => 'Просмотр завершён',
                'probability' => 70,
                'next_stage' => 'negotiation',
            ],
            'negotiation' => [
                'label' => 'Переговоры',
                'probability' => 80,
                'next_stage' => 'contract_signed',
            ],
            'contract_signed' => [
                'label' => 'Договор подписан',
                'probability' => 90,
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
        'auto_stage_transition' => env('REAL_ESTATE_AUTO_STAGE_TRANSITION', true),
    ],

    // Настройки объектов недвижимости
    'properties' => [
        // Типы объектов
        'types' => [
            'apartment' => 'Квартира',
            'house' => 'Дом',
            'commercial' => 'Коммерческое',
            'land' => 'Земельный участок',
            'garage' => 'Гараж',
        ],
        
        // Статусы объектов
        'statuses' => [
            'available' => 'Доступен',
            'reserved' => 'Зарезервирован',
            'sold' => 'Продан',
            'under_contract' => 'В договоре',
            'maintenance' => 'На обслуживании',
        ],
        
        // Автоматическое снятие с резерва (в часах)
        'reserve_expiry_hours' => env('REAL_ESTATE_RESERVE_EXPIRY', 24),
    ],

    // Настройки автоматизаций
    'automations' => [
        // Автоматические уведомления
        'notifications' => [
            'booking_created' => true,
            'booking_confirmed' => true,
            'booking_reminder' => [
                'enabled' => true,
                'hours_before' => 24,
            ],
            'viewing_reminder' => [
                'enabled' => true,
                'hours_before' => 2,
            ],
            'contract_expiry' => [
                'enabled' => true,
                'days_before' => 7,
            ],
        ],
        
        // Автоматическое изменение статусов
        'auto_status_changes' => [
            'expire_bookings' => true,
            'release_reservations' => true,
            'update_funnel_stages' => true,
        ],
    ],

    // Интеграция с маркетплейсом CatVRF
    'marketplace' => [
        'enabled' => env('REAL_ESTATE_MARKETPLACE_ENABLED', true),
        
        'sync' => [
            'properties' => true,
            'bookings' => true,
            'prices' => true,
            'availability' => true,
        ],
        
        // Интервал синхронизации (в минутах)
        'sync_interval_minutes' => env('REAL_ESTATE_SYNC_INTERVAL', 15),
        
        // Webhook для уведомлений от маркетплейса
        'webhook_secret' => env('REAL_ESTATE_WEBHOOK_SECRET'),
    ],

    // Настройки Fraud Detection
    'fraud_detection' => [
        'enabled' => env('REAL_ESTATE_FRAUD_DETECTION_ENABLED', true),
        
        // Пороговый score для блокировки
        'threshold' => env('REAL_ESTATE_FRAUD_THRESHOLD', 0.7),
        
        // Проверка Face ID
        'face_id_verification' => env('REAL_ESTATE_FACE_ID_VERIFICATION', true),
        
        // Проверка Blockchain
        'blockchain_verification' => env('REAL_ESTATE_BLOCKCHAIN_VERIFICATION', false),
        
        // Проверка B2B клиентов
        'b2b_verification' => env('REAL_ESTATE_B2B_VERIFICATION', true),
    ],

    // Настройки кэширования
    'cache' => [
        'enabled' => env('REAL_ESTATE_CACHE_ENABLED', true),
        
        'ttl' => [
            'properties' => env('REAL_ESTATE_CACHE_TTL_PROPERTIES', 3600), // 1 час
            'bookings' => env('REAL_ESTATE_CACHE_TTL_BOOKINGS', 300), // 5 минут
            'availability' => env('REAL_ESTATE_CACHE_TTL_AVAILABILITY', 180), // 3 минуты
            'prices' => env('REAL_ESTATE_CACHE_TTL_PRICES', 600), // 10 минут
        ],
        
        'tags' => [
            'properties' => 'realestate:properties',
            'bookings' => 'realestate:bookings',
            'availability' => 'realestate:availability',
        ],
    ],

    // Настройки очередей
    'queues' => [
        'booking_created' => env('REAL_ESTATE_QUEUE_BOOKING_CREATED', 'realestate'),
        'booking_confirmed' => env('REAL_ESTATE_QUEUE_BOOKING_CONFIRMED', 'realestate'),
        'sync_marketplace' => env('REAL_ESTATE_QUEUE_SYNC', 'realestate-sync'),
        'notifications' => env('REAL_ESTATE_QUEUE_NOTIFICATIONS', 'realestate-notifications'),
        'fraud_check' => env('REAL_ESTATE_QUEUE_FRAUD', 'realestate-fraud'),
    ],

    // Настройки аналитики
    'analytics' => [
        'enabled' => env('REAL_ESTATE_ANALYTICS_ENABLED', true),
        
        // Отслеживаемые метрики
        'metrics' => [
            'conversion_rate' => true,
            'average_deal_value' => true,
            'time_to_close' => true,
            'viewing_conversion' => true,
            'source_tracking' => true,
        ],
        
        // Хранение аналитики (в днях)
        'retention_days' => env('REAL_ESTATE_ANALYTICS_RETENTION', 365),
    ],

    // Настройки интеграций
    'integrations' => [
        // CRM системы
        'crm' => [
            'enabled' => env('REAL_ESTATE_CRM_INTEGRATION', false),
            'provider' => env('REAL_ESTATE_CRM_PROVIDER'), // amocrm, bitrix24
            'api_key' => env('REAL_ESTATE_CRM_API_KEY'),
        ],
        
        // Карты
        'maps' => [
            'enabled' => env('REAL_ESTATE_MAPS_ENABLED', true),
            'provider' => env('REAL_ESTATE_MAPS_PROVIDER', 'yandex'), // yandex, google, 2gis
            'api_key' => env('REAL_ESTATE_MAPS_API_KEY'),
        ],
        
        // Документы
        'documents' => [
            'enabled' => env('REAL_ESTATE_DOCUMENTS_ENABLED', true),
            'auto_generate' => env('REAL_ESTATE_AUTO_GENERATE_DOCS', true),
        ],
    ],

    // Настройки безопасности
    'security' => [
        // Двухфакторная аутентификация для сделок
        'require_2fa_for_deals' => env('REAL_ESTATE_REQUIRE_2FA_DEALS', false),
        
        // Ограничение на количество бронирований за день
        'max_bookings_per_day' => env('REAL_ESTATE_MAX_BOOKINGS_DAY', 10),
        
        // Ограничение на сумму сделки без доп. проверки
        'max_amount_without_verification' => env('REAL_ESTATE_MAX_AMOUNT_NO_VERIFICATION', 10000000), // 10 млн руб
    ],
];
