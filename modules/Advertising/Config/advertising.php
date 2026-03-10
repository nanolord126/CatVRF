<?php

use App\Services\Infrastructure\DopplerService;

return [
    'ord' => [
        'driver' => DopplerService::get('AD_ORD_DRIVER', 'yandex'),
        'api_key' => DopplerService::get('YANDEX_ORD_KEY'),
        'client_id' => DopplerService::get('AD_ORD_CLIENT_ID'),
        'storage_years' => 3,
    ],
    'defaults' => [
        'label' => 'Реклама',
        'vat' => 20.0,
    ],
];
