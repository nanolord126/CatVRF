<?php

declare(strict_types=1);

use Laravel\Octane\Contracts\Server;
use Laravel\Octane\Octane;
use App\Octane\Listeners\FlushSwooleTables;
use App\Octane\Listeners\InitializeSwooleTables;
use App\Octane\Listeners\LogWorkerError;
use App\Octane\Listeners\WarmupSwooleCache;

return [
    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines the default "server" that will be utilized by Octane
    | when starting, restarting, or stopping your server. The server specified
    | here should correspond to a server installation in your "server" array.
    |
    */

    'server' => env('OCTANE_SERVER', 'frankenphp'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Listener
    |--------------------------------------------------------------------------
    |
    | When this configuration value is set to "true", Octane will automatically
    | start an HTTPS listener using the certificates defined below. This
    | allows your application to serve requests over secure connections.
    |
    */

    'https' => env('OCTANE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | Octane's "tick" listeners allow you to register code that will be
    | executed on every "tick" of the Octane server. You may register
    | a closure or any callable class to be invoked on each tick.
    |
    */

    'listeners' => [
        'RequestReceived' => [
            // App\Listeners\FlushRequestState::class,
        ],

        'RequestHandled' => [
            // App\Listeners\CollectGarbage::class,
        ],

        'RequestTerminated' => [
            // App\Listeners\FlushTemporaryFiles::class,
        ],

        'TaskReceived' => [
            // App\Listeners\FlushRequestState::class,
        ],

        'TaskTerminated' => [
            // App\Listeners\FlushTemporaryFiles::class,
        ],

        'Tick' => [
            // App\Listeners\Heartbeat::class,
        ],

        'WorkerStarting' => [
            InitializeSwooleTables::class,
            WarmupSwooleCache::class,
        ],

        'WorkerStopping' => [
            FlushSwooleTables::class,
        ],

        'WorkerErrorOccurred' => [
            LogWorkerError::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    |
    | The bindings below will be either "warmed" or "flushed" on every
    | request or task, depending on the binding type. For example, the
    | session and auth bindings are typically flushed on every request.
    |
    */

    'warm' => [
        'cache',
        'config',
        'events',
        'routes',
        'views',
    ],

    'flush' => [
        'cache',
        'db',
        'octane',
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection Threshold
    |--------------------------------------------------------------------------
    |
    | When executing long-lived PHP scripts such as Octane, memory can
    | build up before being cleared by PHP. You may control how often
    | garbage collection is triggered by defining this threshold.
    |
    */

    'gc' => env('OCTANE_GC', null),

    /*
    |--------------------------------------------------------------------------
    | Maximum Execution Time
    |--------------------------------------------------------------------------
    |
    | The maximum execution time defines the maximum amount of time that
    | a request or task is allowed to run. You may set this value to
    | "null" to disable enforcing a maximum execution time.
    |
    */

    'max_execution_time' => env('OCTANE_MAX_EXECUTION_TIME', 30),

    /*
    |--------------------------------------------------------------------------
    | Swoole Server Options
    |--------------------------------------------------------------------------
    |
    | These options will be passed to the Swoole server during start.
    | You may review the Swoole documentation to find all of the
    | available options that may be configured for your server.
    |
    */

    'swoole' => [
        'worker_count' => env('SWOOLE_HTTP_WORKER_COUNT', function () {
            if (PHP_OS_FAMILY === 'Windows') {
                return 4;
            }
            $nproc = @shell_exec('nproc');

            return (int) $nproc ?: 4;
        }),
        'task_worker_count' => env('SWOOLE_TASK_WORKER_COUNT', 4),
        'task_worker_max_request' => env('SWOOLE_TASK_WORKER_MAX_REQUEST', 1000),
        'http_worker_max_request' => env('SWOOLE_HTTP_WORKER_MAX_REQUEST', 5000),
        'max_request' => env('SWOOLE_MAX_REQUEST', 5000),
        'enable_coroutine' => true,
        'hook_flags' => defined('SWOOLE_HOOK_ALL') ? SWOOLE_HOOK_ALL : 0,

        // Worker topology for CatVRF
        'dispatch_mode' => defined('SWOOLE_DISPATCH_ROUNDROBIN') ? SWOOLE_DISPATCH_ROUNDROBIN : 2,
        'package_max_length' => 20 * 1024 * 1024, // 20MB for large payloads
        'buffer_output_size' => 32 * 1024 * 1024, // 32MB buffer
        'socket_buffer_size' => 128 * 1024 * 1024, // 128MB socket buffer

        // Performance tuning
        'enable_static_handler' => true,
        'document_root' => base_path('public'),
        'static_handler_locations' => ['/public', '/storage/app/public'],

        // Graceful shutdown
        'reload_async' => true,
        'max_wait_time' => 60,

        // WebSocket support
        'open_websocket_protocol' => true,
        'websocket_subprotocol' => '',

        // Table memory
        'table_size' => 10240,

        // Logging
        'log_file' => storage_path('logs/swoole.log'),
        'log_level' => defined('SWOOLE_LOG_INFO') ? SWOOLE_LOG_INFO : 0,

        // Process management
        'daemonize' => env('SWOOLE_DAEMONIZE', false),
        'pid_file' => storage_path('octane-swoole.pid'),

        // SSL (if needed)
        'ssl_cert_file' => env('SWOOLE_SSL_CERT_FILE'),
        'ssl_key_file' => env('SWOOLE_SSL_KEY_FILE'),
    ],

    'frankenphp' => [
        'http' => [
            'host' => env('FRANKENPHP_HOST', '127.0.0.1'),
            'port' => env('FRANKENPHP_PORT', 8000),
        ],
        'workers' => [
            'count' => env('FRANKENPHP_WORKERS', function () {
                if (PHP_OS_FAMILY === 'Windows') {
                    return 4;
                }
                $nproc = @shell_exec('nproc');

                return (int) $nproc ?: 4;
            }),
            'max_requests' => env('FRANKENPHP_MAX_REQUESTS', 5000),
        ],
        'performance' => [
            'compress' => env('FRANKENPHP_COMPRESS', true),
            'logger' => env('FRANKENPHP_LOGGER', 'stdout'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RoadRunner Server Options
    |--------------------------------------------------------------------------
    |
    | These options will be passed to the RoadRunner server during start.
    | You may review the RoadRunner documentation to find all of the
    | available options that may be configured for your server.
    |
    */

    'roadrunner' => [
        'command' => env('OCTANE_ROADRUNNER_BINARY', 'rr'),
        'command_arguments' => [
            'serve',
            '-o', env('OCTANE_ROADRUNNER_CONFIG', '.rr.yaml'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Tables
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Swoole tables that will be created and
    | managed by Octane. These tables are extremely fast in-memory
    | data structures that can be used to store hot data.
    |
    */

    'tables' => [
        // Slot holds for medical appointments - 10000 slots, 2 hour TTL
        'slot_holds' => [
            'size' => 10000,
            'columns' => [
                'user_id' => ['type' => 'int', 'size' => 8],
                'doctor_id' => ['type' => 'int', 'size' => 8],
                'clinic_id' => ['type' => 'int', 'size' => 8],
                'slot_time' => ['type' => 'int', 'size' => 8],
                'expires_at' => ['type' => 'int', 'size' => 8],
                'status' => ['type' => 'string', 'size' => 20],
                'created_at' => ['type' => 'int', 'size' => 8],
            ],
        ],

        // Quota counters for API rate limiting - 5000 entries
        'quota_counters' => [
            'size' => 5000,
            'columns' => [
                'user_id' => ['type' => 'int', 'size' => 8],
                'tenant_id' => ['type' => 'int', 'size' => 8],
                'endpoint' => ['type' => 'string', 'size' => 100],
                'count' => ['type' => 'int', 'size' => 4],
                'window_start' => ['type' => 'int', 'size' => 8],
                'reset_at' => ['type' => 'int', 'size' => 8],
            ],
        ],

        // Active video consultation rooms - 1000 concurrent rooms
        'video_rooms' => [
            'size' => 1000,
            'columns' => [
                'room_id' => ['type' => 'string', 'size' => 64],
                'doctor_id' => ['type' => 'int', 'size' => 8],
                'patient_id' => ['type' => 'int', 'size' => 8],
                'status' => ['type' => 'string', 'size' => 20],
                'started_at' => ['type' => 'int', 'size' => 8],
                'expires_at' => ['type' => 'int', 'size' => 8],
                'participant_count' => ['type' => 'int', 'size' => 2],
            ],
        ],

        // Rate limiters for API endpoints - 10000 entries
        'rate_limits' => [
            'size' => 10000,
            'columns' => [
                'identifier' => ['type' => 'string', 'size' => 64],
                'key' => ['type' => 'string', 'size' => 100],
                'count' => ['type' => 'int', 'size' => 4],
                'reset_at' => ['type' => 'int', 'size' => 8],
                'blocked_until' => ['type' => 'int', 'size' => 8],
            ],
        ],

        // Session cache for high-frequency operations - 5000 entries
        'session_cache' => [
            'size' => 5000,
            'columns' => [
                'session_id' => ['type' => 'string', 'size' => 64],
                'user_id' => ['type' => 'int', 'size' => 8],
                'data' => ['type' => 'string', 'size' => 4096],
                'last_activity' => ['type' => 'int', 'size' => 8],
                'expires_at' => ['type' => 'int', 'size' => 8],
            ],
        ],

        // Fraud detection cache - 3000 entries
        'fraud_cache' => [
            'size' => 3000,
            'columns' => [
                'user_id' => ['type' => 'int', 'size' => 8],
                'ip_address' => ['type' => 'string', 'size' => 45],
                'fingerprint' => ['type' => 'string', 'size' => 64],
                'risk_score' => ['type' => 'int', 'size' => 2],
                'flags' => ['type' => 'string', 'size' => 255],
                'last_checked' => ['type' => 'int', 'size' => 8],
                'expires_at' => ['type' => 'int', 'size' => 8],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | While using Swoole, Octane recommends using an in-memory cache such
    | as Swoole Table. You may configure your cache stores below to use
    | the Swoole table driver for improved performance.
    |
    */

    'cache' => [
        'stores' => [
            'swoole' => [
                'driver' => 'octane',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Cache Table
    |--------------------------------------------------------------------------
    |
    | While using the Swoole server, Octane can store cache data in a
    | Swoole table for better performance. You may configure the
    | table size and columns below based on your needs.
    |
    */

    'cache_table' => [
        'size' => env('SWOOLE_CACHE_TABLE_SIZE', 10000),
        'columns' => [
            'key' => ['type' => 'string', 'size' => 255],
            'value' => ['type' => 'string', 'size' => 8192],
            'expiration' => ['type' => 'int', 'size' => 8],
        ],
    ],
];
