<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Telegram bot integration for order notifications
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'bot_username' => env('TELEGRAM_BOT_USERNAME', ''),
    
    'webhook' => [
        'url' => env('TELEGRAM_WEBHOOK_URL', ''),
        'secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
    ],

    'notifications' => [
        'enabled' => env('TELEGRAM_NOTIFICATIONS_ENABLED', true),
        
        'events' => [
            'created' => true,
            'confirmed' => true,
            'ready_for_delivery' => true,
            'in_delivery' => true,
            'delivered' => true,
            'cancelled' => true,
        ],
        
        'retry_attempts' => 3,
        'retry_delay_seconds' => 5,
    ],

    'commands' => [
        'start' => '/start',
        'help' => '/help',
        'status' => '/status',
        'unsubscribe' => '/unsubscribe',
    ],
];
