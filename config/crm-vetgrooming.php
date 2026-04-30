<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | VetGrooming CRM Module Status
    |--------------------------------------------------------------------------
    |
    | Enable or disable the VetGrooming CRM module.
    |
    */
    'enabled' => env('VETGROOMING_CRM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for pricing and payments.
    |
    */
    'default_currency' => env('VETGROOMING_CRM_CURRENCY', 'RUB'),

    /*
    |--------------------------------------------------------------------------
    | Grooming Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for grooming sessions and appointments.
    |
    */
    'grooming' => [
        // Maximum advance booking (in days)
        'max_advance_booking_days' => (int) env('VETGROOMING_MAX_ADVANCE_BOOKING', 30),

        // Minimum advance booking (in hours)
        'min_advance_booking_hours' => (int) env('VETGROOMING_MIN_ADVANCE_BOOKING', 4),

        // Cancellation deadline (in hours)
        'cancellation_deadline_hours' => (int) env('VETGROOMING_CANCELLATION_DEADLINE', 24),

        // Auto-confirmation of bookings
        'auto_confirm' => env('VETGROOMING_AUTO_CONFIRM', true),

        // Grooming statuses
        'statuses' => [
            'scheduled' => 'Запланирован',
            'in_progress' => 'В процессе',
            'completed' => 'Завершён',
            'cancelled' => 'Отменён',
        ],

        // Service types
        'service_types' => [
            'full_grooming' => 'Полный груминг',
            'hygiene' => 'Гигиенический груминг',
            'spa' => 'SPA процедуры',
            'trimming' => 'Тримминг',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Veterinary Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for veterinary appointments and medical records.
    |
    */
    'veterinary' => [
        // Emergency appointments
        'emergency_enabled' => env('VETGROOMING_EMERGENCY_ENABLED', true),

        // Emergency priority status
        'emergency_status' => 'urgent',

        // Medical record retention (in years)
        'medical_record_retention_years' => (int) env('VETGROOMING_MEDICAL_RECORD_RETENTION', 7),

        // Vaccination reminder days before expiry
        'vaccination_reminder_days' => (int) env('VETGROOMING_VACCINATION_REMINDER', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exotic Animals Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for exotic animal grooming and safety protocols.
    |
    */
    'exotic' => [
        // Enable exotic animal grooming
        'enabled' => env('VETGROOMING_EXOTIC_ENABLED', true),

        // Require safety protocol checklist
        'require_safety_checklist' => env('VETGROOMING_REQUIRE_SAFETY_CHECKLIST', true),

        // Temperature control enabled
        'temperature_control_enabled' => env('VETGROOMING_TEMPERATURE_CONTROL', true),

        // Maximum stress level threshold
        'max_stress_threshold' => (int) env('VETGROOMING_MAX_STRESS_THRESHOLD', 7),

        // Exotic types
        'exotic_types' => [
            'birds' => 'Птицы',
            'reptiles' => 'Рептилии',
            'small_mammals' => 'Мелкие млекопитающие',
            'large_mammals' => 'Крупные млекопитающие',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Medication Inventory Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for medication and product inventory management.
    |
    */
    'inventory' => [
        // Enable expiry tracking
        'expiry_tracking_enabled' => env('VETGROOMING_EXPIRY_TRACKING', true),

        // Expiry warning days
        'expiry_warning_days' => (int) env('VETGROOMING_EXPIRY_WARNING', 30),

        // Auto-disable expired items
        'auto_disable_expired' => env('VETGROOMING_AUTO_DISABLE_EXPIRED', true),

        // Low stock threshold percentage
        'low_stock_threshold_percent' => (int) env('VETGROOMING_LOW_STOCK_THRESHOLD', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sales Funnel Configuration
    |--------------------------------------------------------------------------
    |
    | Define the sales funnel stages and transitions for VetGrooming vertical.
    |
    */
    'funnel' => [
        'stages' => [
            'lead' => [
                'name' => 'Лид',
                'description' => 'Новый потенциальный клиент',
                'color' => 'gray',
            ],
            'booking' => [
                'name' => 'Запись',
                'description' => 'Записан на приём/груминг',
                'color' => 'blue',
            ],
            'consultation' => [
                'name' => 'Консультация',
                'description' => 'Первичный осмотр',
                'color' => 'cyan',
            ],
            'treatment' => [
                'name' => 'Лечение',
                'description' => 'Назначено лечение',
                'color' => 'yellow',
            ],
            'grooming' => [
                'name' => 'Груминг',
                'description' => 'Груминг-сессия',
                'color' => 'orange',
            ],
            'follow_up' => [
                'name' => 'Контроль',
                'description' => 'Контрольный визит',
                'color' => 'purple',
            ],
            'loyalty' => [
                'name' => 'Лояльность',
                'description' => 'Постоянный клиент',
                'color' => 'emerald',
            ],
        ],
        
        'transitions' => [
            'lead' => ['booking', 'consultation'],
            'booking' => ['consultation', 'grooming', 'cancelled'],
            'consultation' => ['treatment', 'grooming', 'follow_up'],
            'treatment' => ['follow_up', 'recovered'],
            'grooming' => ['follow_up', 'loyalty'],
            'follow_up' => ['loyalty', 'booking'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation Rules
    |--------------------------------------------------------------------------
    |
    | Define automatic actions triggered by events.
    |
    */
    'automation' => [
        // Reminder before appointment
        'appointment_reminder_hours' => (int) env('VETGROOMING_APPOINTMENT_REMINDER', 24),

        // Vaccination reminder
        'vaccination_reminder_enabled' => env('VETGROOMING_VACCINATION_REMINDER_ENABLED', true),

        // Loyalty points per visit
        'loyalty_points_per_visit' => (int) env('VETGROOMING_LOYALTY_POINTS', 10),

        // Loyalty points for referral
        'loyalty_points_referral' => (int) env('VETGROOMING_REFERRAL_POINTS', 100),

        // Auto-send follow-up after treatment
        'auto_send_follow_up' => env('VETGROOMING_AUTO_FOLLOW_UP', true),

        // Follow-up days after treatment
        'follow_up_days' => (int) env('VETGROOMING_FOLLOW_UP_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketplace Integration
    |--------------------------------------------------------------------------
    |
    | Integration settings with CatVRF marketplace.
    |
    */
    'marketplace' => [
        'enabled' => env('VETGROOMING_MARKETPLACE_ENABLED', true),
        
        // Auto-sync services to marketplace
        'sync_services' => true,
        
        // Handle marketplace bookings
        'handle_marketplace_bookings' => true,
        
        // Marketplace commission percentage
        'commission_percent' => (float) env('VETGROOMING_MARKETPLACE_COMMISSION', 5.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache configuration for VetGrooming CRM.
    |
    */
    'cache' => [
        // Cache duration for available slots (in minutes)
        'available_slots_ttl' => 5,
        
        // Cache duration for schedules (in minutes)
        'schedule_ttl' => 60,
        
        // Cache duration for medical records (in minutes)
        'medical_records_ttl' => 30,
        
        // Use Redis for real-time features
        'use_redis' => env('VETGROOMING_CACHE_USE_REDIS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Queue configuration for async operations.
    |
    */
    'queue' => [
        // Queue name for VetGrooming CRM jobs
        'queue_name' => env('VETGROOMING_QUEUE_NAME', 'vetgrooming'),
        
        // Connection name
        'connection' => env('VETGROOMING_QUEUE_CONNECTION', 'redis'),
    ],
];
