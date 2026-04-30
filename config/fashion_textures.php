<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Stable Diffusion API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to Stable Diffusion API for texture generation
    |
    */

    'sd_api_url' => env('STABLE_DIFFUSION_API_URL', 'http://localhost:7860'),
    'sd_api_key' => env('STABLE_DIFFUSION_API_KEY', ''),
    'sd_timeout' => env('STABLE_DIFFUSION_TIMEOUT', 600),

    /*
    |--------------------------------------------------------------------------
    | ControlNet Configuration
    |--------------------------------------------------------------------------
    |
    | ControlNet models and weights for texture generation
    |
    */

    'controlnet' => [
        'enabled' => env('CONTROLNET_ENABLED', true),
        'models' => [
            'canny' => [
                'model' => env('CONTROLNET_CANNY_MODEL', 'control_v11p_sd15_canny'),
                'weight' => (float) env('CONTROLNET_CANNY_WEIGHT', 0.85),
                'guidance_start' => (float) env('CONTROLNET_CANNY_GUIDANCE_START', 0.0),
                'guidance_end' => (float) env('CONTROLNET_CANNY_GUIDANCE_END', 1.0),
            ],
            'depth' => [
                'model' => env('CONTROLNET_DEPTH_MODEL', 'control_v11f1p_sd15_depth'),
                'weight' => (float) env('CONTROLNET_DEPTH_WEIGHT', 0.70),
                'guidance_start' => (float) env('CONTROLNET_DEPTH_GUIDANCE_START', 0.0),
                'guidance_end' => (float) env('CONTROLNET_DEPTH_GUIDANCE_END', 1.0),
            ],
            'openpose' => [
                'model' => env('CONTROLNET_OPENPOSE_MODEL', 'control_v11p_sd15_openpose'),
                'weight' => (float) env('CONTROLNET_OPENPOSE_WEIGHT', 0.70),
                'guidance_start' => (float) env('CONTROLNET_OPENPOSE_GUIDANCE_START', 0.0),
                'guidance_end' => (float) env('CONTROLNET_OPENPOSE_GUIDANCE_END', 1.0),
            ],
            'normal' => [
                'model' => env('CONTROLNET_NORMAL_MODEL', 'control_v11p_sd15_normalbae'),
                'weight' => (float) env('CONTROLNET_NORMAL_WEIGHT', 0.65),
                'guidance_start' => (float) env('CONTROLNET_NORMAL_GUIDANCE_START', 0.0),
                'guidance_end' => (float) env('CONTROLNET_NORMAL_GUIDANCE_END', 1.0),
                'optional' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP-Adapter Configuration
    |--------------------------------------------------------------------------
    |
    | IP-Adapter settings for preserving exact color and style
    |
    */

    'ip_adapter' => [
        'enabled' => env('IP_ADAPTER_ENABLED', true),
        'model' => env('IP_ADAPTER_MODEL', 'ip-adapter_sd15'),
        'weight' => (float) env('IP_ADAPTER_WEIGHT', 0.8),
    ],

    /*
    |--------------------------------------------------------------------------
    | LoRA Configuration
    |--------------------------------------------------------------------------
    |
    | LoRA models and weights for different material types
    |
    */

    'loras' => [
        'enabled' => env('LORA_ENABLED', true),
        'base_path' => env('LORA_BASE_PATH', storage_path('app/loras')),
        'default_strength' => (float) env('LORA_DEFAULT_STRENGTH', 0.75),
        'material_specific' => [
            'fabric' => [
                'FashionRealism_v2.safetensors' => 0.78,
                'FabricTextile.safetensors' => 0.72,
            ],
            'leather' => [
                'LeatherMaterials.safetensors' => 0.82,
                'FashionRealism_v2.safetensors' => 0.78,
                'GarmentConstruction.safetensors' => 0.68,
            ],
            'denim' => [
                'FabricTextile.safetensors' => 0.85,
                'FashionRealism_v2.safetensors' => 0.78,
                'DenimSpecialized.safetensors' => 0.75,
            ],
            'knitwear' => [
                'FabricTextile.safetensors' => 0.78,
                'KnitwearSpecialized.safetensors' => 0.78,
                'FashionRealism_v2.safetensors' => 0.75,
            ],
            'silk' => [
                'LuxuryFabrics.safetensors' => 0.80,
                'FashionRealism_v2.safetensors' => 0.78,
            ],
            'synthetic' => [
                'TechnicalFabric.safetensors' => 0.70,
                'FashionRealism_v2.safetensors' => 0.75,
            ],
            'wool' => [
                'FabricTextile.safetensors' => 0.75,
                'WoolTexture.safetensors' => 0.72,
                'FashionRealism_v2.safetensors' => 0.75,
            ],
            'linen' => [
                'NaturalFabrics.safetensors' => 0.70,
                'FabricTextile.safetensors' => 0.68,
            ],
            'velvet' => [
                'LuxuryFabrics.safetensors' => 0.82,
                'FashionRealism_v2.safetensors' => 0.78,
            ],
            'suede' => [
                'LeatherMaterials.safetensors' => 0.80,
                'FashionRealism_v2.safetensors' => 0.78,
            ],
            'canvas' => [
                'FabricTextile.safetensors' => 0.70,
                'NaturalFabrics.safetensors' => 0.68,
            ],
            'technical' => [
                'TechnicalFabric.safetensors' => 0.75,
                'SportswearTexture.safetensors' => 0.70,
            ],
            'mesh' => [
                'TechnicalFabric.safetensors' => 0.72,
                'MeshTexture.safetensors' => 0.70,
            ],
            'rubber' => [
                'SyntheticMaterials.safetensors' => 0.75,
                'FashionRealism_v2.safetensors' => 0.70,
            ],
            'plastic' => [
                'SyntheticMaterials.safetensors' => 0.72,
                'FashionRealism_v2.safetensors' => 0.70,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sampler Configuration
    |--------------------------------------------------------------------------
    |
    | Default sampler settings for Stable Diffusion
    |
    */

    'sampler' => [
        'default' => env('SD_SAMPLER', 'DPM++ 2M Karras'),
        'steps' => (int) env('SD_STEPS', 50),
        'cfg_scale' => (float) env('SD_CFG_SCALE', 7.5),
        'clip_skip' => (int) env('SD_CLIP_SKIP', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resolution Configuration
    |--------------------------------------------------------------------------
    |
    | Default resolution settings for texture generation
    |
    */

    'resolution' => [
        'default_width' => (int) env('TEXTURE_DEFAULT_WIDTH', 1024),
        'default_height' => (int) env('TEXTURE_DEFAULT_HEIGHT', 1024),
        'portrait_width' => (int) env('TEXTURE_PORTRAIT_WIDTH', 768),
        'portrait_height' => (int) env('TEXTURE_PORTRAIT_HEIGHT', 1024),
        'landscape_width' => (int) env('TEXTURE_LANDSCAPE_WIDTH', 1024),
        'landscape_height' => (int) env('TEXTURE_LANDSCAPE_HEIGHT', 768),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Configuration
    |--------------------------------------------------------------------------
    |
    | Default prompt templates and negative prompts
    |
    */

    'prompts' => [
        'base_template' => 'high quality product photography of {product_name}, {material_keywords}, detailed fabric weave, realistic material, studio lighting, 8k, product shot, white background',
        'negative_prompt' => 'lowres, bad anatomy, bad hands, text, error, missing fingers, extra digit, fewer digits, cropped, worst quality, low quality, normal quality, jpeg artifacts, signature, watermark, username, blurry, deformed, ugly, plastic skin, doll-like, disfigured, poorly drawn face, mutation, mutated, extra limb, ugly, poorly drawn hands, missing limb, floating limbs, disconnected limbs, malformed hands, blur, out of focus, long neck, long body, disgusting, childish, cartoon, 3d, disfigured, bad art, deformed, watermark, out of frame',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Storage settings for generated textures
    |
    */

    'storage' => [
        'disk' => env('TEXTURE_STORAGE_DISK', 'public'),
        'path' => env('TEXTURE_STORAGE_PATH', 'textures'),
        'formats' => ['png', 'jpg'],
        'max_file_size' => (int) env('TEXTURE_MAX_FILE_SIZE', 10485760), // 10MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue settings for async texture generation
    |
    */

    'queue' => [
        'connection' => env('TEXTURE_QUEUE_CONNECTION', 'redis'),
        'queue' => env('TEXTURE_QUEUE_NAME', 'texture-generation'),
        'max_tries' => (int) env('TEXTURE_QUEUE_MAX_TRIES', 3),
        'timeout' => (int) env('TEXTURE_QUEUE_TIMEOUT', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Thresholds
    |--------------------------------------------------------------------------
    |
    | Quality thresholds for texture validation
    |
    */

    'quality' => [
        'min_score' => (float) env('TEXTURE_MIN_QUALITY_SCORE', 0.6),
        'high_quality_threshold' => (float) env('TEXTURE_HIGH_QUALITY_THRESHOLD', 0.8),
        'validate_on_generation' => env('TEXTURE_VALIDATE_ON_GENERATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Retry settings for failed generations
    |
    */

    'retry' => [
        'max_attempts' => (int) env('TEXTURE_RETRY_MAX_ATTEMPTS', 3),
        'delay_seconds' => (int) env('TEXTURE_RETRY_DELAY', 30),
        'backoff_multiplier' => (float) env('TEXTURE_RETRY_BACKOFF', 2.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Monitoring and logging settings
    |
    */

    'monitoring' => [
        'log_generation_time' => env('TEXTURE_LOG_GENERATION_TIME', true),
        'log_quality_metrics' => env('TEXTURE_LOG_QUALITY_METRICS', true),
        'track_statistics' => env('TEXTURE_TRACK_STATISTICS', true),
        'alert_on_failure' => env('TEXTURE_ALERT_ON_FAILURE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flags for experimental features
    |
    */

    'features' => [
        'enable_displacement_map' => env('ENABLE_DISPLACEMENT_MAP', true),
        'enable_normal_map_enhancement' => env('ENABLE_NORMAL_MAP_ENHANCEMENT', false),
        'enable_ao_enhancement' => env('ENABLE_AO_ENHANCEMENT', false),
        'enable_multi_view_consistency' => env('ENABLE_MULTI_VIEW_CONSISTENCY', true),
        'enable_texture_upscaling' => env('ENABLE_TEXTURE_UPSCALING', false),
    ],
];
