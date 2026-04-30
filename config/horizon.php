<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
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
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web', 'authorize'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the Long Wait Detected
    | notifications will be sent for each queue.
    |
    */

    'waits' => [
        'redis:default' => 60,
        'redis:emergency' => 30,
        'redis:payment' => 45,
        'redis:payment-webhook' => 45,
        'redis:fraud-check-payment' => 45,
        'redis:notification' => 90,
        'redis:audit' => 90,
        'redis:ml-recalculate' => 300,
        'redis:delivery' => 120,
        'redis:bulk' => 180,
        'redis:filament-heavy' => 180,
        'redis:filament-light' => 60,
        'redis:ml-retrain-high-priority' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silent Jobs
    |--------------------------------------------------------------------------
    |
    | Silent jobs will not have their exceptions or out-of-memory errors
    | reported to the "recent" job list. You may wish to mark some of your
    | jobs as silent if they are not important or if they run very frequently.
    |
    */

    'silenced' => [
        // 'App\Jobs\SomeJob',
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how long the metrics are stored. By default all
    | metrics are stored for 7 days. You may store them for a shorter or
    | longer period of time if you wish.
    |
    */

    'metrics' => [
        'trim_slugs' => [
            'horizon_job_wait_time' => 30,
            'horizon_jobs_throughput' => 30,
            'horizon_jobs_runtime' => 30,
            'horizon_jobs_failed' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing the new instance of the application to immediately start.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon master
    | supervisor may consume before it is terminated and restarted. You
    | should generally leave this value alone unless it is too low.
    |
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Supervisor Configuration
    |--------------------------------------------------------------------------
    |
    | Supervisors provide the fundamental building blocks of Horizon. Each
    | supervisor is responsible for monitoring a pool of queue workers.
    | You may define one or more supervisors here.
    |
    */

    'supervisors' => [
        [
            'name' => 'emergency',
            'connection' => 'redis',
            'queue' => ['emergency'],
            'balance' => 'simple',
            'processes' => 2,
            'tries' => 3,
            'timeout' => 30,
            'nice' => -5, // Highest priority
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 128,
            'sleep' => 1,
            'max_tries' => 3,
            'delay' => 0,
        ],
        [
            'name' => 'payment',
            'connection' => 'redis',
            'queue' => ['payment', 'payment-webhook', 'fraud-check-payment'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
            'timeout' => 90,
            'nice' => -3, // High priority
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 256,
            'sleep' => 1,
            'max_tries' => 3,
            'delay' => 0,
        ],
        [
            'name' => 'notification',
            'connection' => 'redis',
            'queue' => ['notification'],
            'balance' => 'simple',
            'processes' => 4,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 256,
            'sleep' => 1,
            'max_tries' => 3,
            'delay' => 0,
        ],
        [
            'name' => 'audit',
            'connection' => 'redis',
            'queue' => ['audit'],
            'balance' => 'simple',
            'processes' => 2,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 128,
            'sleep' => 1,
            'max_tries' => 3,
            'delay' => 0,
        ],
        [
            'name' => 'ml-retrain-high-priority',
            'connection' => 'redis',
            'queue' => ['ml-retrain-high-priority'],
            'balance' => 'simple',
            'processes' => 2,
            'tries' => 2,
            'timeout' => 3600,
            'nice' => -5,
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 1024,
            'sleep' => 5,
            'max_tries' => 2,
            'delay' => 0,
        ],
        [
            'name' => 'default',
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'processes' => 4,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 128,
            'sleep' => 3,
            'max_tries' => 3,
            'delay' => 0,
        ],
        [
            'name' => 'delivery',
            'connection' => 'redis',
            'queue' => ['delivery'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
            'timeout' => 180,
            'nice' => 0,
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 256,
            'sleep' => 2,
            'max_tries' => 3,
            'delay' => 0,
        ],
        [
            'name' => 'bulk',
            'connection' => 'redis',
            'queue' => ['bulk'],
            'balance' => 'simple',
            'processes' => 2,
            'tries' => 2,
            'timeout' => 300,
            'nice' => 5, // Low priority
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 512,
            'sleep' => 5,
            'max_tries' => 2,
            'delay' => 10,
        ],
        [
            'name' => 'filament-heavy',
            'connection' => 'redis',
            'queue' => ['filament-heavy'],
            'balance' => 'simple',
            'processes' => 2,
            'tries' => 2,
            'timeout' => 300,
            'nice' => 0,
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 512,
            'sleep' => 3,
            'max_tries' => 2,
            'delay' => 0,
        ],
        [
            'name' => 'filament-light',
            'connection' => 'redis',
            'queue' => ['filament-light'],
            'balance' => 'simple',
            'processes' => 2,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 128,
            'sleep' => 1,
            'max_tries' => 3,
            'delay' => 0,
        ],
        [
            'name' => 'logistics',
            'connection' => 'redis',
            'queue' => ['logistics'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
            'timeout' => 120,
            'nice' => -2, // High priority for real-time logistics
            'max_jobs' => 0,
            'max_time' => 0,
            'memory' => 256,
            'sleep' => 1,
            'max_tries' => 3,
            'delay' => 0,
        ],
    ],
];
