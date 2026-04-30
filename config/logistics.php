<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Logistics AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Logistics AI inference service integration
    |
    */

    'inference' => [
        'enabled' => env('LOGISTICS_INFERENCE_ENABLED', true),

        'redis' => [
            'queue_key' => env('LOGISTICS_INFERENCE_QUEUE_KEY', 'logistics:inference:queue'),
            'result_prefix' => env('LOGISTICS_INFERENCE_RESULT_PREFIX', 'logistics:inference:result:'),
            'result_ttl' => env('LOGISTICS_INFERENCE_RESULT_TTL', 300), // 5 minutes
            'timeout' => env('LOGISTICS_INFERENCE_TIMEOUT', 30), // 30 seconds
        ],

        'agent' => [
            'enabled' => env('LOGISTICS_AGENT_ENABLED', true),
            'default_mode' => env('LOGISTICS_AGENT_DEFAULT_MODE', 'supervised'), // supervised or autonomous
            'llm_provider' => env('LOGISTICS_AGENT_LLM_PROVIDER', 'openai'),
            'llm_model' => env('LOGISTICS_AGENT_LLM_MODEL', 'gpt-4-turbo'),
            'max_auto_actions_per_hour' => env('LOGISTICS_AGENT_MAX_ACTIONS', 10),
        ],

        'abtest' => [
            'enabled' => env('LOGISTICS_ABTEST_ENABLED', true),
            'traffic_percentage' => env('LOGISTICS_ABTEST_TRAFFIC_PERCENTAGE', 10),
        ],
    ],

    'models' => [
        'courier_assignment' => [
            'enabled' => env('COURIER_ASSIGNMENT_ENABLED', true),
            'version' => env('COURIER_ASSIGNMENT_VERSION', 'v1'),
        ],
        'pvz_scoring' => [
            'enabled' => env('PVZ_SCORING_ENABLED', true),
            'version' => env('PVZ_SCORING_VERSION', 'v1'),
        ],
        'eta_prediction' => [
            'enabled' => env('ETA_PREDICTION_ENABLED', true),
            'version' => env('ETA_PREDICTION_VERSION', 'v1'),
        ],
        'demand_forecast' => [
            'enabled' => env('DEMAND_FORECAST_ENABLED', false),
            'version' => env('DEMAND_FORECAST_VERSION', 'v1'),
        ],
    ],

    'feature_store' => [
        'clickhouse' => [
            'host' => env('CLICKHOUSE_HOST', 'localhost'),
            'port' => env('CLICKHOUSE_PORT', 8123),
            'database' => env('CLICKHOUSE_DATABASE', 'default'),
            'user' => env('CLICKHOUSE_USER', 'default'),
            'password' => env('CLICKHOUSE_PASSWORD', ''),
        ],
    ],
];
