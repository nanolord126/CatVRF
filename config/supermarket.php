<?php

declare(strict_types=1);

return [
    'vertical_name' => 'Supermarket',
    'enabled' => env('SUPERMARKET_ENABLED', true),
    
    'commissions' => [
        'rate' => env('SUPERMARKET_COMMISSION_RATE', 0.05),
        'min_amount' => env('SUPERMARKET_MIN_COMMISSION', 10),
    ],
    
    'returns' => [
        'default_policy' => [
            'max_days' => env('SUPERMARKET_DEFAULT_RETURN_DAYS', 3),
            'auto_approve_hours' => env('SUPERMARKET_AUTO_APPROVE_HOURS', 12),
        ],
        'cold_chain' => [
            'only_defect' => true,
            'requires_temperature' => true,
        ],
    ],
    
    'subscriptions' => [
        'min_amount' => env('SUPERMARKET_SUBSCRIPTION_MIN_AMOUNT', 1500),
        'min_items' => env('SUPERMARKET_SUBSCRIPTION_MIN_ITEMS', 8),
        'frequencies' => ['weekly', 'biweekly', 'monthly'],
        'delivery_slots' => ['morning' => '08:00-12:00', 'day' => '12:00-17:00', 'evening' => '17:00-22:00'],
    ],
    
    'age_verification' => [
        'enabled' => env('SUPERMARKET_AGE_VERIFICATION_ENABLED', true),
        'required_age' => 18,
        'methods' => ['passport', 'selfie', 'bankid', 'gosuslugi'],
        'validity_days' => env('SUPERMARKET_AGE_VERIFICATION_VALIDITY', 365),
    ],
    
    'honesty_mark' => [
        'enabled' => env('SUPERMARKET_HONESTY_MARK_ENABLED', true),
        'check_on_sale' => true,
        'withdraw_on_delivery' => true,
    ],
    
    'notifications' => [
        'pre_delivery_hours' => env('SUPERMARKET_PRE_DELIVERY_HOURS', 24),
        'channels' => ['database', 'push', 'telegram', 'whatsapp'],
    ],
    
    'queues' => [
        'default' => 'supermarket',
        'high' => 'supermarket-high',
        'low' => 'supermarket-low',
    ],
];
