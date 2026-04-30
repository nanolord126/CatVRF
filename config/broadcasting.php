<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Broadcasting Driver
    |--------------------------------------------------------------------------
    | Default: pusher | ably | redis | log | null | reverb
    | For WebRTC live streaming, use 'reverb' (native Laravel WebSocket)
    */
    'default' => env('BROADCAST_DRIVER', 'reverb'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true,
                'encrypted' => true,
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
            'options' => [
                'autoConnect' => true,
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CLUSTER_ENABLED', false) ? 'cluster' : 'default',
        ],

        'reverb' => [
            'driver' => 'reverb',
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 6001),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'app_id' => env('REVERB_APP_ID', 'laravel'),
            'app_key' => env('REVERB_APP_KEY', 'your-app-key'),
            'app_secret' => env('REVERB_APP_SECRET', 'your-app-secret'),
            /*
            |--------------------------------------------------------------------------
            | Reverb Scaling Configuration
            |--------------------------------------------------------------------------
            |
            | Enable Redis adapter for horizontal scaling across multiple Reverb servers.
            | All Reverb instances will share channel state via Redis pub/sub.
            |
            */
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'redis' => [
                    'connection' => env('REDIS_CLUSTER_ENABLED', false) ? 'cluster' : env('REVERB_REDIS_CONNECTION', 'default'),
                    'prefix' => env('REVERB_REDIS_PREFIX', 'reverb'),
                ],
            ],
            /*
            |--------------------------------------------------------------------------
            | Connection Limits
            |--------------------------------------------------------------------------
            |
            | Maximum concurrent connections per Reverb instance.
            | Adjust based on your infrastructure capacity.
            |
            */
            'max_connections' => env('REVERB_MAX_CONNECTIONS', 10000),
            /*
            |--------------------------------------------------------------------------
            | Rate Limiting
            |--------------------------------------------------------------------------
            |
            | Global rate limiting for broadcast events to prevent abuse.
            |
            */
            'rate_limiting' => [
                'enabled' => env('REVERB_RATE_LIMITING_ENABLED', true),
                'max_messages_per_second' => env('REVERB_MAX_MESSAGES_PER_SECOND', 1000),
                'max_messages_per_minute_per_channel' => env('REVERB_MAX_MESSAGES_PER_MINUTE_PER_CHANNEL', 120),
            ],
        ],

        'swoole' => [
            'driver' => 'swoole',
            'host' => env('SWOOLE_WEBSOCKET_HOST', '0.0.0.0'),
            'port' => env('SWOOLE_WEBSOCKET_PORT', 9501),
            'scheme' => env('SWOOLE_WEBSOCKET_SCHEME', 'ws'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebRTC Configuration (Live Streaming Mesh)
    |--------------------------------------------------------------------------
    | TURN/STUN servers for NAT traversal
    */
    'webrtc' => [
        'stun' => env('WEBRTC_STUN', 'stun:stun.l.google.com:19302'),
        'turn' => [
            'url' => env('WEBRTC_TURN_URL', 'turn:your-turn-server:3478'),
            'username' => env('WEBRTC_TURN_USERNAME', ''),
            'credential' => env('WEBRTC_TURN_CREDENTIAL', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Vertical Scaling Configuration
    |--------------------------------------------------------------------------
    |
    | Global settings for WebSocket scaling across 28 verticals.
    | These settings work in conjunction with config/verticals.php
    |
    */
    'vertical_scaling' => [
        /*
        |--------------------------------------------------------------------------
        | Channel Sharding
        |--------------------------------------------------------------------------
        |
        | Enable vertical-prefixed channel sharding for Redis optimization.
        | Format: v_{vertical}:{channel}
        |
        */
        'channel_sharding' => [
            'enabled' => env('BROADCAST_CHANNEL_SHARDING_ENABLED', true),
            'prefix' => 'v',
        ],

        /*
        |--------------------------------------------------------------------------
        | Presence Channel Optimization
        |--------------------------------------------------------------------------
        |
        | Presence channels are expensive. Only use where necessary.
        | Verticals can override this in config/verticals.php
        |
        */
        'presence_channels' => [
            'default_enabled' => false,
            'max_users_per_channel' => 100,
        ],

        /*
        |--------------------------------------------------------------------------
        | Load Balancer Configuration
        |--------------------------------------------------------------------------
        |
        | Settings for horizontal scaling with load balancer.
        | Requires sticky sessions (session affinity).
        |
        */
        'load_balancer' => [
            'sticky_sessions' => [
                'enabled' => env('BROADCAST_STICKY_SESSIONS_ENABLED', true),
                'method' => env('BROADCAST_STICKY_SESSIONS_METHOD', 'cookie'), // 'cookie' or 'ip'
                'cookie_name' => env('BROADCAST_STICKY_COOKIE_NAME', 'reverb_server'),
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Monitoring & Metrics
        |--------------------------------------------------------------------------
        |
        | Enable metrics collection for Prometheus/Grafana monitoring.
        |
        */
        'monitoring' => [
            'enabled' => env('BROADCAST_MONITORING_ENABLED', true),
            'metrics_prefix' => 'broadcast',
            'collect_per_vertical_stats' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Fallback Configuration
        |--------------------------------------------------------------------------
        |
        | Fallback to polling if WebSocket connection fails.
        |
        */
        'fallback' => [
            'enabled' => env('BROADCAST_FALLBACK_ENABLED', true),
            'polling_interval' => env('BROADCAST_FALLBACK_POLLING_INTERVAL', 10000), // milliseconds
            'max_retries' => env('BROADCAST_FALLBACK_MAX_RETRIES', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    | Queue name for broadcast jobs
    */
    'queue' => [
        'connection' => env('QUEUE_CONNECTION', 'sync'),
        'queue' => env('QUEUE_NAME', 'default'),
    ],
];
