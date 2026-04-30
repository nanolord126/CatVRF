<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | KYB Enhanced Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for enhanced KYB features including PEP screening,
    | adverse media monitoring, and AI link analysis.
    |
    */

    'pep_screening' => [
        'world_check' => [
            'api_key' => env('WORLDCHECK_API_KEY'),
            'api_url' => env('WORLDCHECK_API_URL', 'https://api-world-check.refinitiv.com'),
            'timeout' => 30,
        ],
        'kontur_focus' => [
            'api_key' => env('KONTUR_FOCUS_API_KEY'),
            'api_url' => env('KONTUR_FOCUS_API_URL', 'https://focus-api.kontur.ru'),
            'timeout' => 30,
        ],
        'rescreen_interval_days' => 30,
        'cooling_off_period_months' => 18,
        'enhanced_due_diligence_threshold' => 'high',
    ],

    'adverse_media' => [
        'google_news' => [
            'api_key' => env('GOOGLE_NEWS_API_KEY'),
            'api_url' => env('GOOGLE_NEWS_API_URL', 'https://newsapi.org/v2'),
            'timeout' => 30,
        ],
        'yandex_news' => [
            'api_key' => env('YANDEX_NEWS_API_KEY'),
            'api_url' => env('YANDEX_NEWS_API_URL', 'https://news-api.yandex.ru'),
            'timeout' => 30,
        ],
        'monitoring_interval_hours' => 6,
        'alert_threshold' => 0.7, // Sentiment confidence threshold
        'max_articles_per_screen' => 100,
    ],

    'link_analysis' => [
        'max_depth' => 5,
        'ubo_threshold' => 25, // 25% ownership
        'critical_risk_threshold' => 70,
        'high_risk_threshold' => 50,
        'medium_risk_threshold' => 30,
        'enable_neo4j' => env('ENABLE_NEO4J_LINK_ANALYSIS', false),
        'neo4j' => [
            'host' => env('NEO4J_HOST', 'localhost'),
            'port' => env('NEO4J_PORT', 7687),
            'username' => env('NEO4J_USERNAME', 'neo4j'),
            'password' => env('NEO4J_PASSWORD'),
            'database' => env('NEO4J_DATABASE', 'neo4j'),
        ],
    ],

    'risk_scoring' => [
        'pep_weight' => 30,
        'adverse_media_weight' => 25,
        'link_analysis_weight' => 25,
        'sanctions_weight' => 20,
        'manual_review_threshold' => 70,
    ],
];
