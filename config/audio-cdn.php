<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Audio CDN Provider
    |--------------------------------------------------------------------------
    |
    | Supported providers: bunny, cloudflare, aws
    |
    */
    'provider' => env('AUDIO_CDN_PROVIDER', 'bunny'),

    /*
    |--------------------------------------------------------------------------
    | Bunny CDN Configuration
    |--------------------------------------------------------------------------
    */
    'bunny' => [
        'api_key' => env('BUNNY_API_KEY'),
        'storage_zone' => env('BUNNY_STORAGE_ZONE'),
        'hostname' => env('BUNNY_AUDIO_HOSTNAME'),
        'pull_zone' => env('BUNNY_PULL_ZONE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Stream Configuration
    |--------------------------------------------------------------------------
    */
    'cloudflare' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'email' => env('CLOUDFLARE_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS CloudFront + S3 Configuration
    |--------------------------------------------------------------------------
    */
    'aws' => [
        'cloudfront_domain' => env('AWS_CLOUDFRONT_DOMAIN'),
        'cloudfront_key_id' => env('AWS_CLOUDFRONT_KEY_ID'),
        'cloudfront_private_key' => env('AWS_CLOUDFRONT_PRIVATE_KEY'),
        's3_bucket' => env('AWS_S3_AUDIO_BUCKET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Processing Settings
    |--------------------------------------------------------------------------
    */
    'processing' => [
        'default_format' => env('AUDIO_DEFAULT_FORMAT', 'opus'),
        'opus_bitrate' => env('AUDIO_OPUS_BITRATE', '64k'),
        'aac_bitrate' => env('AUDIO_AAC_BITRATE', '128k'),
        'sample_rate' => env('AUDIO_SAMPLE_RATE', '48000'),
        'loudnorm_target' => env('AUDIO_LOUDNORM_TARGET', 'I=-16:TP=-1.5:LRA=11'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Signed URL Settings
    |--------------------------------------------------------------------------
    */
    'signed_urls' => [
        'default_ttl_minutes' => env('AUDIO_SIGNED_URL_TTL', 1440), // 24 hours
        'max_ttl_minutes' => env('AUDIO_SIGNED_URL_MAX_TTL', 10080), // 7 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'name' => env('AUDIO_QUEUE_NAME', 'audio-processing'),
        'connection' => env('AUDIO_QUEUE_CONNECTION', 'redis'),
        'memory_limit' => env('AUDIO_QUEUE_MEMORY_LIMIT', '512'), // MB
    ],

    /*
    |--------------------------------------------------------------------------
    | FFmpeg Path
    |--------------------------------------------------------------------------
    */
    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),
];
