<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | CDN Provider
    |--------------------------------------------------------------------------
    |
    | The CDN provider to use for media optimization and delivery.
    | Options: cloudflare, bunny, none
    |
    */

    'provider' => env('CDN_PROVIDER', 'cloudflare'),

    /*
    |--------------------------------------------------------------------------
    | CDN Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable CDN integration.
    |
    */

    'enabled' => env('CDN_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Cloudflare Images and CDN.
    |
    */

    'cloudflare' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'account_key' => env('CLOUDFLARE_ACCOUNT_KEY'),
        'images_enabled' => env('CLOUDFLARE_IMAGES_ENABLED', false),
        'polish_enabled' => env('CLOUDFLARE_POLISH_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bunny CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Bunny CDN and Image Optimizer.
    |
    */

    'bunny' => [
        'api_key' => env('BUNNY_API_KEY'),
        'storage_zone' => env('BUNNY_STORAGE_ZONE'),
        'pull_zone' => env('BUNNY_PULL_ZONE'),
        'optimizer_enabled' => env('BUNNY_OPTIMIZER_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Video CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for video streaming CDN.
    | Options: bunny_stream, cloudflare_stream, none
    |
    */

    'video_provider' => env('CDN_VIDEO_PROVIDER', 'bunny_stream'),
    'video_enabled' => env('CDN_VIDEO_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Bunny Stream Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Bunny Stream video CDN.
    |
    */

    'bunny_stream' => [
        'api_key' => env('BUNNY_STREAM_API_KEY'),
        'library_id' => env('BUNNY_STREAM_LIBRARY_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Stream Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Cloudflare Stream video CDN.
    |
    */

    'cloudflare_stream' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_STREAM_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | CDN cache configuration.
    |
    */

    'cache' => [
        'ttl' => env('CDN_CACHE_TTL', 31536000), // 1 year
        'bypass_cache' => env('CDN_BYPASS_CACHE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Signed URL Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for signed URLs.
    |
    */

    'signed_url_ttl' => env('CDN_SIGNED_URL_TTL', 15), // 15 minutes

    /*
    |--------------------------------------------------------------------------
    | Auto Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Automatically upload new media files to CDN after upload.
    |
    */

    'auto_upload' => env('CDN_AUTO_UPLOAD', false),

];
