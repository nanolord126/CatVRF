<?php

use App\Services\Infrastructure\DopplerService;

return [
    'gateways' => [
        'tinkoff' => [
            'terminal_id' => DopplerService::get('TINKOFF_TERMINAL_ID'),
            'secret_key' => DopplerService::get('TINKOFF_SECRET_KEY'),
            'api_url' => DopplerService::get('TINKOFF_API_URL', 'https://securepay.tinkoff.ru/v2/'),
        ],
        'sber' => [
            'username' => DopplerService::get('SBER_USERNAME'),
            'password' => DopplerService::get('SBER_PASSWORD'),
            'api_url' => DopplerService::get('SBER_API_URL'),
        ],
        'tochka' => [
            'client_id' => DopplerService::get('TOCHKA_CLIENT_ID'),
            'client_secret' => DopplerService::get('TOCHKA_CLIENT_SECRET'),
        ],
    ],
    'ofd' => [
        'default' => DopplerService::get('OFD_DRIVER', 'tensor'), // tensor or atol
        'tensor' => [
            'app_id' => DopplerService::get('TENSOR_APP_ID'),
            'app_secret' => DopplerService::get('TENSOR_APP_SECRET'),
        ],
        'atol' => [
            'login' => DopplerService::get('ATOL_LOGIN'),
            'password' => DopplerService::get('ATOL_PASSWORD'),
        ],
    ],
    'commissions' => [
        'platform_percent' => 12.0,
        'client_cashback_percent' => 1.0, // 1% from the 12% goes back to client
    ],
    'onboarding' => [
        'trial_days' => 7,
        'total_fee' => 15000.0,
        'license_fee' => 7500.0,
        'deposit_amount' => 7500.0,
        'is_deposit_refundable' => false, // Non-refundable deposit policy
    ],
];
