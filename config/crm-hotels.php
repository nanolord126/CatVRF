<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Hotels CRM Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Hotels & HoReCa CatCRM module including funnels,
    | automations, and business rules.
    |
    */

    'funnels' => [
        'booking' => [
            'stages' => [
                'lead' => [
                    'name' => 'Lead',
                    'auto_advance' => false,
                ],
                'qualification' => [
                    'name' => 'Qualification',
                    'auto_advance' => true,
                    'advance_after_hours' => 24,
                ],
                'reservation' => [
                    'name' => 'Reservation',
                    'auto_advance' => false,
                ],
                'confirmation' => [
                    'name' => 'Confirmation',
                    'auto_advance' => false,
                ],
                'check_in' => [
                    'name' => 'Check-in',
                    'auto_advance' => false,
                ],
                'stay' => [
                    'name' => 'Stay',
                    'auto_advance' => false,
                ],
                'check_out' => [
                    'name' => 'Check-out',
                    'auto_advance' => false,
                ],
                'post_stay' => [
                    'name' => 'Post-stay',
                    'auto_advance' => true,
                    'advance_after_hours' => 48,
                ],
            ],
        ],
    ],

    'automations' => [
        'check_in' => [
            'enabled' => true,
            'actions' => [
                'update_room_status' => 'occupied',
                'create_housekeeping_task' => true,
                'send_welcome_notification' => true,
            ],
        ],
        'check_out' => [
            'enabled' => true,
            'actions' => [
                'update_room_status' => 'available',
                'mark_room_dirty' => true,
                'create_housekeeping_task' => true,
                'update_guest_stats' => true,
                'send_farewell_notification' => true,
                'generate_invoice' => true,
            ],
        ],
        'no_show' => [
            'enabled' => true,
            'actions' => [
                'cancel_booking' => true,
                'mark_room_available' => true,
                'charge_cancellation_fee' => true,
            ],
        ],
        'early_checkin' => [
            'enabled' => true,
            'rules' => [
                'available_hours_before' => 2,
                'charge_fee' => true,
                'fee_percentage' => 0.3,
            ],
        ],
        'late_checkout' => [
            'enabled' => true,
            'rules' => [
                'available_hours_after' => 2,
                'charge_fee' => true,
                'fee_percentage' => 0.5,
            ],
        ],
    ],

    'notifications' => [
        'channels' => ['email', 'telegram', 'sms'],
        'booking_confirmation' => [
            'enabled' => true,
            'channels' => ['email', 'telegram'],
        ],
        'check_in_reminder' => [
            'enabled' => true,
            'hours_before' => 24,
            'channels' => ['telegram', 'sms'],
        ],
        'check_out_reminder' => [
            'enabled' => true,
            'hours_before' => 2,
            'channels' => ['telegram', 'sms'],
        ],
        'payment_reminder' => [
            'enabled' => true,
            'hours_before' => 48,
            'channels' => ['email', 'telegram'],
        ],
        'review_request' => [
            'enabled' => true,
            'hours_after_checkout' => 24,
            'channels' => ['email'],
        ],
    ],

    'pricing' => [
        'default_currency' => 'RUB',
        'seasonal_pricing' => [
            'enabled' => true,
            'high_season_multiplier' => 1.3,
            'low_season_multiplier' => 0.8,
        ],
        'dynamic_pricing' => [
            'enabled' => true,
            'occupancy_threshold_high' => 80,
            'occupancy_threshold_low' => 30,
            'price_increase_percentage' => 20,
            'price_decrease_percentage' => 15,
        ],
    ],

    'cancellation' => [
        'policy' => [
            'free_cancellation_hours' => 24,
            'partial_refund_hours' => 72,
            'no_refund_hours' => 0,
        ],
        'fees' => [
            'first_night_percentage' => 100,
            'full_stay_percentage' => 30,
        ],
    ],

    'housekeeping' => [
        'default_cleaning_time_minutes' => 30,
        'deep_cleaning_time_minutes' => 60,
        'turn_down_time_minutes' => 15,
        'inspection_time_minutes' => 10,
        'auto_schedule_on_checkout' => true,
    ],

    'integrations' => [
        'booking_com' => [
            'enabled' => false,
            'api_key' => env('BOOKING_COM_API_KEY'),
            'hotel_id' => env('BOOKING_COM_HOTEL_ID'),
        ],
        'ostrovok' => [
            'enabled' => false,
            'api_key' => env('OSTROVOK_API_KEY'),
            'hotel_id' => env('OSTROVOK_HOTEL_ID'),
        ],
        'airbnb' => [
            'enabled' => false,
            'api_key' => env('AIRBNB_API_KEY'),
        ],
    ],
];
