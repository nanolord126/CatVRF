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
            'connection' => 'default',
        ],

        'reverb' => [
            'driver' => 'reverb',
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 6001),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'app_id' => env('REVERB_APP_ID', 'laravel'),
            'app_key' => env('REVERB_APP_KEY', 'your-app-key'),
            'app_secret' => env('REVERB_APP_SECRET', 'your-app-secret'),
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
    | Queue Configuration
    |--------------------------------------------------------------------------
    | Queue name for broadcast jobs
    */
    'queue' => [
        'connection' => env('QUEUE_CONNECTION', 'sync'),
        'queue' => env('QUEUE_NAME', 'default'),
    ],
];
