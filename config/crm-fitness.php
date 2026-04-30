<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Fitness CRM Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the CatCRM Fitness module - gym and fitness club management
    |
    */

    'enabled' => env('FITNESS_CRM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Business Type Auto-Activation
    |--------------------------------------------------------------------------
    |
    | Automatically activate Fitness CRM when tenant selects "fitness" business type
    |
    */
    'auto_activate_on_business_type' => [
        'fitness',
        'gym',
        'sports_club',
        'yoga_studio',
        'crossfit',
        'pilates',
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking Settings
    |--------------------------------------------------------------------------
    */
    'booking' => [
        'min_minutes_before_booking' => 30,
        'max_minutes_ahead_booking' => 7 * 24 * 60, // 7 days
        'cancellation_deadline_hours' => 2,
        'no_show_penalty_points' => 10,
        'waitlist_enabled' => true,
        'auto_confirm_booking' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Membership Settings
    |--------------------------------------------------------------------------
    */
    'membership' => [
        'default_freeze_days' => 30,
        'max_freeze_days' => 90,
        'freeze_cooldown_days' => 7,
        'expiry_warning_days' => 7,
        'auto_renewal_enabled' => false,
        'grace_period_days' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Loyalty Points Settings
    |--------------------------------------------------------------------------
    */
    'loyalty' => [
        'points_per_attendance' => 10,
        'points_per_referral' => 100,
        'points_per_review' => 20,
        'bonus_points_program_completion' => 200,
        'point_value_rub' => 0.5, // Each point = 0.5 RUB discount
        'points_expire_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Schedule Settings
    |--------------------------------------------------------------------------
    */
    'schedule' => [
        'default_slot_duration_minutes' => 60,
        'buffer_minutes_between_slots' => 15,
        'max_daily_slots_per_trainer' => 12,
        'auto_generate_recurring' => true,
        'recurrence_patterns' => [
            'daily',
            'weekly',
            'biweekly',
            'monthly',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attendance Settings
    |--------------------------------------------------------------------------
    */
    'attendance' => [
        'auto_checkout_after_hours' => 3,
        'late_grace_minutes' => 10,
        'require_trainer_confirmation' => false,
        'auto_record_metrics' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'booking_confirmation' => true,
        'booking_reminder_hours_before' => [24, 2],
        'cancellation_notification' => true,
        'membership_expiry_warning_days' => [7, 3, 1],
        'new_program_announcement' => true,
        'trainer_schedule_change' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sales Funnel Configuration
    |--------------------------------------------------------------------------
    */
    'funnels' => [
        'main' => [
            'lead' => [
                'name' => 'Lead',
                'description' => 'New lead from website, marketplace, or call',
                'auto_create' => true,
            ],
            'consultation' => [
                'name' => 'Consultation',
                'description' => 'First consultation or trial session',
                'auto_transition' => true,
            ],
            'membership_sale' => [
                'name' => 'Membership Sale',
                'description' => 'Membership purchased',
                'requires_payment' => true,
            ],
            'booking' => [
                'name' => 'Booking',
                'description' => 'Client booked for sessions',
                'auto_transition' => true,
            ],
            'attendance' => [
                'name' => 'Attendance',
                'description' => 'Client attending sessions',
                'track_retention' => true,
            ],
            'retention' => [
                'name' => 'Retention',
                'description' => 'Membership renewal and upsells',
                'auto_create_renewal_opportunity' => true,
            ],
            'loyalty' => [
                'name' => 'Loyalty',
                'description' => 'Loyalty program and referrals',
                'auto_enroll' => true,
            ],
        ],
        'personal_training' => [
            'lead',
            'consultation',
            'pt_sale',
            'sessions',
            'retention',
        ],
        'seasonal_program' => [
            'interest',
            'enrolled',
            'active',
            'completed',
            'next_program',
        ],
        'corporate' => [
            'company_lead',
            'proposal',
            'contract_signed',
            'employee_onboarding',
            'active',
            'renewal',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation Rules
    |--------------------------------------------------------------------------
    */
    'automations' => [
        'auto_create_slots_from_trainer_schedule' => true,
        'auto_send_reminders' => true,
        'auto_deduct_membership_visits' => true,
        'auto_award_loyalty_points' => true,
        'auto_expire_memberships' => true,
        'auto_generate_reports' => true,
        'auto_handle_waitlist' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'marketplace' => [
            'enabled' => true,
            'sync_venues' => true,
            'sync_trainers' => true,
            'sync_workout_types' => true,
            'sync_schedule' => true,
        ],
        'payments' => [
            'enabled' => true,
            'auto_create_invoice' => true,
            'require_payment_for_booking' => false,
        ],
        'loyalty' => [
            'enabled' => true,
            'sync_points' => true,
        ],
        'analytics' => [
            'enabled' => true,
            'track_attendance' => true,
            'track_revenue' => true,
            'track_retention' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'dashboard' => [
            'show_occupancy_chart' => true,
            'show_revenue_chart' => true,
            'show_attendance_chart' => true,
            'show_top_trainers' => true,
            'show_upcoming_bookings' => true,
        ],
        'calendar' => [
            'default_view' => 'week',
            'enable_drag_drop' => true,
            'show_trainer_availability' => true,
            'color_code_by_workout_type' => true,
        ],
        'mobile' => [
            'qr_checkin_enabled' => true,
            'push_notifications_enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Compliance
    |--------------------------------------------------------------------------
    */
    'security' => [
        'medical_data_encryption' => true,
        'gdpr_compliant' => true,
        'audit_log_enabled' => true,
        'require_consent_for_photos' => true,
        'data_retention_days' => 2555, // 7 years
    ],
];
