<?php

declare(strict_types=1);

use App\Services\Infrastructure\DopplerService;

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
        'key' => DopplerService::get('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => DopplerService::get('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => DopplerService::get('AWS_ACCESS_KEY_ID'),
        'secret' => DopplerService::get('AWS_SECRET_ACCESS_KEY'),
        'region' => DopplerService::get('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => DopplerService::get('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => DopplerService::get('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openweathermap' => [
        'api_key' => env('OPENWEATHERMAP_API_KEY'),
        'base_url' => env('OPENWEATHERMAP_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
        'cache_ttl' => env('OPENWEATHERMAP_CACHE_TTL', 1800), // 30 minutes
        'units' => env('OPENWEATHERMAP_UNITS', 'metric'),
    ],

    // Booking.com Marketplace Integration
    'booking_com' => [
        'api_key' => env('BOOKING_COM_API_KEY'),
        'hotel_id' => env('BOOKING_COM_HOTEL_ID'),
        'webhook_secret' => env('BOOKING_COM_WEBHOOK_SECRET'),
        'api_base_url' => env('BOOKING_COM_API_BASE_URL', 'https://supply-xml.booking.com'),
    ],

    // Ostrovok Marketplace Integration
    'ostrovok' => [
        'api_key' => env('OSTROVOK_API_KEY'),
        'hotel_id' => env('OSTROVOK_HOTEL_ID'),
        'webhook_secret' => env('OSTROVOK_WEBHOOK_SECRET'),
        'api_base_url' => env('OSTROVOK_API_BASE_URL', 'https://partner.ostrovok.ru/api'),
    ],

    // Airbnb Marketplace Integration
    'airbnb' => [
        'client_id' => env('AIRBNB_CLIENT_ID'),
        'client_secret' => env('AIRBNB_CLIENT_SECRET'),
        'webhook_token' => env('AIRBNB_WEBHOOK_TOKEN'),
        'api_base_url' => env('AIRBNB_API_BASE_URL', 'https://api.airbnb.com/v1'),
    ],

    // Telegram Bot Integration
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    // WhatsApp Integration
    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'ultramsg'),
        'base_url' => env('WHATSAPP_BASE_URL'),
        'token' => env('WHATSAPP_TOKEN'),
        'instance_id' => env('WHATSAPP_INSTANCE_ID'),
        'from_number' => env('WHATSAPP_FROM_NUMBER'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    ],

    // Firebase Cloud Messaging
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'project_id' => env('FCM_PROJECT_ID'),
    ],

    // Web Push
    'web_push' => [
        'vapid_public_key' => env('WEB_PUSH_VAPID_PUBLIC_KEY'),
        'vapid_private_key' => env('WEB_PUSH_VAPID_PRIVATE_KEY'),
    ],

    // Apple Push Notification Service (APNs) for iMessage
    'apns' => [
        'key_id' => env('APNS_KEY_ID'),
        'team_id' => env('APNS_TEAM_ID'),
        'private_key' => env('APNS_PRIVATE_KEY'),
        'bundle_id' => env('APNS_BUNDLE_ID'),
        'sandbox' => env('APNS_SANDBOX', true),
    ],

    // Viber
    'viber' => [
        'api_token' => env('VIBER_API_TOKEN'),
        'sender_name' => env('VIBER_SENDER_NAME', 'CatVRF'),
        'api_url' => env('VIBER_API_URL', 'https://chatapi.viber.com/pa'),
    ],

    // KakaoTalk
    'kakao' => [
        'api_key' => env('KAKAO_API_KEY'),
        'api_secret' => env('KAKAO_API_SECRET'),
        'sender_number' => env('KAKAO_SENDER_NUMBER'),
        'api_url' => env('KAKAO_API_URL', 'https://api.kakaotalk.com'),
    ],

    // Signal (third-party service)
    'signal' => [
        'api_url' => env('SIGNAL_API_URL'),
        'number' => env('SIGNAL_NUMBER'),
    ],

    // WeChat
    'wechat' => [
        'app_id' => env('WECHAT_APP_ID'),
        'app_secret' => env('WECHAT_APP_SECRET'),
        'template_id' => env('WECHAT_TEMPLATE_ID'),
        'api_url' => env('WECHAT_API_URL', 'https://api.weixin.qq.com/cgi-bin'),
    ],

    // VKontakte (VK)
    'vk' => [
        'access_token' => env('VK_ACCESS_TOKEN'),
        'api_version' => env('VK_API_VERSION', '5.131'),
        'api_url' => env('VK_API_URL', 'https://api.vk.com/method'),
    ],

    // Odnoklassniki
    'odnoklassniki' => [
        'application_key' => env('ODNOKLASSNIKI_APPLICATION_KEY'),
        'application_secret' => env('ODNOKLASSNIKI_APPLICATION_SECRET'),
        'access_token' => env('ODNOKLASSNIKI_ACCESS_TOKEN'),
        'api_url' => env('ODNOKLASSNIKI_API_URL', 'https://api.ok.ru'),
    ],

];
