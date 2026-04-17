<?php declare(strict_types=1);

return [
    'booking' => [
        'hold_slot_b2c_minutes' => env('REAL_ESTATE_HOLD_SLOT_B2C_MINUTES', 15),
        'hold_slot_b2b_minutes' => env('REAL_ESTATE_HOLD_SLOT_B2B_MINUTES', 60),
        'cache_ttl_seconds' => env('REAL_ESTATE_CACHE_TTL_SECONDS', 3600),
        'booking_lock_ttl' => env('REAL_ESTATE_BOOKING_LOCK_TTL', 300),
    ],

    'ai' => [
        'constructor_enabled' => env('REAL_ESTATE_AI_CONSTRUCTOR_ENABLED', true),
        'vision_provider' => env('REAL_ESTATE_AI_VISION_PROVIDER', 'openai'),
        'cache_ttl_seconds' => env('REAL_ESTATE_AI_CACHE_TTL_SECONDS', 3600),
    ],

    'fraud' => [
        'max_score_threshold' => env('REAL_ESTATE_FRAUD_MAX_SCORE', 0.7),
        'enable_ml_detection' => env('REAL_ESTATE_FRAUD_ML_ENABLED', true),
    ],

    'webrtc' => [
        'enabled' => env('REAL_ESTATE_WEBRTC_ENABLED', true),
        'room_ttl_hours' => env('REAL_ESTATE_WEBRTC_ROOM_TTL_HOURS', 2),
        'secret' => env('REAL_ESTATE_WEBRTC_SECRET'),
    ],

    'blockchain' => [
        'enabled' => env('REAL_ESTATE_BLOCKCHAIN_ENABLED', true),
        'provider' => env('REAL_ESTATE_BLOCKCHAIN_PROVIDER', 'ethereum'),
        'network' => env('REAL_ESTATE_BLOCKCHAIN_NETWORK', 'mainnet'),
    ],

    'pricing' => [
        'dynamic_pricing_enabled' => env('REAL_ESTATE_DYNAMIC_PRICING_ENABLED', true),
        'b2b_discount_percent' => env('REAL_ESTATE_B2B_DISCOUNT_PERCENT', 15),
        'peak_demand_multiplier' => env('REAL_ESTATE_PEAK_DEMAND_MULTIPLIER', 1.1),
        'flash_discount_multiplier' => env('REAL_ESTATE_FLASH_DISCOUNT_MULTIPLIER', 1.15),
    ],

    'deal_scoring' => [
        'credit_weight' => env('REAL_ESTATE_DEAL_SCORE_CREDIT_WEIGHT', 0.4),
        'legal_weight' => env('REAL_ESTATE_DEAL_SCORE_LEGAL_WEIGHT', 0.3),
        'liquidity_weight' => env('REAL_ESTATE_DEAL_SCORE_LIQUIDITY_WEIGHT', 0.3),
        'recommendation_threshold' => env('REAL_ESTATE_DEAL_SCORE_THRESHOLD', 0.7),
    ],

    'commission' => [
        'b2b_platform_percent' => env('REAL_ESTATE_COMMISSION_B2B_PLATFORM', 8),
        'b2b_agent_percent' => env('REAL_ESTATE_COMMISSION_B2B_AGENT', 3),
        'b2b_referral_percent' => env('REAL_ESTATE_COMMISSION_B2B_REFERRAL', 2),
    ],
];
