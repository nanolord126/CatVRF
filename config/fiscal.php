<?php

return [
    'default' => App\Services\Infrastructure\DopplerService::get('FISCAL_DRIVER', 'cloudkassir'),
    'fallback' => 'atol',
    'common' => [
        'inn' => App\Services\Infrastructure\DopplerService::get('FISCAL_INN'),
        'taxation_system' => App\Services\Infrastructure\DopplerService::get('FISCAL_TAXATION_SYSTEM', 'usn_income'),
    ],
    'drivers' => [
        'cloudkassir' => [
            'id' => App\Services\Infrastructure\DopplerService::get('CLOUDKASSIR_ID'),
            'key' => App\Services\Infrastructure\DopplerService::get('CLOUDKASSIR_KEY'),
            'endpoint' => 'https://api.cloudpayments.ru/kassa/receipt',
        ],
        'atol' => [
            'login' => App\Services\Infrastructure\DopplerService::get('ATOL_LOGIN'),
            'password' => App\Services\Infrastructure\DopplerService::get('ATOL_PASS'),
            'group_code' => App\Services\Infrastructure\DopplerService::get('ATOL_GROUP_CODE'),
            'endpoint' => 'https://online.atol.ru/possystem/v4/',
        ],
    ],
];
