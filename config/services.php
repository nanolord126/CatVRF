<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => App\Services\Infrastructure\DopplerService::get('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => App\Services\Infrastructure\DopplerService::get('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => App\Services\Infrastructure\DopplerService::get('AWS_ACCESS_KEY_ID'),
        'secret' => App\Services\Infrastructure\DopplerService::get('AWS_SECRET_ACCESS_KEY'),
        'region' => App\Services\Infrastructure\DopplerService::get('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => App\Services\Infrastructure\DopplerService::get('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => App\Services\Infrastructure\DopplerService::get('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
