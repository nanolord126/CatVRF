<?php

return [
    'rtb' => [
        'enabled' => env('RTB_ENABLED', true),
        'timeout_ms' => env('RTB_TIMEOUT_MS', 120),
        'auction_type' => env('RTB_AUCTION_TYPE', 'second_price'), // first_price | second_price
        'default_floor_cpm_kopecks' => env('RTB_DEFAULT_FLOOR_CPM_KOPECKS', 500),
        'max_bid_kopecks' => env('RTB_MAX_BID_KOPECKS', 100000),
        'dsps' => [
            // Internal DSP (self-serve campaigns)
            0 => [
                'name' => 'Internal',
                'endpoint' => null,
                'priority' => 100,
            ],
            // External DSPs can be added here
            // 1 => ['name' => 'Yandex', 'endpoint' => env('DSP_YANDEX_ENDPOINT'), 'priority' => 90],
            // 2 => ['name' => 'VK', 'endpoint' => env('DSP_VK_ENDPOINT'), 'priority' => 85],
        ],
    ],
];
