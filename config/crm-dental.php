<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Dental CRM Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация CRM модуля для вертикали стоматологии (Dental)
    | Включает настройки воронок продаж, планов лечения, автоматизаций,
    | интеграций с маркетплейсом CatVRF, кэширования и очередей.
    |
    */

    // Основные настройки модуля
    'enabled' => env('DENTAL_ENABLED', true),
    
    'default_currency' => env('DENTAL_CURRENCY', 'RUB'),
    
    'timezone' => env('DENTAL_TIMEZONE', 'Europe/Moscow'),

    // Настройки планов лечения
    'treatment_plans' => [
        // Автоматическое подтверждение плана лечения
        'auto_confirm' => env('DENTAL_AUTO_CONFIRM', false),
        
        // Требование предоплаты
        'require_deposit' => env('DENTAL_REQUIRE_DEPOSIT', true),
        
        // Минимальный размер депозита (% от суммы)
        'deposit_percentage' => env('DENTAL_DEPOSIT_PERCENTAGE', 30),
        
        // Время на удержание слота (в минутах)
        'hold_time_minutes' => env('DENTAL_HOLD_TIME', 60),
        
        // Максимальное количество процедур в плане
        'max_procedures_per_plan' => env('DENTAL_MAX_PROCEDURES', 20),
    ],

    // Статусы планов лечения
    'treatment_statuses' => [
        'draft' => [
            'label' => 'Черновик',
            'color' => 'gray',
            'icon' => 'document',
            'next_status' => 'active',
        ],
        'active' => [
            'label' => 'Активен',
            'color' => 'success',
            'icon' => 'play',
            'next_status' => 'in_progress',
        ],
        'in_progress' => [
            'label' => 'В процессе',
            'color' => 'info',
            'icon' => 'clock',
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
        'on_hold' => [
            'label' => 'Приостановлен',
            'color' => 'warning',
            'icon' => 'pause',
            'next_status' => 'active',
        ],
    ],

    // Настройки воронок продаж
    'sales_funnel' => [
        'stages' => [
            'lead' => [
                'label' => 'Лид',
                'probability' => 10,
                'next_stage' => 'consultation',
            ],
            'consultation' => [
                'label' => 'Консультация',
                'probability' => 30,
                'next_stage' => 'diagnosis',
            ],
            'diagnosis' => [
                'label' => 'Диагностика',
                'probability' => 50,
                'next_stage' => 'treatment_plan',
            ],
            'treatment_plan' => [
                'label' => 'План лечения',
                'probability' => 70,
                'next_stage' => 'treatment',
            ],
            'treatment' => [
                'label' => 'Лечение',
                'probability' => 90,
                'next_stage' => 'completed',
            ],
            'completed' => [
                'label' => 'Завершено',
                'probability' => 100,
                'next_stage' => 'retention',
            ],
            'retention' => [
                'label' => 'Удержание',
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
        'auto_stage_transition' => env('DENTAL_AUTO_STAGE_TRANSITION', true),
    ],

    // Настройки процедур
    'procedures' => [
        // Категории процедур
        'categories' => [
            'diagnostics' => 'Диагностика',
            'preventive' => 'Профилактика',
            'restorative' => 'Восстановительное лечение',
            'endodontic' => 'Эндодонтия',
            'periodontal' => 'Пародонтология',
            'prosthetic' => 'Протезирование',
            'orthodontic' => 'Ортодонтия',
            'surgical' => 'Хирургия',
            'cosmetic' => 'Эстетическая стоматология',
        ],
        
        // Типы процедур
        'procedure_types' => [
            'examination' => 'Осмотр',
            'xray' => 'Рентген',
            'cleaning' => 'Чистка',
            'filling' => 'Пломбирование',
            'root_canal' => 'Лечение каналов',
            'extraction' => 'Удаление',
            'crown' => 'Коронка',
            'implant' => 'Имплантация',
            'braces' => 'Брекеты',
        ],
        
        // Статусы процедур
        'statuses' => [
            'scheduled' => 'Запланирована',
            'in_progress' => 'В процессе',
            'completed' => 'Выполнена',
            'cancelled' => 'Отменена',
            'postponed' => 'Отложена',
        ],
        
        // Управление инвентарём материалов
        'inventory' => [
            'auto_low_stock_alert' => true,
            'low_stock_threshold' => 5,
            'auto_reorder' => false,
        ],
    ],

    // Настройки пациентов
    'patients' => [
        // Статусы пациентов
        'statuses' => [
            'active' => 'Активен',
            'inactive' => 'Неактивен',
            'new' => 'Новый',
            'vip' => 'VIP',
        ],
        
        // История болезни
        'medical_history' => [
            'required' => true,
            'auto_update' => true,
            'retention_years' => 10,
        ],
        
        // Напоминания о приёмах
        'reminders' => [
            'enabled' => true,
            'sms' => true,
            'email' => true,
            'push' => true,
        ],
    ],

    // Настройки зубных карт
    'tooth_charts' => [
        // Система нумерации зубов
        'numbering_system' => env('DENTAL_NUMBERING_SYSTEM', 'fdi'), // fdi, universal, palmer
        
        // Автоматическое обновление зубной карты
        'auto_update' => env('DENTAL_AUTO_UPDATE_TOOTH_CHART', true),
        
        // Хранение истории изменений
        'track_changes' => env('DENTAL_TRACK_TOOTH_CHANGES', true),
    ],

    // Настройки автоматизаций
    'automations' => [
        // Автоматические уведомления
        'notifications' => [
            'appointment_created' => true,
            'appointment_confirmed' => true,
            'appointment_reminder' => [
                'enabled' => true,
                'hours_before' => 24,
            ],
            'treatment_reminder' => [
                'enabled' => true,
                'days_before' => 7,
            ],
            'follow_up' => [
                'enabled' => true,
                'days_after' => 3,
            ],
            'recall' => [
                'enabled' => true,
                'months_after' => 6,
            ],
        ],
        
        // Автоматическое изменение статусов
        'auto_status_changes' => [
            'confirm_appointments' => true,
            'complete_treatments' => true,
            'update_tooth_charts' => true,
        ],
        
        // Рекомендации по лечению
        'recommendations' => [
            'enabled' => true,
            'based_on_history' => true,
            'based_on_age' => true,
            'preventive_care' => true,
        ],
    ],

    // Интеграция с маркетплейсом CatVRF
    'marketplace' => [
        'enabled' => env('DENTAL_MARKETPLACE_ENABLED', true),
        
        'sync' => [
            'procedures' => true,
            'appointments' => true,
            'prices' => true,
            'availability' => true,
        ],
        
        // Интервал синхронизации (в минутах)
        'sync_interval_minutes' => env('DENTAL_SYNC_INTERVAL', 15),
        
        // Webhook для уведомлений от маркетплейса
        'webhook_secret' => env('DENTAL_WEBHOOK_SECRET'),
    ],

    // Настройки Fraud Detection
    'fraud_detection' => [
        'enabled' => env('DENTAL_FRAUD_DETECTION_ENABLED', true),
        
        // Пороговый score для блокировки
        'threshold' => env('DENTAL_FRAUD_THRESHOLD', 0.7),
        
        // Проверка истории пациентов
        'check_patient_history' => true,
        
        // Проверка страховых полисов
        'check_insurance' => env('DENTAL_CHECK_INSURANCE', true),
    ],

    // Настройки кэширования
    'cache' => [
        'enabled' => env('DENTAL_CACHE_ENABLED', true),
        
        'ttl' => [
            'procedures' => env('DENTAL_CACHE_TTL_PROCEDURES', 3600), // 1 час
            'appointments' => env('DENTAL_CACHE_TTL_APPOINTMENTS', 300), // 5 минут
            'inventory' => env('DENTAL_CACHE_TTL_INVENTORY', 180), // 3 минуты
            'prices' => env('DENTAL_CACHE_TTL_PRICES', 600), // 10 минут
        ],
        
        'tags' => [
            'procedures' => 'dental:procedures',
            'appointments' => 'dental:appointments',
            'inventory' => 'dental:inventory',
        ],
    ],

    // Настройки очередей
    'queues' => [
        'appointment_created' => env('DENTAL_QUEUE_APPOINTMENT_CREATED', 'dental'),
        'appointment_confirmed' => env('DENTAL_QUEUE_APPOINTMENT_CONFIRMED', 'dental'),
        'sync_marketplace' => env('DENTAL_QUEUE_SYNC', 'dental-sync'),
        'notifications' => env('DENTAL_QUEUE_NOTIFICATIONS', 'dental-notifications'),
        'fraud_check' => env('DENTAL_QUEUE_FRAUD', 'dental-fraud'),
    ],

    // Настройки аналитики
    'analytics' => [
        'enabled' => env('DENTAL_ANALYTICS_ENABLED', true),
        
        // Отслеживаемые метрики
        'metrics' => [
            'conversion_rate' => true,
            'average_treatment_value' => true,
            'patient_retention' => true,
            'treatment_frequency' => true,
            'revenue_per_patient' => true,
        ],
        
        // Хранение аналитики (в днях)
        'retention_days' => env('DENTAL_ANALYTICS_RETENTION', 365),
    ],

    // Настройки интеграций
    'integrations' => [
        // CRM системы
        'crm' => [
            'enabled' => env('DENTAL_CRM_INTEGRATION', false),
            'provider' => env('DENTAL_CRM_PROVIDER'), // amocrm, bitrix24
            'api_key' => env('DENTAL_CRM_API_KEY'),
        ],
        
        // Медицинское оборудование
        'equipment' => [
            'enabled' => env('DENTAL_EQUIPMENT_ENABLED', true),
            'providers' => [
                'xray' => env('DENTAL_XRAY_INTEGRATION', false),
                'intraoral_camera' => env('DENTAL_CAMERA_INTEGRATION', false),
                'cad_cam' => env('DENTAL_CADCAM_INTEGRATION', false),
            ],
        ],
        
        // Страховые компании
        'insurance' => [
            'enabled' => env('DENTAL_INSURANCE_ENABLED', true),
            'providers' => [
                'dms' => env('DENTAL_DMS_ENABLED', false),
                'vhi' => env('DENTAL_VHI_ENABLED', false),
            ],
        ],
    ],

    // Настройки безопасности и compliance
    'security' => [
        // Соответствие 152-ФЗ
        'compliance_152_fz' => env('DENTAL_COMPLIANCE_152_FZ', true),
        
        // Шифрование медицинских данных
        'encrypt_medical_data' => env('DENTAL_ENCRYPT_MEDICAL_DATA', true),
        
        // Двухфакторная аутентификация для доступа к медицинским данным
        'require_2fa_for_medical_data' => env('DENTAL_REQUIRE_2FA_MEDICAL', true),
        
        // Ограничение на количество записей за день
        'max_appointments_per_day' => env('DENTAL_MAX_APPOINTMENTS_DAY', 50),
        
        // Ограничение на сумму лечения без доп. проверки
        'max_amount_without_verification' => env('DENTAL_MAX_AMOUNT_NO_VERIFICATION', 100000), // 100 тыс руб
    ],
];
