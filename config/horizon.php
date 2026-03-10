<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This value is the domain where Horizon will be accessible from. If this
    | value is null, then Horizon will be accessible from all domains.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | operational data that it needs to run its dashboard and monitor
    | its various queues. This should not be your default connection.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You may
    | wish to change this prefix if you are running multiple applications
    | using the same Redis database for your Horizon instances.
    |
    */

    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | 100k req/min Heavy Load Optimized Setup (2026 Standard).
    | Auto-scaling workers for high-throughput processing.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],

    'metrics' => [
        'trim_days' => [
            'overall' => 7,
            'queue' => 7,
        ],
    ],

    'fast_termination' => true,

    'memory_limit_recovery_threshold' => 80,

    'defaults' => [
        'supervisor-heavy' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',
            'autoScale' => true,
            'minProcesses' => 5,
            'maxProcesses' => 50, // Massive scaling for 100k load
            'maxWaitTime' => 60,
            'maxJobs' => 1000,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 90,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-heavy' => [
                'maxProcesses' => 100, // Top-tier scaling for Production 2026
                'balance' => 'auto',
            ],
            'supervisor-notifications' => [
                'connection' => 'redis',
                'queue' => ['notifications'],
                'balance' => 'simple',
                'processes' => 20,
                'tries' => 3,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
        ],
    ],
];
