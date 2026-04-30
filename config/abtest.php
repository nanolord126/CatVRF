<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | A/B Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for A/B testing of Logistics AI inference logic
    |
    */

    'enabled' => env('LOGISTICS_ABTEST_ENABLED', true),

    'traffic_percentage' => env('LOGISTICS_ABTEST_TRAFFIC_PERCENTAGE', 10), // Start with 10%

    'start_date' => env('LOGISTICS_ABTEST_START_DATE'), // Optional: YYYY-MM-DD

    'end_date' => env('LOGISTICS_ABTEST_END_DATE'), // Optional: YYYY-MM-DD

    'experiments' => [
        'courier_assignment' => [
            'enabled' => true,
            'traffic_percentage' => 10,
            'description' => 'GNN + RL vs rule-based assignment',
        ],
        'eta_prediction' => [
            'enabled' => true,
            'traffic_percentage' => 10,
            'description' => 'Hybrid model vs LightGBM',
        ],
        'pvz_scoring' => [
            'enabled' => true,
            'traffic_percentage' => 10,
            'description' => 'Temporal features vs baseline',
        ],
    ],

    'metrics_retention_hours' => 168, // 7 days

    'statistical_significance' => [
        'min_sample_size' => 1000,
        'min_improvement_percent' => 5.0,
        'confidence_level' => 0.95,
    ],
];
