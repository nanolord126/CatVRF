<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Beauty CRM Module Status
    |--------------------------------------------------------------------------
    |
    | Enable or disable the Beauty CRM module for the Beauty Masters vertical.
    |
    */
    'enabled' => env('BEAUTY_CRM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for pricing and payments.
    |
    */
    'default_currency' => env('BEAUTY_CRM_CURRENCY', 'RUB'),

    /*
    |--------------------------------------------------------------------------
    | Bonus System (Loyalty)
    |--------------------------------------------------------------------------
    |
    | Bonus points configuration for loyalty program.
    |
    */
    'bonus' => [
        'enabled' => env('BEAUTY_BONUS_ENABLED', true),
        
        // Points earned per ruble spent (e.g., 0.01 = 1 point per 100 rubles)
        'points_per_ruble' => (float) env('BEAUTY_BONUS_POINTS_PER_RUBLE', 0.01),
        
        // Tier thresholds (in rubles)
        'tiers' => [
            'bronze' => 0,
            'silver' => 10000,
            'gold' => 50000,
            'platinum' => 150000,
        ],
        
        // Tier multipliers for bonus earning
        'tier_multipliers' => [
            'bronze' => 1.0,
            'silver' => 1.2,
            'gold' => 1.5,
            'platinum' => 2.0,
        ],
        
        // Points to ruble conversion rate for redemption
        'points_to_ruble_rate' => (float) env('BEAUTY_BONUS_POINTS_TO_RUBLE', 1.0),
        
        // Maximum points that can be redeemed per transaction
        'max_redeem_per_transaction' => (int) env('BEAUTY_BONUS_MAX_REDEEM', 1000),
        
        // Birthday bonus points
        'birthday_bonus_points' => (int) env('BEAUTY_BONUS_BIRTHDAY', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Appointment Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for appointment booking and management.
    |
    */
    'appointments' => [
        // Minimum advance booking time (in hours)
        'min_advance_booking_hours' => (int) env('BEAUTY_MIN_ADVANCE_BOOKING', 1),
        
        // Maximum advance booking time (in days)
        'max_advance_booking_days' => (int) env('BEAUTY_MAX_ADVANCE_BOOKING', 30),
        
        // Default appointment duration (in minutes)
        'default_duration_minutes' => (int) env('BEAUTY_DEFAULT_DURATION', 30),
        
        // Buffer time between appointments (in minutes)
        'buffer_minutes' => (int) env('BEAUTY_BUFFER_MINUTES', 5),
        
        // Auto-confirm appointments on online booking
        'auto_confirm' => (bool) env('BEAUTY_AUTO_CONFIRM', false),
        
        // Require payment for confirmation
        'require_payment_for_confirmation' => (bool) env('BEAUTY_REQUIRE_PAYMENT', false),
        
        // Cancellation policy (in hours before appointment)
        'cancellation_deadline_hours' => (int) env('BEAUTY_CANCELLATION_DEADLINE', 24),
        
        // No-show penalty (percentage of service price)
        'no_show_penalty_percent' => (int) env('BEAUTY_NO_SHOW_PENALTY', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminders
    |--------------------------------------------------------------------------
    |
    | Automatic reminder configuration for appointments.
    |
    */
    'reminders' => [
        'enabled' => env('BEAUTY_REMINDERS_ENABLED', true),
        
        // Reminder schedules (in hours before appointment)
        'schedule' => [
            '24h' => 24,
            '2h' => 2,
        ],
        
        // Notification channels (priority order)
        'channels' => [
            'telegram',
            'whatsapp',
            'sms',
            'email',
        ],
        
        // Timezone for reminder scheduling
        'timezone' => env('BEAUTY_REMINDER_TIMEZONE', 'Europe/Moscow'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sales Funnel Configuration
    |--------------------------------------------------------------------------
    |
    | Define the sales funnel stages and transitions for Beauty vertical.
    |
    */
    'funnel' => [
        'stages' => [
            'lead' => [
                'name' => 'Лид',
                'description' => 'Новый потенциальный клиент',
                'color' => 'gray',
            ],
            'new_client' => [
                'name' => 'Новый клиент',
                'description' => 'Первая запись через маркетплейс или звонок',
                'color' => 'blue',
            ],
            'appointment_created' => [
                'name' => 'Запись создана',
                'description' => 'Запись к мастеру',
                'color' => 'yellow',
            ],
            'confirmed' => [
                'name' => 'Подтверждена',
                'description' => 'Запись подтверждена',
                'color' => 'blue',
            ],
            'in_progress' => [
                'name' => 'В процессе',
                'description' => 'Услуга выполняется',
                'color' => 'orange',
            ],
            'completed' => [
                'name' => 'Завершена',
                'description' => 'Услуга выполнена',
                'color' => 'green',
            ],
            'paid' => [
                'name' => 'Оплачена',
                'description' => 'Оплата получена',
                'color' => 'green',
            ],
            'post_service' => [
                'name' => 'После сервиса',
                'description' => 'Отзыв + фото до/после',
                'color' => 'purple',
            ],
            'retained' => [
                'name' => 'Удержан',
                'description' => 'Повторная запись',
                'color' => 'green',
            ],
        ],
        
        'transitions' => [
            'lead' => ['new_client'],
            'new_client' => ['appointment_created'],
            'appointment_created' => ['confirmed', 'cancelled'],
            'confirmed' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed'],
            'completed' => ['paid', 'post_service'],
            'paid' => ['post_service', 'retained'],
            'post_service' => ['retained'],
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
        // Bonus points earning
        'bonus_on_completion' => true,
        
        // Tier upgrade check
        'auto_tier_upgrade' => true,
        
        // First visit detection
        'detect_first_visit' => true,
        
        // Reminder scheduling
        'schedule_reminders' => true,
        
        // User stats calculation
        'calculate_stats' => true,
        
        // Master rating sync
        'sync_master_rating' => true,
        
        // Supply deduction on service completion
        'deduct_supplies' => true,
        
        // Suggest next appointment
        'suggest_next_appointment' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Calendar Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for calendar views and scheduling.
    |
    */
    'calendar' => [
        // Default view (day, week, month)
        'default_view' => 'week',
        
        // Time slot interval (in minutes)
        'slot_duration_minutes' => 15,
        
        // Business hours start
        'business_start_hour' => (int) env('BEAUTY_BUSINESS_START', 9),
        
        // Business hours end
        'business_end_hour' => (int) env('BEAUTY_BUSINESS_END', 21),
        
        // Enable drag and drop
        'enable_drag_drop' => true,
        
        // Enable real-time updates via WebSocket
        'enable_realtime' => true,
        
        // Color scheme for appointment statuses
        'status_colors' => [
            'pending' => '#F59E0B', // yellow
            'confirmed' => '#3B82F6', // blue
            'in_progress' => '#F97316', // orange
            'completed' => '#10B981', // green
            'cancelled' => '#EF4444', // red
            'no_show' => '#EF4444', // red
            'paid' => '#10B981', // green
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Photo Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for before/after photos.
    |
    */
    'photos' => [
        'enabled' => env('BEAUTY_PHOTOS_ENABLED', true),
        
        // Storage disk
        'storage_disk' => env('BEAUTY_PHOTOS_DISK', 'public'),
        
        // Maximum file size (in MB)
        'max_file_size_mb' => (int) env('BEAUTY_PHOTOS_MAX_SIZE', 10),
        
        // Allowed mime types
        'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
        
        // Auto-generate thumbnails
        'generate_thumbnails' => true,
        
        // Thumbnail dimensions
        'thumbnail_width' => 300,
        'thumbnail_height' => 300,
        
        // Default visibility
        'default_public' => false,
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
        'enabled' => env('BEAUTY_MARKETPLACE_ENABLED', true),
        
        // Auto-sync venues to marketplace
        'sync_venues' => true,
        
        // Auto-sync services to marketplace
        'sync_services' => true,
        
        // Auto-sync masters to marketplace
        'sync_masters' => true,
        
        // Handle bookings from marketplace
        'handle_marketplace_bookings' => true,
        
        // Marketplace commission percentage
        'commission_percent' => (float) env('BEAUTY_MARKETPLACE_COMMISSION', 5.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fraud Detection
    |--------------------------------------------------------------------------
    |
    | Fraud detection settings for Beauty vertical.
    |
    */
    'fraud' => [
        'enabled' => env('BEAUTY_FRAUD_ENABLED', true),
        
        // Maximum appointments per user per day
        'max_appointments_per_day' => (int) env('BEAUTY_FRAUD_MAX_PER_DAY', 5),
        
        // Maximum cancellations per user per month
        'max_cancellations_per_month' => (int) env('BEAUTY_FRAUD_MAX_CANCELLATIONS', 3),
        
        // Block suspicious users automatically
        'auto_block' => false,
        
        // Notify admin on suspicious activity
        'notify_admin' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache configuration for Beauty CRM.
    |
    */
    'cache' => [
        // Cache duration for available slots (in minutes)
        'available_slots_ttl' => 5,
        
        // Cache duration for schedules (in minutes)
        'schedule_ttl' => 120,
        
        // Cache duration for bonus profiles (in minutes)
        'bonus_profile_ttl' => 60,
        
        // Use Redis for real-time features
        'use_redis' => env('BEAUTY_CACHE_USE_REDIS', true),
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
        // Queue name for Beauty CRM jobs
        'queue_name' => env('BEAUTY_QUEUE_NAME', 'beauty'),
        
        // Connection name
        'connection' => env('BEAUTY_QUEUE_CONNECTION', 'redis'),
    ],
];
