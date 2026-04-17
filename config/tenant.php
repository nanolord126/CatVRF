<?php declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-tenant architecture including quotas,
    | rate limits, security settings, and vertical management.
    |
    */

    'quotas' => [
        'ai_tokens' => [
            'default' => 1000000, // 1M tokens per day
            'premium' => 10000000, // 10M tokens per day
        ],
        'redis_ops' => [
            'default' => 100000, // 100K ops per hour
            'premium' => 1000000, // 1M ops per hour
        ],
        'db_queries' => [
            'default' => 50000, // 50K queries per hour
            'premium' => 500000, // 500K queries per hour
        ],
        'storage_bytes' => [
            'default' => 10 * 1024 * 1024 * 1024, // 10GB per day
            'premium' => 100 * 1024 * 1024 * 1024, // 100GB per day
        ],
    ],

    'rate_limits' => [
        'default' => [
            'limit' => 100,
            'window' => 60, // seconds
        ],
        'api' => [
            'limit' => 1000,
            'window' => 60,
        ],
        'ai' => [
            'limit' => 50,
            'window' => 60,
        ],
    ],

    'security' => [
        'ip_whitelist_enabled' => env('TENANT_IP_WHITELIST_ENABLED', false),
        'signature_required' => env('TENANT_SIGNATURE_REQUIRED', true),
        'signature_ttl' => 300, // 5 minutes
    ],

    'identification' => [
        'header' => env('TENANT_HEADER_RESOLVER', false),
        'header_name' => 'X-Tenant-ID',
        'subdomain' => true,
    ],

    'verticals' => [
        'enabled' => [
            'Medical',
            'Beauty',
            'Food',
            'Delivery',
            'Taxi',
            'Hotels',
            'Auto',
            'RealEstate',
            'Fashion',
            'Electronics',
        ],
        'default_verticals' => ['Medical', 'Beauty'],
    ],

    'retention' => [
        'soft_delete_days' => 90,
        'hard_delete_days' => 365,
        'anonymize_before_delete' => true,
    ],

    'observability' => [
        'enabled' => env('TENANT_OBSERVABILITY_ENABLED', true),
        'prometheus_endpoint' => env('TENANT_PROMETHEUS_ENDPOINT', '/metrics/tenant'),
        'metrics_ttl' => 86400,
    ],
];
