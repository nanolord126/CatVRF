<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Filesystem
    |--------------------------------------------------------------------------
    |
    | The filesystem disk to use for media storage.
    |
    */

    'disk_name' => env('MEDIA_DISK', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Media Paths
    |--------------------------------------------------------------------------
    |
    | The path structure for media files.
    |
    */

    'path_generator' => null,

    /*
    |--------------------------------------------------------------------------
    | File Names
    |--------------------------------------------------------------------------
    |
    | The file naming strategy.
    |
    */

    'file_namer' => null,

    /*
    |--------------------------------------------------------------------------
    | URL Generator
    |
    |--------------------------------------------------------------------------
    |
    | The URL generator to use for media URLs.
    |
    */

    'url_generator' => null,

    /*
    |--------------------------------------------------------------------------
    | Image Optimizations
    |--------------------------------------------------------------------------
    |
    | Configuration for image optimization.
    |
    */

    'image_optimizations' => [
        'enable_webp' => env('MEDIA_ENABLE_WEBP', true),
        'enable_avif' => env('MEDIA_ENABLE_AVIF', false),
        'webp_quality' => env('MEDIA_WEBP_QUALITY', 82),
        'avif_quality' => env('MEDIA_AVIF_QUALITY', 78),
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Images
    |--------------------------------------------------------------------------
    |
    | Configuration for responsive image generation.
    |
    */

    'responsive_images' => [
        'enable' => env('MEDIA_RESPONSIVE_IMAGES', true),
        'widths' => [300, 600, 900, 1200, 1600],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Conversions
    |--------------------------------------------------------------------------
    |
    | Default media conversions for all models.
    |
    */

    'conversions' => [
        'avatar' => [
            'width' => 512,
            'height' => 512,
            'quality' => 85,
        ],
        'thumb' => [
            'width' => 300,
            'height' => 300,
            'quality' => 80,
        ],
        'medium' => [
            'width' => 1200,
            'height' => 800,
            'quality' => 82,
        ],
        'large' => [
            'width' => 2048,
            'height' => 2048,
            'quality' => 78,
        ],
        'medical' => [
            'width' => 1600,
            'height' => 1200,
            'quality' => 92,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Remote Media
    |--------------------------------------------------------------------------
    |
    | Configuration for remote media handling.
    |
    */

    'remote' => [
        'enable' => env('MEDIA_REMOTE_ENABLED', false),
        'extra_headers' => [
            'Cache-Control' => 'max-age=31536000, public',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    |
    | Enable tenant isolation for media files.
    |
    */

    'tenant_isolation' => env('MEDIA_TENANT_ISOLATION', true),

];
