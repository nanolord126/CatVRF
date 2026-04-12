<?php

declare(strict_types=1);

/**
 * CatVRF 2026 — Laravel Horizon Configuration.
 *
 * Queue management and monitoring for all async jobs.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    */
    'domain' => env('HORIZON_DOMAIN'),

    'path' => 'horizon',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    */
    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', 'catvrf_horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => ['web', 'auth', 'can:access-horizon'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    | Alert if jobs wait longer than these times (seconds).
    */
    'waits' => [
        'redis:default' => 60,
        'redis:fraud-notifications' => 10,
        'redis:payments' => 15,
        'redis:audit-logs' => 30,
        'redis:ml' => 120,
        'redis:delivery' => 20,
        'redis:marketing' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times (minutes)
    |--------------------------------------------------------------------------
    */
    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080, // 7 days
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    | Jobs that should not appear in the recent jobs list.
    */
    'silenced' => [],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    */
    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    */
    'memory_limit' => 256,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-critical' => [
                'connection' => 'redis',
                'queue' => ['fraud-notifications', 'payments'],
                'balance' => 'auto',
                'maxProcesses' => 20,
                'memory' => 256,
                'tries' => 5,
                'timeout' => 60,
                'nice' => 0,
            ],
            'supervisor-default' => [
                'connection' => 'redis',
                'queue' => ['default', 'audit-logs', 'delivery', 'marketing'],
                'balance' => 'auto',
                'maxProcesses' => 15,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 120,
                'nice' => 0,
            ],
            'supervisor-ml' => [
                'connection' => 'redis',
                'queue' => ['ml', 'recommendations', 'embeddings'],
                'balance' => 'auto',
                'maxProcesses' => 5,
                'memory' => 512,
                'tries' => 2,
                'timeout' => 300,
                'nice' => 0,
            ],
            'supervisor-bulk' => [
                'connection' => 'redis',
                'queue' => ['bulk-import', 'reports', 'exports'],
                'balance' => 'simple',
                'maxProcesses' => 3,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 600,
                'nice' => 0,
            ],
        ],

        'local' => [
            'supervisor-default' => [
                'connection' => 'redis',
                'queue' => ['default', 'fraud-notifications', 'payments', 'audit-logs', 'delivery', 'ml', 'marketing'],
                'balance' => 'auto',
                'maxProcesses' => 3,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 120,
                'nice' => 0,
            ],
        ],
    ],
];
