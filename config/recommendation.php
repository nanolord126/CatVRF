<?php

declare(strict_types=1);

return [
    'ml_service_url' => env('RECOMMENDATION_ML_SERVICE_URL', 'http://localhost:8000'),
    'ml_service_api_key' => env('RECOMMENDATION_ML_SERVICE_API_KEY', ''),
    'ml_service_timeout' => (int) env('RECOMMENDATION_ML_SERVICE_TIMEOUT', 5),
    'ml_service_retry_attempts' => (int) env('RECOMMENDATION_ML_SERVICE_RETRY_ATTEMPTS', 2),

    'cache' => [
        'ttl' => (int) env('RECOMMENDATION_CACHE_TTL', 300),
        'prefix' => env('RECOMMENDATION_CACHE_PREFIX', 'rec:'),
        'warmup_enabled' => env('RECOMMENDATION_CACHE_WARMUP_ENABLED', false),
    ],

    'fairness' => [
        'min_seller_exposure' => (float) env('RECOMMENDATION_MIN_SELLER_EXPOSURE', 0.02),
        'max_dominance_share' => (float) env('RECOMMENDATION_MAX_DOMINANCE_SHARE', 0.30),
        'diversity_min_gap' => (float) env('RECOMMENDATION_DIVERSITY_MIN_GAP', 0.05),
        'exploration_budget' => (float) env('RECOMMENDATION_EXPLORATION_BUDGET', 0.10),
        'min_sellers_in_feed' => (int) env('RECOMMENDATION_MIN_SELLERS_IN_FEED', 5),
    ],

    'scenarios' => [
        'home_feed' => [
            'default_limit' => 20,
            'cache_ttl' => 300,
        ],
        'product_detail' => [
            'default_limit' => 12,
            'cache_ttl' => 300,
        ],
        'cart' => [
            'default_limit' => 6,
            'cache_ttl' => 60,
        ],
        'search' => [
            'default_limit' => 30,
            'cache_ttl' => 120,
        ],
        'seller_page' => [
            'default_limit' => 16,
            'cache_ttl' => 600,
        ],
        'email_digest' => [
            'default_limit' => 8,
            'cache_ttl' => 3600,
        ],
        'checkout_upsell' => [
            'default_limit' => 4,
            'cache_ttl' => 60,
        ],
        'category_browse' => [
            'default_limit' => 24,
            'cache_ttl' => 180,
        ],
        'reorder' => [
            'default_limit' => 10,
            'cache_ttl' => 900,
        ],
    ],

    'sources' => [
        'two_tower' => [
            'enabled' => env('RECOMMENDATION_TWO_TOWER_ENABLED', true),
            'weight' => (float) env('RECOMMENDATION_TWO_TOWER_WEIGHT', 1.0),
        ],
        'collaborative' => [
            'enabled' => env('RECOMMENDATION_COLLABORATIVE_ENABLED', true),
            'weight' => (float) env('RECOMMENDATION_COLLABORATIVE_WEIGHT', 0.8),
        ],
        'content_based' => [
            'enabled' => env('RECOMMENDATION_CONTENT_BASED_ENABLED', true),
            'weight' => (float) env('RECOMMENDATION_CONTENT_BASED_WEIGHT', 0.7),
        ],
        'graph_based' => [
            'enabled' => env('RECOMMENDATION_GRAPH_BASED_ENABLED', false),
            'weight' => (float) env('RECOMMENDATION_GRAPH_BASED_WEIGHT', 0.6),
        ],
        'bandit' => [
            'enabled' => env('RECOMMENDATION_BANDIT_ENABLED', true),
            'weight' => (float) env('RECOMMENDATION_BANDIT_WEIGHT', 0.5),
        ],
        'trending' => [
            'enabled' => env('RECOMMENDATION_TRENDING_ENABLED', true),
            'weight' => (float) env('RECOMMENDATION_TRENDING_WEIGHT', 0.4),
        ],
        'popular' => [
            'enabled' => env('RECOMMENDATION_POPULAR_ENABLED', true),
            'weight' => (float) env('RECOMMENDATION_POPULAR_WEIGHT', 0.3),
        ],
    ],

    'fallback' => [
        'enabled' => env('RECOMMENDATION_FALLBACK_ENABLED', true),
        'max_candidates' => (int) env('RECOMMENDATION_FALLBACK_MAX_CANDIDATES', 50),
    ],

    'monitoring' => [
        'drift_check_enabled' => env('RECOMMENDATION_DRIFT_CHECK_ENABLED', true),
        'drift_check_interval_hours' => (int) env('RECOMMENDATION_DRIFT_CHECK_INTERVAL_HOURS', 24),
        'psi_threshold' => (float) env('RECOMMENDATION_PSI_THRESHOLD', 0.25),
        'accuracy_drop_threshold' => (float) env('RECOMMENDATION_ACCURACY_DROP_THRESHOLD', 0.10),
    ],

    'ab_testing' => [
        'enabled' => env('RECOMMENDATION_AB_TESTING_ENABLED', true),
        'default_experiment_name' => env('RECOMMENDATION_DEFAULT_EXPERIMENT', 'recommendation_v1'),
    ],
];
