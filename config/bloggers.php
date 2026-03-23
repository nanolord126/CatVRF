<?php

declare(strict_types=1);

return [
    /*
     * Bloggers Module Configuration (2026 Canon)
     * Production-ready streaming, live-commerce, NFT gifts
     */

    // WebRTC & Streaming
    'webrtc' => [
        'enabled' => (bool) env('WEBRTC_ENABLED', true),
        'ice_servers' => [
            [
                'urls' => [
                    'stun:stun.l.google.com:19302',
                    'stun:stun1.l.google.com:19302',
                ],
            ],
        ],
        'max_bitrate' => (int) env('WEBRTC_MAX_BITRATE', 2500000), // 2.5 Mbps
        'max_viewers_per_stream' => (int) env('MAX_VIEWERS_PER_STREAM', 10000),
    ],

    // Reverb (Laravel Broadcasting)
    'reverb' => [
        'enabled' => (bool) env('REVERB_ENABLED', true),
        'broadcast_driver' => env('BROADCAST_DRIVER', 'reverb'),
    ],

    // FFmpeg (recording & transcoding)
    'ffmpeg' => [
        'enabled' => (bool) env('FFMPEG_ENABLED', true),
        'binary' => env('FFMPEG_BINARY', 'ffmpeg'),
        'timeout' => (int) env('FFMPEG_TIMEOUT', 3600),
        'hls_segment_time' => (int) env('HLS_SEGMENT_TIME', 10),
        'vod_directory' => storage_path('app/streams/vod'),
    ],

    // TON Blockchain (NFT Gifts)
    'ton' => [
        'enabled' => (bool) env('TON_ENABLED', true),
        'network' => env('TON_NETWORK', 'testnet'), // testnet | mainnet
        'rpc_endpoint' => env('TON_RPC_ENDPOINT', 'https://testnet.toncenter.com/api/v2/jsonRPC'),
        'api_key' => env('TON_API_KEY', ''), // From TON Center
        'mnemonic' => env('TON_MNEMONIC', ''), // 24-word seed phrase
        'wallet_version' => env('TON_WALLET_VERSION', 'v4r2'),
        'nft_collection_address' => env('TON_NFT_COLLECTION_ADDRESS', ''), // Deployed NFT collection
        'admin_address' => env('TON_ADMIN_ADDRESS', ''), // For minting
    ],

    // Live Commerce
    'live_commerce' => [
        'enabled' => (bool) env('LIVE_COMMERCE_ENABLED', true),
        'max_pinned_products' => (int) env('MAX_PINNED_PRODUCTS', 5),
        'cart_session_ttl' => (int) env('CART_SESSION_TTL', 3600), // 1 hour
        'quick_checkout' => (bool) env('QUICK_CHECKOUT_ENABLED', true),
    ],

    // NFT Gifts
    'nft_gifts' => [
        'enabled' => (bool) env('NFT_GIFTS_ENABLED', true),
        'min_gift_price' => (int) env('MIN_GIFT_PRICE', 100), // Kopiykas
        'max_gift_price' => (int) env('MAX_GIFT_PRICE', 100000), // Kopiykas
        'gift_rate_limit' => (int) env('GIFT_RATE_LIMIT', 10), // Per 10 seconds
        'auto_mint_enabled' => (bool) env('AUTO_MINT_NFT_ENABLED', true),
        'metadata_ipfs_gateway' => env('IPFS_GATEWAY', 'https://gateway.pinata.cloud'),
    ],

    // Monetization
    'monetization' => [
        'commission_percent' => (float) env('BLOGGER_COMMISSION_PERCENT', 0.14), // 14%
        'migration_discount_percent' => (float) env('BLOGGER_MIGRATION_DISCOUNT_PERCENT', 0.10), // 10% for first 4 months
        'payout_schedule' => env('BLOGGER_PAYOUT_SCHEDULE', 'weekly'), // weekly | monthly
        'min_payout_amount' => (int) env('MIN_PAYOUT_AMOUNT', 100000), // Kopiykas (1000 RUB)
    ],

    // Verification
    'verification' => [
        'enabled' => (bool) env('BLOGGER_VERIFICATION_ENABLED', true),
        'require_inn' => (bool) env('REQUIRE_INN', true),
        'require_documents' => (bool) env('REQUIRE_DOCUMENTS', true),
        'gosuslugi_integration' => (bool) env('GOSUSLUGI_INTEGRATION', false),
        'manual_review_required' => (bool) env('MANUAL_BLOGGER_REVIEW', true),
    ],

    // Rate Limiting
    'rate_limit' => [
        'create_stream' => (int) env('RATE_LIMIT_CREATE_STREAM', 100), // per hour
        'send_gift' => (int) env('RATE_LIMIT_SEND_GIFT', 50), // per hour
        'live_commerce_add' => (int) env('RATE_LIMIT_LIVE_COMMERCE_ADD', 100), // per hour
        'chat_message' => (int) env('RATE_LIMIT_CHAT_MESSAGE', 10), // per minute
    ],

    // Security
    'security' => [
        'enable_content_moderation' => (bool) env('ENABLE_CONTENT_MODERATION', true),
        'enable_chat_moderation' => (bool) env('ENABLE_CHAT_MODERATION', true),
        'enable_gift_captcha' => (bool) env('ENABLE_GIFT_CAPTCHA', true),
        'redis_lock_timeout' => (int) env('REDIS_LOCK_TIMEOUT', 30), // seconds
    ],

    // Logging
    'logging' => [
        'channel' => 'bloggers',
        'audit_channel' => 'audit',
    ],
];
