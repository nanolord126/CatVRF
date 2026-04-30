<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | FFmpeg Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for video processing with FFmpeg.
    |
    */

    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),

    /*
    |--------------------------------------------------------------------------
    | LiveKit Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for LiveKit WebRTC service.
    |
    */

    'livekit' => [
        'host_url' => env('LIVEKIT_HOST_URL', 'ws://localhost:7880'),
        'url' => env('LIVEKIT_URL', 'ws://localhost:7880'),
        'api_key' => env('LIVEKIT_API_KEY'),
        'api_secret' => env('LIVEKIT_API_SECRET'),
        'enabled' => env('LIVEKIT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recording Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for video recording storage and retention.
    |
    */

    'recording' => [
        'storage_disk' => env('VIDEO_RECORDING_DISK', 's3'),
        'retention_days' => env('VIDEO_RECORDING_RETENTION_DAYS', 30),
        'auto_process' => env('VIDEO_RECORDING_AUTO_PROCESS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for video transcoding and optimization.
    |
    */

    'optimization' => [
        'enable_hls' => env('VIDEO_ENABLE_HLS', true),
        'enable_avif' => env('VIDEO_ENABLE_AVIF', false),
        'qualities' => [
            '360p' => ['width' => 640, 'height' => 360, 'bitrate' => '800k'],
            '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => '2800k'],
            '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => '5000k'],
        ],
        'watermark_enabled' => env('VIDEO_WATERMARK_ENABLED', false),
        'watermark_text' => env('VIDEO_WATERMARK_TEXT', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue configuration for video processing jobs.
    |
    */

    'queue' => [
        'video_processing' => env('VIDEO_PROCESSING_QUEUE', 'video-processing'),
        'timeout' => env('VIDEO_PROCESSING_TIMEOUT', 1800),
        'tries' => env('VIDEO_PROCESSING_TRIES', 3),
    ],

];
