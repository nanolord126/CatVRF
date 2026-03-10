<?php

/**
 * Конфигурация платёжных систем.
 *
 * Поддерживаемые провайдеры (в порядке приоритета):
 * 1. Tinkoff (основной) - поддержка SBP, карт, QR-кодов
 * 2. Tochka Bank - корпоративные платежи и выплаты
 * 3. Sber - мобильная коммерция и платежи
 * 4. SBP - система быстрых платежей (используется как альтернатива)
 */
return [
    'default' => App\Services\Infrastructure\DopplerService::get('PAYMENT_GATEWAY', 'tinkoff'),
    'webhook_secret' => App\Services\Infrastructure\DopplerService::get('PAYMENT_WEBHOOK_SECRET', env('PAYMENT_WEBHOOK_SECRET')),
    
    // Платёжные провайдеры (в порядке приоритета)
    'drivers' => [
        // Основной провайдер
        'tinkoff' => [
            'terminal_id' => App\Services\Infrastructure\DopplerService::get('TINKOFF_TERMINAL_ID'),
            'secret_key' => App\Services\Infrastructure\DopplerService::get('TINKOFF_SECRET_KEY'),
            'sbp' => true,  // Поддержка системы быстрых платежей
            'qr_codes' => true,  // Динамические QR-коды
            'tokenization' => true,  // Сохранение карт
        ],
        // Корпоративные платежи
        'tochka' => [
            'api_key' => App\Services\Infrastructure\DopplerService::get('TOCHKA_API_KEY'),
            'client_id' => App\Services\Infrastructure\DopplerService::get('TOCHKA_CLIENT_ID'),
            'sbp' => true,
            'corporate_payouts' => true,  // Выплаты на счета юрлиц
        ],
        // Мобильная коммерция
        'sber' => [
            'username' => App\Services\Infrastructure\DopplerService::get('SBER_USER'),
            'password' => App\Services\Infrastructure\DopplerService::get('SBER_PASS'),
            'sbp' => true,
            'mobile_commerce' => true,
        ],
    ],
    
    // Фискальная система (ФЗ-54)
    'fiscal' => [
        'provider' => App\Services\Infrastructure\DopplerService::get('FISCAL_PROVIDER', 'cloudkassir'),
        'api_key' => App\Services\Infrastructure\DopplerService::get('FISCAL_API_KEY'),
        'organization_name' => App\Services\Infrastructure\DopplerService::get('ORGANIZATION_NAME'),
        'inn' => App\Services\Infrastructure\DopplerService::get('INN'),
    ],
    
    // Налогообложение
    'ofd' => [
        'tax_system' => 'osn', // osn, usn_income, usn_income_minus_expense, envd, esn
        'tax_code' => 1, // 0%, 10%, 20%
    ],
];


