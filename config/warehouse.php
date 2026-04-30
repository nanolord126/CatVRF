<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | PII Protection Configuration (152-ФЗ)
    |--------------------------------------------------------------------------
    */
    'pii' => [
        // Salt для hash-функций PII
        'salt' => env('WAREHOUSE_PII_SALT', 'change_this_in_production'),

        // Роли с доступом к PII
        'allowed_roles' => [
            'admin',
            'manager',
            'compliance',
            'auditor'
        ],

        // Автоматическая анонимизация в логах
        'auto_anonymize_logs' => env('WAREHOUSE_PII_AUTO_ANONYMIZE', true),

        // Шифрование чувствительных полей в БД
        'encrypt_sensitive_fields' => env('WAREHOUSE_PII_ENCRYPT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention Configuration
    |--------------------------------------------------------------------------
    */
    'retention' => [
        // Срок хранения логов (152-ФЗ)
        'logs' => env('WAREHOUSE_RETENTION_LOGS', '1 year'),

        // Срок хранения инвентаризаций
        'inventory_counts' => env('WAREHOUSE_RETENTION_INVENTORY_COUNTS', '5 years'),

        // Срок хранения движений товаров
        'stock_movements' => env('WAREHOUSE_RETENTION_STOCK_MOVEMENTS', '7 years'),

        // Срок хранения партий после истечения срока годности
        'batches_after_expiry' => env('WAREHOUSE_RETENTION_BATCHES_AFTER_EXPIRY', '1 year'),

        // Автоматическая очистка (cron)
        'auto_cleanup' => env('WAREHOUSE_RETENTION_AUTO_CLEANUP', true),
        'cleanup_schedule' => env('WAREHOUSE_RETENTION_CLEANUP_SCHEDULE', '0 2 * * *'), // 2 AM daily
    ],

    /*
    |--------------------------------------------------------------------------
    | License Management Configuration (ФЗ-323, ФЗ-61)
    |--------------------------------------------------------------------------
    */
    'licenses' => [
        // Проверка лицензий для лекарственных средств
        'check_pharmacy_license' => env('WAREHOUSE_CHECK_PHARMACY_LICENSE', true),

        // Проверка лицензий для контролируемых веществ
        'check_controlled_substance_license' => env('WAREHOUSE_CHECK_CONTROLLED_LICENSE', true),

        // Блокировка партии за X дней до истечения срока годности
        'expiry_warning_days' => env('WAREHOUSE_EXPIRY_WARNING_DAYS', 30),

        // Автоматическая проверка температурного режима
        'check_temperature' => env('WAREHOUSE_CHECK_TEMPERATURE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        // Честный ЗНАК (маркировка лекарств)
        'chestny_znak' => [
            'enabled' => env('WAREHOUSE_CHESTNY_ZNAK_ENABLED', false),
            'api_url' => env('WAREHOUSE_CHESTNY_ZNAK_API_URL'),
            'api_key' => env('WAREHOUSE_CHESTNY_ZNAK_API_KEY'),
            'certificate_path' => env('WAREHOUSE_CHESTNY_ZNAK_CERT_PATH'),
        ],

        // ЕГИСЗ (Единая государственная информационная система)
        'egisz' => [
            'enabled' => env('WAREHOUSE_EGISZ_ENABLED', false),
            'api_url' => env('WAREHOUSE_EGISZ_API_URL'),
            'api_key' => env('WAREHOUSE_EGISZ_API_KEY'),
        ],

        // 1С интеграция
        'onec' => [
            'enabled' => env('WAREHOUSE_ONEC_ENABLED', false),
            'api_url' => env('WAREHOUSE_ONEC_API_URL'),
            'username' => env('WAREHOUSE_ONEC_USERNAME'),
            'password' => env('WAREHOUSE_ONEC_PASSWORD'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory Configuration
    |--------------------------------------------------------------------------
    */
    'inventory' => [
        // Минимальный процент расхождений для блокировки инвентаризации
        'discrepancy_threshold' => env('WAREHOUSE_DISCREPANCY_THRESHOLD', 5),

        // Автоматическое создание инвентаризации при расхождении
        'auto_create_count' => env('WAREHOUSE_AUTO_CREATE_COUNT', false),

        // Циклическая инвентаризация (cycle counting)
        'cycle_counting' => [
            'enabled' => env('WAREHOUSE_CYCLE_COUNTING_ENABLED', false),
            'frequency_days' => env('WAREHOUSE_CYCLE_COUNTING_FREQUENCY', 30),
            'abc_categories' => ['A' => 7, 'B' => 14, 'C' => 30], // Days between counts
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cold Chain Configuration (ФЗ-323)
    |--------------------------------------------------------------------------
    */
    'cold_chain' => [
        // Температурные диапазоны для разных типов лекарств
        'temperature_ranges' => [
            'standard' => ['min' => 2.0, 'max' => 25.0],
            'refrigerated' => ['min' => 2.0, 'max' => 8.0],
            'frozen' => ['min' => -25.0, 'max' => -10.0],
        ],

        // Влажность (%)
        'humidity_range' => ['min' => 35, 'max' => 75],

        // Интервал мониторинга (минуты)
        'monitoring_interval' => env('WAREHOUSE_COLD_CHAIN_INTERVAL', 15),
    ],
];
