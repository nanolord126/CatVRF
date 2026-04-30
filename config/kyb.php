<?php

declare(strict_types=1);

return [
    'kontur_focus' => [
        'api_key' => env('KONTUR_FOCUS_API_KEY'),
        'api_url' => env('KONTUR_FOCUS_API_URL', 'https://api.kontur.ru'),
        'timeout' => 30,
    ],
    'spark_interfax' => [
        'api_key' => env('SPARK_API_KEY'),
        'api_url' => env('SPARK_API_URL', 'https://api.spark-interfax.ru'),
        'timeout' => 30,
    ],
    'rosfinmonitoring' => [
        'api_url' => env('ROSFINMONITORING_API_URL', 'https://rosfinmonitoring.ru/api'),
        'timeout' => 30,
    ],
    'ofac' => [
        'api_url' => env('OFAC_API_URL', 'https://api.ofac.gov'),
        'api_key' => env('OFAC_API_KEY'),
        'timeout' => 30,
    ],
    'eu_sanctions' => [
        'api_url' => env('EU_SANCTIONS_API_URL', 'https://webgate.ec.europa.eu'),
        'timeout' => 30,
    ],
    'ubo' => [
        'max_levels' => env('UBO_MAX_LEVELS', 5),
        'ubo_threshold' => env('UBO_THRESHOLD', 25.0),
    ],
    'risk' => [
        'critical_threshold' => 80,
        'high_threshold' => 60,
        'medium_threshold' => 40,
    ],
    'verification' => [
        'expiry_days' => 365,
        'rescreen_days' => 180,
    ],
];
