<?php

declare(strict_types=1);

use Laravel\Pulse\Http\Middleware\Authorize;
use Laravel\Pulse\Recorders;

return [

    /*
    |--------------------------------------------------------------------------
    | Pulse Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Pulse will be accessible from. The domain
    | will also be used as the prefix for queue names and database tables.
    |
    */

    'domain' => env('PULSE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Pulse Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Pulse will be accessible from. Feel free
    | to change this path to anything you like.
    |
    */

    'path' => env('PULSE_PATH', 'pulse'),

    /*
    |--------------------------------------------------------------------------
    | Pulse Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to completely disable Pulse, regardless of any
    | other configuration options. It's a master switch for the service.
    |
    */

    'enabled' => env('PULSE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Pulse Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the storage driver that will be
    | used to store Pulse's data. In addition, you may set any connection
    | specific options that the driver may need.
    |
    */

    'storage' => [
        'driver' => env('PULSE_STORAGE_DRIVER', 'redis'),
        'connection' => env('PULSE_STORAGE_CONNECTION'),
        'chunk' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Ingest Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the ingest driver that will be
    | used to ingest Pulse's data. In addition, you may set any connection
    | specific options that the driver may need.
    |
    */

    'ingest' => [
        'driver' => env('PULSE_INGEST_DRIVER', 'redis'),
        'connection' => env('PULSE_INGEST_CONNECTION'),
        'trim' => [
            'lottery' => [1, 1_000],
            'keep' => '7 days',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Pulse route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'web',
        Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Recorders
    |--------------------------------------------------------------------------
    |
    | The following array lists the "recorders" that will be registered with
    | Pulse. The recorders gather application event data and store it in
    | the Pulse storage. Feel free to customize this list as needed.
    |
    */

    'recorders' => [
        Recorders\JobsRecorder::class => [
            'enabled' => env('PULSE_JOBS_RECORDER_ENABLED', true),
            'sample_rate' => env('PULSE_JOBS_SAMPLE_RATE', 1),
            'ignore' => [
                '/^Laravel\\\\Horizon\\\\Jobs\\\\/',
                '/^App\\\\Jobs\\\\/',
            ],
        ],

        Recorders\ServersRecorder::class => [
            'enabled' => env('PULSE_SERVERS_RECORDER_ENABLED', true),
            'sample_rate' => env('PULSE_SERVERS_SAMPLE_RATE', 1),
            'server_name' => env('PULSE_SERVER_NAME', gethostname()),
        ],

        Recorders\CacheInteractionsRecorder::class => [
            'enabled' => env('PULSE_CACHE_INTERACTIONS_RECORDER_ENABLED', true),
            'sample_rate' => env('PULSE_CACHE_INTERACTIONS_SAMPLE_RATE', 0.1),
            'ignore' => [
                '/^framework/',
                '/^laravel/',
            ],
        ],

        Recorders\ExceptionsRecorder::class => [
            'enabled' => env('PULSE_EXCEPTIONS_RECORDER_ENABLED', true),
            'sample_rate' => env('PULSE_EXCEPTIONS_SAMPLE_RATE', 1),
            'ignore' => [
                '/^Laravel\\\\/',
            ],
        ],

        Recorders\RequestsRecorder::class => [
            'enabled' => env('PULSE_REQUESTS_RECORDER_ENABLED', true),
            'sample_rate' => env('PULSE_REQUESTS_SAMPLE_RATE', 0.1),
            'ignore' => [
                '/^pulse',
            ],
        ],

        Recorders\SlowQueriesRecorder::class => [
            'enabled' => env('PULSE_SLOW_QUERIES_RECORDER_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_QUERIES_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_QUERIES_THRESHOLD', 1000),
        ],

        Recorders\SlowOutgoingRequestsRecorder::class => [
            'enabled' => env('PULSE_SLOW_OUTGOING_REQUESTS_RECORDER_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_OUTGOING_REQUESTS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_OUTGOING_REQUESTS_THRESHOLD', 5000),
        ],

        Recorders\SlowJobsRecorder::class => [
            'enabled' => env('PULSE_SLOW_JOBS_RECORDER_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_JOBS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_JOBS_THRESHOLD', 60000),
        ],

        // Supermarket-specific recorders
        Recorders\UserJobsRecorder::class => [
            'enabled' => env('PULSE_USER_JOBS_RECORDER_ENABLED', true),
            'sample_rate' => env('PULSE_USER_JOBS_SAMPLE_RATE', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Dashboard Options
    |--------------------------------------------------------------------------
    |
    | The following options configure the Pulse dashboard appearance and
    | behavior. You may customize these options to suit your needs.
    |
    */

    'dashboard' => [
        'enabled' => env('PULSE_DASHBOARD_ENABLED', true),
        'timezone' => env('PULSE_TIMEZONE', config('app.timezone')),
        'only_on' => env('PULSE_DASHBOARD_ONLY_ON'),
        'dark_mode' => env('PULSE_DASHBOARD_DARK_MODE', false),
        'date_format' => env('PULSE_DATE_FORMAT', 'Y-m-d H:i:s'),
        'date_timezone' => env('PULSE_DATE_TIMEZONE', config('app.timezone')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Capsule Configuration
    |--------------------------------------------------------------------------
    |
    | Pulse allows you to configure "capsules" which are isolated instances
    | of Pulse that can be used to monitor different parts of your app.
    |
    */

    'capsules' => [
        'supermarket' => [
            'enabled' => env('PULSE_SUPERMARKET_CAPSULE_ENABLED', true),
            'storage' => [
                'driver' => env('PULSE_SUPERMARKET_STORAGE_DRIVER', 'redis'),
                'connection' => env('PULSE_SUPERMARKET_STORAGE_CONNECTION'),
            ],
            'ingest' => [
                'driver' => env('PULSE_SUPERMARKET_INGEST_DRIVER', 'redis'),
                'connection' => env('PULSE_SUPERMARKET_INGEST_CONNECTION'),
            ],
        ],
    ],

];
