<?php declare(strict_types=1);

/**
 * Feature Drift Detection Configuration
 * 
 * Mapping of critical features for each vertical and their thresholds.
 * Used by FeatureDriftDetectorTrait to automatically detect drift.
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Default thresholds for all verticals
    |--------------------------------------------------------------------------
    */
    'default_thresholds' => [
        'psi_critical' => 0.25,
        'psi_moderate' => 0.1,
        'ks_alpha' => 0.05,
        'js_critical' => 0.3,
        'js_moderate' => 0.1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Vertical-specific critical features
    |--------------------------------------------------------------------------
    | Each vertical can have its own set of features to monitor for drift.
    | Features should be extracted from the vertical's data and passed to
    | the drift detector.
    */
    'verticals' => [
        'medical' => [
            'features' => [
                'ai_diagnosis_frequency',
                'health_score',
                'emergency_event_rate',
                'quota_usage_ratio',
                'appointment_cancellation_rate',
                'prescription_accuracy_score',
            ],
            'thresholds' => [
                'psi_critical' => 0.2, // Stricter for medical
                'psi_moderate' => 0.08,
                'ks_alpha' => 0.03,
            ],
            'enabled' => true,
        ],

        'food' => [
            'features' => [
                'order_frequency',
                'average_order_value',
                'delivery_time_minutes',
                'restaurant_rating_avg',
                'menu_item_popularity_score',
            ],
            'thresholds' => [
                'psi_critical' => 0.25,
                'psi_moderate' => 0.1,
            ],
            'enabled' => true,
        ],

        'beauty' => [
            'features' => [
                'service_booking_frequency',
                'master_rating_avg',
                'service_duration_minutes',
                'customer_retention_rate',
                'peak_hour_booking_ratio',
            ],
            'enabled' => true,
        ],

        'fashion' => [
            'features' => [
                'product_view_frequency',
                'add_to_cart_rate',
                'average_order_value',
                'return_rate',
                'size_distribution_score',
            ],
            'enabled' => true,
        ],

        'travel' => [
            'features' => [
                'booking_frequency',
                'average_trip_duration_days',
                'destination_popularity_score',
                'seasonal_demand_index',
                'cancellation_rate',
            ],
            'enabled' => true,
        ],

        'auto' => [
            'features' => [
                'service_request_frequency',
                'diagnostic_accuracy_score',
                'repair_duration_hours',
                'parts_cost_ratio',
                'customer_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'electronics' => [
            'features' => [
                'product_view_frequency',
                'warranty_claim_rate',
                'review_rating_avg',
                'price_sensitivity_score',
                'category_preference_score',
            ],
            'enabled' => true,
        ],

        'fitness' => [
            'features' => [
                'workout_frequency',
                'session_duration_minutes',
                'trainer_rating_avg',
                'goal_completion_rate',
                'peak_hour_attendance_ratio',
            ],
            'enabled' => true,
        ],

        'sports' => [
            'features' => [
                'event_booking_frequency',
                'live_stream_viewership',
                'merchandise_sales_ratio',
                'team_engagement_score',
                'seasonal_participation_index',
            ],
            'enabled' => true,
        ],

        'real_estate' => [
            'features' => [
                'property_view_frequency',
                'offer_submission_rate',
                'listing_duration_days',
                'price_per_sqm_avg',
                'neighborhood_demand_score',
            ],
            'enabled' => true,
        ],

        'hotels' => [
            'features' => [
                'booking_frequency',
                'average_stay_duration_days',
                'room_occupancy_rate',
                'guest_rating_avg',
                'seasonal_demand_index',
            ],
            'enabled' => true,
        ],

        'taxi' => [
            'features' => [
                'ride_request_frequency',
                'average_ride_distance_km',
                'wait_time_minutes',
                'driver_rating_avg',
                'surge_multiplier_avg',
            ],
            'enabled' => true,
        ],

        'pharmacy' => [
            'features' => [
                'prescription_frequency',
                'order_value_avg',
                'delivery_time_minutes',
                'medication_adherence_score',
                'repeat_customer_rate',
            ],
            'enabled' => true,
        ],

        'education' => [
            'features' => [
                'course_enrollment_frequency',
                'lesson_completion_rate',
                'quiz_score_avg',
                'engagement_time_minutes',
                'certification_rate',
            ],
            'enabled' => true,
        ],

        'freelance' => [
            'features' => [
                'proposal_submission_frequency',
                'hire_rate',
                'project_duration_days',
                'hourly_rate_avg',
                'client_rating_avg',
            ],
            'enabled' => true,
        ],

        'logistics' => [
            'features' => [
                'shipment_frequency',
                'delivery_time_hours',
                'package_weight_kg_avg',
                'tracking_update_frequency',
                'delivery_success_rate',
            ],
            'enabled' => true,
        ],

        'luxury' => [
            'features' => [
                'product_view_frequency',
                'average_order_value',
                'customer_lifetime_value',
                'brand_loyalty_score',
                'exclusive_access_rate',
            ],
            'enabled' => true,
        ],

        'insurance' => [
            'features' => [
                'policy_purchase_frequency',
                'claim_rate',
                'coverage_amount_avg',
                'premium_payment_frequency',
                'customer_retention_rate',
            ],
            'enabled' => true,
        ],

        'legal' => [
            'features' => [
                'consultation_frequency',
                'case_duration_days',
                'hourly_rate_avg',
                'client_satisfaction_score',
                'case_success_rate',
            ],
            'enabled' => true,
        ],

        'payment' => [
            'features' => [
                'transaction_frequency',
                'transaction_amount_avg',
                'payment_method_distribution',
                'failure_rate',
                'processing_time_seconds',
            ],
            'enabled' => true,
        ],

        'analytics' => [
            'features' => [
                'report_generation_frequency',
                'data_volume_processed_gb',
                'query_execution_time_seconds',
                'user_engagement_score',
                'dashboard_view_frequency',
            ],
            'enabled' => true,
        ],

        'consulting' => [
            'features' => [
                'project_request_frequency',
                'project_duration_days',
                'hourly_rate_avg',
                'client_satisfaction_score',
                'repeat_business_rate',
            ],
            'enabled' => true,
        ],

        'content' => [
            'features' => [
                'content_creation_frequency',
                'view_count_avg',
                'engagement_rate',
                'share_frequency',
                'content_lifespan_days',
            ],
            'enabled' => true,
        ],

        'event_planning' => [
            'features' => [
                'event_booking_frequency',
                'attendee_count_avg',
                'budget_per_event_avg',
                'vendor_rating_avg',
                'seasonal_demand_index',
            ],
            'enabled' => true,
        ],

        'staff' => [
            'features' => [
                'shift_assignment_frequency',
                'hours_worked_avg',
                'overtime_hours_ratio',
                'performance_score_avg',
                'absence_rate',
            ],
            'enabled' => true,
        ],

        'inventory' => [
            'features' => [
                'stock_level_avg',
                'turnover_rate',
                'reorder_frequency',
                'stockout_rate',
                'holding_cost_ratio',
            ],
            'enabled' => true,
        ],

        'tickets' => [
            'features' => [
                'ticket_sales_frequency',
                'event_attendance_rate',
                'price_category_distribution',
                'advance_booking_days_avg',
                'refund_rate',
            ],
            'enabled' => true,
        ],

        'wallet' => [
            'features' => [
                'transaction_frequency',
                'balance_avg',
                'topup_frequency',
                'spending_pattern_score',
                'currency_distribution',
            ],
            'enabled' => true,
        ],

        'pet' => [
            'features' => [
                'service_booking_frequency',
                'pet_type_distribution',
                'service_duration_minutes',
                'owner_satisfaction_score',
                'repeat_booking_rate',
            ],
            'enabled' => true,
        ],

        'wedding_planning' => [
            'features' => [
                'booking_frequency',
                'budget_per_wedding_avg',
                'vendor_count_avg',
                'guest_count_avg',
                'seasonal_demand_index',
            ],
            'enabled' => true,
        ],

        'veterinary' => [
            'features' => [
                'appointment_frequency',
                'treatment_type_distribution',
                'visit_duration_minutes',
                'pet_age_distribution',
                'revenue_per_visit_avg',
            ],
            'enabled' => true,
        ],

        'toys_and_games' => [
            'features' => [
                'product_view_frequency',
                'age_category_distribution',
                'average_order_value',
                'seasonal_demand_index',
                'review_rating_avg',
            ],
            'enabled' => true,
        ],

        'advertising' => [
            'features' => [
                'ad_impression_frequency',
                'click_through_rate',
                'conversion_rate',
                'cost_per_click_avg',
                'campaign_duration_days',
            ],
            'enabled' => true,
        ],

        'car_rental' => [
            'features' => [
                'booking_frequency',
                'rental_duration_days',
                'vehicle_category_distribution',
                'damage_claim_rate',
                'customer_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'finances' => [
            'features' => [
                'transaction_frequency',
                'account_balance_avg',
                'spending_category_distribution',
                'savings_rate',
                'investment_return_rate',
            ],
            'enabled' => true,
        ],

        'flowers' => [
            'features' => [
                'order_frequency',
                'flower_type_distribution',
                'delivery_time_minutes',
                'seasonal_demand_index',
                'customer_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'furniture' => [
            'features' => [
                'product_view_frequency',
                'category_distribution',
                'average_order_value',
                'delivery_time_days',
                'return_rate',
            ],
            'enabled' => true,
        ],

        'photography' => [
            'features' => [
                'session_booking_frequency',
                'session_duration_hours',
                'package_type_distribution',
                'client_rating_avg',
                'repeat_booking_rate',
            ],
            'enabled' => true,
        ],

        'short_term_rentals' => [
            'features' => [
                'booking_frequency',
                'stay_duration_days',
                'occupancy_rate',
                'guest_rating_avg',
                'seasonal_demand_index',
            ],
            'enabled' => true,
        ],

        'sports_nutrition' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'average_order_value',
                'category_preference_score',
                'repeat_customer_rate',
            ],
            'enabled' => true,
        ],

        'personal_development' => [
            'features' => [
                'course_enrollment_frequency',
                'completion_rate',
                'engagement_time_minutes',
                'progress_score_avg',
                'certification_rate',
            ],
            'enabled' => true,
        ],

        'home_services' => [
            'features' => [
                'service_request_frequency',
                'service_duration_hours',
                'provider_rating_avg',
                'cost_per_service_avg',
                'repeat_booking_rate',
            ],
            'enabled' => true,
        ],

        'gardening' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'seasonal_demand_index',
                'category_distribution',
                'customer_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'geo' => [
            'features' => [
                'location_query_frequency',
                'accuracy_score_avg',
                'response_time_ms',
                'feature_usage_distribution',
                'user_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'geo_logistics' => [
            'features' => [
                'route_optimization_frequency',
                'delivery_efficiency_score',
                'fuel_consumption_ratio',
                'time_saved_minutes_avg',
                'cost_savings_ratio',
            ],
            'enabled' => true,
        ],

        'grocery_and_delivery' => [
            'features' => [
                'order_frequency',
                'basket_size_avg',
                'delivery_time_minutes',
                'category_distribution',
                'repeat_customer_rate',
            ],
            'enabled' => true,
        ],

        'farm_direct' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'seasonal_availability_score',
                'product_category_distribution',
                'customer_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'meat_shops' => [
            'features' => [
                'order_frequency',
                'product_type_distribution',
                'average_order_value',
                'delivery_time_minutes',
                'customer_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'office_catering' => [
            'features' => [
                'order_frequency',
                'order_size_avg',
                'menu_type_distribution',
                'delivery_time_minutes',
                'corporate_client_retention_rate',
            ],
            'enabled' => true,
        ],

        'party_supplies' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'seasonal_demand_index',
                'category_distribution',
                'average_order_value',
            ],
            'enabled' => true,
        ],

        'confectionery' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'seasonal_demand_index',
                'product_type_distribution',
                'customer_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'construction_and_repair' => [
            'features' => [
                'service_request_frequency',
                'project_duration_days',
                'cost_per_project_avg',
                'material_cost_ratio',
                'client_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'cleaning_services' => [
            'features' => [
                'service_booking_frequency',
                'service_duration_hours',
                'area_cleaned_sqm_avg',
                'provider_rating_avg',
                'repeat_booking_rate',
            ],
            'enabled' => true,
        ],

        'communication' => [
            'features' => [
                'message_frequency',
                'response_time_minutes',
                'engagement_score',
                'channel_preference_distribution',
                'satisfaction_score_avg',
            ],
            'enabled' => true,
        ],

        'books_and_literature' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'genre_distribution',
                'average_order_value',
                'review_rating_avg',
            ],
            'enabled' => true,
        ],

        'collectibles' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'category_distribution',
                'average_order_value',
                'rarity_score_avg',
            ],
            'enabled' => true,
        ],

        'hobby_and_craft' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'category_distribution',
                'seasonal_demand_index',
                'customer_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'household_goods' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'category_distribution',
                'average_order_value',
                'return_rate',
            ],
            'enabled' => true,
        ],

        'marketplace' => [
            'features' => [
                'product_view_frequency',
                'listing_frequency',
                'sale_conversion_rate',
                'average_order_value',
                'seller_rating_avg',
            ],
            'enabled' => true,
        ],

        'music_and_instruments' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'instrument_type_distribution',
                'average_order_value',
                'customer_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'vegan_products' => [
            'features' => [
                'product_view_frequency',
                'order_frequency',
                'category_distribution',
                'seasonal_demand_index',
                'customer_satisfaction_score',
            ],
            'enabled' => true,
        ],

        'art' => [
            'features' => [
                'artwork_view_frequency',
                'order_frequency',
                'style_distribution',
                'price_range_distribution',
                'artist_rating_avg',
            ],
            'enabled' => true,
        ],

        'crm' => [
            'features' => [
                'lead_generation_frequency',
                'conversion_rate',
                'customer_lifetime_value',
                'engagement_score_avg',
                'churn_rate',
            ],
            'enabled' => true,
        ],

        'delivery' => [
            'features' => [
                'delivery_frequency',
                'delivery_time_minutes',
                'success_rate',
                'customer_satisfaction_score',
                'cost_per_delivery_avg',
            ],
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global settings
    |--------------------------------------------------------------------------
    */
    'enabled' => env('FEATURE_DRIFT_ENABLED', true),
    'cache_ttl_hours' => env('FEATURE_DRIFT_CACHE_TTL', 168), // 7 days
    'auto_alert_on_high_severity' => env('FEATURE_DRIFT_AUTO_ALERT', true),
];
