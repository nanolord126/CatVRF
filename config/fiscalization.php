<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Fiscalization Configuration (54-ФЗ)
    |--------------------------------------------------------------------------
    |
    | Configuration for KKT (cash register) fiscalization.
    | Supports multiple OFD providers: OrangeData, CloudKassir, Atol, Yandex.Kassa.
    |
    */

    'default_provider' => env('FISCALIZATION_DEFAULT_PROVIDER', 'orangedata'),

    /*
    |--------------------------------------------------------------------------
    | OFD Providers Configuration
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'orangedata' => [
            'enabled' => env('ORANGEDATA_ENABLED', true),
            'api_url' => env('ORANGEDATA_API_URL', 'https://api.orangedata.ru/api/v2/documents'),
            'api_key' => env('ORANGEDATA_API_KEY'),
            'group' => env('ORANGEDATA_GROUP', 'Main'),
            'key' => env('ORANGEDATA_KEY'),
            'timeout' => env('ORANGEDATA_TIMEOUT', 30),
            'retry_attempts' => env('ORANGEDATA_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('ORANGEDATA_RETRY_DELAY', 1000), // milliseconds
        ],

        'cloudkassir' => [
            'enabled' => env('CLOUDKASSIR_ENABLED', false),
            'api_url' => env('CLOUDKASSIR_API_URL', 'https://api.cloudkassir.ru/api/v2'),
            'api_key' => env('CLOUDKASSIR_API_KEY'),
            'kkt_id' => env('CLOUDKASSIR_KKT_ID'),
            'timeout' => env('CLOUDKASSIR_TIMEOUT', 30),
            'retry_attempts' => env('CLOUDKASSIR_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('CLOUDKASSIR_RETRY_DELAY', 1000),
        ],

        'atol' => [
            'enabled' => env('ATOL_ENABLED', false),
            'api_url' => env('ATOL_API_URL', 'https://online.atol.ru/possystem/v4'),
            'api_key' => env('ATOL_API_KEY'),
            'group_code' => env('ATOL_GROUP_CODE'),
            'inn' => env('ATOL_INN'),
            'payment_address' => env('ATOL_PAYMENT_ADDRESS', 'https://catvrf.ru'),
            'timeout' => env('ATOL_TIMEOUT', 30),
            'retry_attempts' => env('ATOL_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('ATOL_RETRY_DELAY', 1000),
        ],

        'yandex' => [
            'enabled' => env('YANDEX_FISCAL_ENABLED', false),
            'api_url' => env('YANDEX_API_URL', 'https://payment.yandex.net/api/v3'),
            'shop_id' => env('YANDEX_SHOP_ID'),
            'secret_key' => env('YANDEX_SECRET_KEY'),
            'timeout' => env('YANDEX_TIMEOUT', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent Information (54-ФЗ requirement)
    |--------------------------------------------------------------------------
    */
    'agent' => [
        'type' => env('FISCAL_AGENT_TYPE', 'payment_agent'),
        'name' => env('FISCAL_AGENT_NAME', 'CatVRF Marketplace'),
        'inn' => env('FISCAL_AGENT_INN'),
        'payment_address' => env('FISCAL_PAYMENT_ADDRESS', 'https://catvrf.ru'),
        'phone' => env('FISCAL_AGENT_PHONE'),
        'operation_type' => env('FISCAL_OPERATION_TYPE', 'Маркетплейс'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Taxation System (Система налогообложения)
    |--------------------------------------------------------------------------
    |
    | 1 - Общая (ОСНО)
    | 2 - Упрощенная доход (УСН доход)
    | 3 - Упрощенная доход минус расход (УСН доход - расход)
    | 4 - Единый налог на вмененный доход (ЕНВД)
    | 5 - Единый сельскохозяйственный налог (ЕСХН)
    | 6 - Патентная система налогообложения (ПСН)
    |
    */
    'taxation_system' => env('FISCAL_TAXATION_SYSTEM', 1),

    /*
    |--------------------------------------------------------------------------
    | VAT Rates (НДС)
    |--------------------------------------------------------------------------
    */
    'vat' => [
        'default' => env('FISCAL_DEFAULT_VAT', 20),
        'rates' => [
            'none' => 0,
            '0' => 1,
            '10' => 2,
            '18' => 3,
            '20' => 4,
            '110' => 5,
            '118' => 6,
            '120' => 7,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods (Способы оплаты)
    |--------------------------------------------------------------------------
    |
    | 1 - Наличными
    | 2 - Электронными
    | 3 - Предварительная оплата (аванс)
    | 4 - Постоплата (кредит)
    | 5 - Иная форма оплаты
    |
    */
    'payment_methods' => [
        'cash' => 1,
        'electronic' => 2,
        'prepayment' => 3,
        'credit' => 4,
        'other' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Subjects (Предметы расчета)
    |--------------------------------------------------------------------------
    |
    | 1 - Товар
    | 2 - Подакцизный товар
    | 3 - Работа
    | 4 - Услуга
    | 5 - Ставка азартной игры
    | 6 - Выигрыш азартной игры
    | 7 - Лотерейный билет
    | 8 - Выигрыш лотереи
    | 9 - Предоставление РИД
    | 10 - Платеж
    | 11 - Агентское вознаграждение
    | 12 - Составной предмет расчета
    | 13 - Иной предмет расчета
    |
    */
    'payment_subjects' => [
        'commodity' => 1,
        'excisable_commodity' => 2,
        'work' => 3,
        'service' => 4,
        'gambling_bet' => 5,
        'gambling_prize' => 6,
        'lottery' => 7,
        'lottery_prize' => 8,
        'intellectual_activity' => 9,
        'payment' => 10,
        'agent_commission' => 11,
        'composite' => 12,
        'other' => 13,
    ],

    /*
    |--------------------------------------------------------------------------
    | Receipt Settings
    |--------------------------------------------------------------------------
    */
    'receipt' => [
        'auto_send' => env('FISCAL_AUTO_SEND', true),
        'async_processing' => env('FISCAL_ASYNC_PROCESSING', true),
        'queue_connection' => env('FISCAL_QUEUE_CONNECTION', 'redis'),
        'queue_name' => env('FISCAL_QUEUE_NAME', 'fiscal'),
        'retry_failed' => env('FISCAL_RETRY_FAILED', true),
        'max_retries' => env('FISCAL_MAX_RETRIES', 3),
        'retry_delay_minutes' => env('FISCAL_RETRY_DELAY', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Item Mapping Settings
    |--------------------------------------------------------------------------
    */
    'items' => [
        'default_vat' => env('FISCAL_ITEM_DEFAULT_VAT', 20),
        'default_payment_method' => env('FISCAL_ITEM_DEFAULT_PAYMENT_METHOD', 'electronic'),
        'default_payment_subject' => env('FISCAL_ITEM_DEFAULT_SUBJECT', 'commodity'),
        'max_name_length' => env('FISCAL_ITEM_MAX_NAME_LENGTH', 128),
        'max_quantity' => env('FISCAL_ITEM_MAX_QUANTITY', 99999.999),
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Data (54-ФЗ optional)
    |--------------------------------------------------------------------------
    */
    'customer' => [
        'collect_email' => env('FISCAL_COLLECT_EMAIL', false),
        'collect_phone' => env('FISCAL_COLLECT_PHONE', false),
        'send_receipt_to_customer' => env('FISCAL_SEND_TO_CUSTOMER', false),
        'email_template' => env('FISCAL_EMAIL_TEMPLATE', 'fiscal_receipt'),
        'sms_template' => env('FISCAL_SMS_TEMPLATE', 'fiscal_receipt'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Correction Receipts (Корректирующий чек)
    |--------------------------------------------------------------------------
    */
    'correction' => [
        'enabled' => env('FISCAL_CORRECTION_ENABLED', true),
        'require_approval' => env('FISCAL_CORRECTION_APPROVAL', true),
        'reason_codes' => [
            'self_correction' => 1, // Самостоятельно
            'by_instruction' => 2, // По предписанию
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Attributes (Дополнительный реквизит)
    |--------------------------------------------------------------------------
    */
    'additional_attributes' => [
        'enabled' => env('FISCAL_ADDITIONAL_ATTR_ENABLED', true),
        'attribute_name' => env('FISCAL_ATTR_NAME', 'order_id'),
        'attribute_value_template' => env('FISCAL_ATTR_TEMPLATE', '{order_id}'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Alerts
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('FISCAL_MONITORING_ENABLED', true),
        'alert_on_failure' => env('FISCAL_ALERT_FAILURE', true),
        'alert_on_retry' => env('FISCAL_ALERT_RETRY', true),
        'alert_threshold_percent' => env('FISCAL_ALERT_THRESHOLD', 5), // Alert if > 5% failures
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'receipts_years' => env('FISCAL_RETENTION_YEARS', 5),
        'logs_years' => env('FISCAL_LOG_RETENTION_YEARS', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Mode
    |--------------------------------------------------------------------------
    */
    'testing' => [
        'enabled' => env('FISCAL_TESTING_MODE', false),
        'mock_ofd_responses' => env('FISCAL_MOCK_OFD', false),
        'mock_fiscal_sign' => env('FISCAL_MOCK_FISCAL_SIGN', 'TEST_SIGN'),
        'mock_fn_number' => env('FISCAL_MOCK_FN_NUMBER', '999999999999999'),
    ],
];
