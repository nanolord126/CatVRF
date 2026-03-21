<?php declare(strict_types=1);

return [
    // 3D Rendering Configuration
    '3d' => [
        'enabled' => env('3D_ENABLED', true),
        'default_renderer' => env('3D_RENDERER', 'three'),  // three, babylon
        'default_canvas_width' => 1920,
        'default_canvas_height' => 1080,
        'antialias' => true,
        'alpha' => true,
        'precision' => 'highp',

        // Lighting
        'lighting' => [
            'ambient' => [
                'color' => 0xffffff,
                'intensity' => 0.6,
            ],
            'directional' => [
                'color' => 0xffffff,
                'intensity' => 0.8,
                'position' => [5, 10, 5],
            ],
        ],

        // Camera
        'camera' => [
            'fov' => 75,
            'near' => 0.1,
            'far' => 1000,
            'default_position' => [0, 1.5, 2],
            'default_target' => [0, 0.5, 0],
        ],

        // AR Settings
        'ar' => [
            'enabled' => env('AR_ENABLED', true),
            'library' => env('AR_LIBRARY', 'ar.js'),  // ar.js, babylon-ar
            'camera_access' => true,
            'device_orientation' => true,
        ],

        // Model Storage
        'storage' => [
            'disk' => env('3D_STORAGE_DISK', 'public'),
            'path' => '3d-models',
            'max_file_size' => 100 * 1024 * 1024,  // 100MB
            'allowed_formats' => ['glb', 'gltf', 'obj', 'fbx', 'usdz'],
        ],

        // Thumbnails
        'thumbnails' => [
            'enabled' => true,
            'size' => 512,
            'format' => 'png',
            'cache_ttl' => 86400 * 30,  // 30 days
        ],

        // Performance
        'performance' => [
            'enable_shadows' => true,
            'enable_reflection' => false,
            'lod_enabled' => true,
            'lod_distances' => [10, 50, 100],
            'max_textures' => 16,
            'texture_compression' => 'bc1',
        ],

        // Verticals with 3D Support
        'verticals' => [
            'Auto' => [
                'types' => ['car', 'motorcycle', 'truck'],
                'default_model' => 'generic-car',
                'ar_enabled' => true,
            ],
            'Beauty' => [
                'types' => ['salon', 'master'],
                'default_model' => 'generic-salon',
                'ar_enabled' => false,
            ],
            'Furniture' => [
                'types' => ['sofa', 'chair', 'table', 'bed'],
                'default_model' => 'generic-furniture',
                'ar_enabled' => true,
            ],
            'Hotels' => [
                'types' => ['room', 'suite'],
                'default_model' => 'generic-room',
                'ar_enabled' => true,
            ],
            'Jewelry' => [
                'types' => ['ring', 'necklace', 'bracelet', 'earring'],
                'default_model' => 'generic-jewelry',
                'ar_enabled' => true,
                'precision' => 'high',
            ],
            'RealEstate' => [
                'types' => ['apartment', 'house', 'commercial'],
                'default_model' => 'generic-property',
                'ar_enabled' => true,
            ],
            'Electronics' => [
                'types' => ['phone', 'laptop', 'tablet', 'watch'],
                'default_model' => 'generic-device',
                'ar_enabled' => true,
            ],
        ],

        // Default Materials
        'materials' => [
            'standard' => [
                'color' => 0xcccccc,
                'metalness' => 0.5,
                'roughness' => 0.5,
            ],
            'glossy' => [
                'color' => 0xffffff,
                'metalness' => 0.8,
                'roughness' => 0.2,
            ],
            'matte' => [
                'color' => 0x999999,
                'metalness' => 0.0,
                'roughness' => 0.8,
            ],
        ],

        // Animation
        'animation' => [
            'auto_rotate' => true,
            'auto_rotate_speed' => 0.005,
            'transition_duration' => 500,  // ms
            'easing' => 'easeInOutCubic',
        ],

        // Controls
        'controls' => [
            'enable_rotation' => true,
            'enable_zoom' => true,
            'enable_pan' => true,
            'auto_rotate' => true,
            'mouse_sensitivity' => 1.0,
            'touch_sensitivity' => 1.0,
        ],

        // API
        'api' => [
            'rate_limit' => 1000,  // requests per hour
            'auth_required' => true,
            'cache_ttl' => 3600,  // 1 hour
        ],
    ],
];
