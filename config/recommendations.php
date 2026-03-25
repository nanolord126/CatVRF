<?php declare(strict_types=1);

return [
    // Embedding configuration
    'embeddings' => [
        'provider' => env('EMBEDDING_PROVIDER', 'openai'),
        'model' => env('EMBEDDING_MODEL', 'text-embedding-3-large'),
        'dimension' => 3072,
        'recalculate_interval' => 'daily',
        'recalculate_time' => '04:30',
    ],

    // OpenAI configuration
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],

    // Cache configuration
    'cache' => [
        'ttl_dynamic' => 300, // 5 minutes
        'ttl_stable' => 3600, // 1 hour
        'prefix' => 'recommendation:',
    ],

    // Recommendation sources
    'sources' => [
        'behavior' => ['weight' => 0.45, 'enabled' => true],
        'geo' => ['weight' => 0.25, 'enabled' => true],
        'embedding' => ['weight' => 0.20, 'enabled' => true],
        'business_rules' => ['weight' => 0.10, 'enabled' => true],
        'popularity' => ['weight' => 0.05, 'enabled' => true],
    ],

    // Recommendation limits
    'limits' => [
        'max_recommendations' => 10,
        'min_similarity_score' => 0.75,
        'min_ctr' => 0.08,
        'min_revenue_lift' => 0.15,
    ],

    // Quality metrics
    'quality' => [
        'track_ctr' => true,
        'track_conversion' => true,
        'track_revenue_lift' => true,
        'daily_report_time' => '08:00',
        'alert_threshold_ctr' => 0.05,
        'alert_threshold_revenue' => 0.10,
    ],

    // A/B testing
    'ab_testing' => [
        'enabled' => true,
        'model_v1_traffic' => 80,
        'model_v2_traffic' => 20,
        'feature_flag' => 'recommend_model_v2',
    ],

    // Rate limiting
    'rate_limiting' => [
        'enabled' => true,
        'requests_per_minute_per_user' => 100,
        'requests_per_minute_light' => 100,
        'requests_per_minute_heavy' => 10,
    ],

    // Monitoring
    'monitoring' => [
        'log_channel' => 'recommend',
        'log_quality_channel' => 'recommend_quality',
        'sentry_enabled' => true,
    ],
];
