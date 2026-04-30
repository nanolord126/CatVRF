<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Vertical-Specific Tender Risk Configuration
    |--------------------------------------------------------------------------
    |
    | Each vertical has specific risk patterns, typical deal sizes,
    | seasonality, and fraud indicators. This configuration
    | drives ML assessment and fraud control.
    |
    */

    'verticals' => [
        // Supermarket - high volume, low margin, frequent transactions
        1 => [
            'name' => 'Supermarket',
            'base_risk_level' => 'low',
            'typical_amount_min' => 100000,
            'typical_amount_max' => 5000000,
            'risk_factors' => [
                'high_frequency_trading' => true,
                'price_manipulation' => true,
                'spoilage_risk' => true,
            ],
            'ml_weights' => [
                'balance_ratio' => 0.3,
                'transaction_history' => 0.25,
                'vertical_experience' => 0.2,
                'payment_reliability' => 0.15,
                'geographic_risk' => 0.1,
            ],
            'fraud_indicators' => [
                'sudden_large_orders' => true,
                'new_supplier_pattern' => true,
                'price_anomaly' => true,
            ],
            'seasonality' => ['Q1' => 1.0, 'Q2' => 1.1, 'Q3' => 1.0, 'Q4' => 1.3],
        ],

        // Restaurant - perishable goods, time-sensitive
        2 => [
            'name' => 'Restaurant',
            'base_risk_level' => 'medium',
            'typical_amount_min' => 50000,
            'typical_amount_max' => 1000000,
            'risk_factors' => [
                'perishable_goods' => true,
                'cancellation_risk' => true,
                'quality_disputes' => true,
            ],
            'ml_weights' => [
                'balance_ratio' => 0.25,
                'transaction_history' => 0.3,
                'vertical_experience' => 0.25,
                'payment_reliability' => 0.15,
                'geographic_risk' => 0.05,
            ],
            'fraud_indicators' => [
                'last_minute_cancellations' => true,
                'quality_complaints' => true,
                'payment_delays' => true,
            ],
            'seasonality' => ['Q1' => 0.8, 'Q2' => 1.0, 'Q3' => 1.2, 'Q4' => 1.5],
        ],

        // BeautyMasters - service-based, reputation-dependent
        3 => [
            'name' => 'BeautyMasters',
            'base_risk_level' => 'low',
            'typical_amount_min' => 20000,
            'typical_amount_max' => 500000,
            'risk_factors' => [
                'service_quality' => true,
                'customer_satisfaction' => true,
                'no_show_risk' => true,
            ],
            'ml_weights' => [
                'balance_ratio' => 0.2,
                'transaction_history' => 0.35,
                'vertical_experience' => 0.25,
                'payment_reliability' => 0.15,
                'reputation_score' => 0.05,
            ],
            'fraud_indicators' => [
                'fake_reviews' => true,
                'service_disputes' => true,
                'payment_refunds' => true,
            ],
            'seasonality' => ['Q1' => 0.7, 'Q2' => 1.0, 'Q3' => 1.3, 'Q4' => 1.4],
        ],

        // Taxi - high volume, immediate payment, location-based
        4 => [
            'name' => 'Taxi',
            'base_risk_level' => 'medium',
            'typical_amount_min' => 500,
            'typical_amount_max' => 50000,
            'risk_factors' => [
                'driver_fraud' => true,
                'route_manipulation' => true,
                'payment_disputes' => true,
            ],
            'ml_weights' => [
                'balance_ratio' => 0.15,
                'transaction_history' => 0.4,
                'vertical_experience' => 0.2,
                'payment_reliability' => 0.2,
                'geographic_risk' => 0.05,
            ],
            'fraud_indicators' => [
                'route_deviation' => true,
                'fake_trips' => true,
                'payment_chargebacks' => true,
            ],
            'seasonality' => ['Q1' => 0.9, 'Q2' => 1.0, 'Q3' => 1.0, 'Q4' => 1.2],
        ],

        // RealEstate - high value, long contracts, legal complexity
        5 => [
            'name' => 'RealEstate',
            'base_risk_level' => 'high',
            'typical_amount_min' => 1000000,
            'typical_amount_max' => 50000000,
            'risk_factors' => [
                'legal_disputes' => true,
                'property_value_fluctuation' => true,
                'contract_breach' => true,
            ],
            'ml_weights' => [
                'balance_ratio' => 0.4,
                'transaction_history' => 0.2,
                'vertical_experience' => 0.15,
                'payment_reliability' => 0.2,
                'legal_compliance' => 0.05,
            ],
            'fraud_indicators' => [
                'fake_properties' => true,
                'title_fraud' => true,
                'payment_wire_fraud' => true,
            ],
            'seasonality' => ['Q1' => 0.8, 'Q2' => 1.2, 'Q3' => 1.1, 'Q4' => 0.9],
        ],

        // Fashion - seasonal, inventory risk, returns
        6 => [
            'name' => 'Fashion',
            'base_risk_level' => 'medium',
            'typical_amount_min' => 50000,
            'typical_amount_max' => 2000000,
            'risk_factors' => [
                'seasonal_demand' => true,
                'return_rate' => true,
                'inventory_obsolescence' => true,
            ],
            'ml_weights' => [
                'balance_ratio' => 0.25,
                'transaction_history' => 0.3,
                'vertical_experience' => 0.2,
                'payment_reliability' => 0.15,
                'seasonal_factor' => 0.1,
            ],
            'fraud_indicators' => [
                'high_return_rate' => true,
                'fake_goods' => true,
                'payment_delays' => true,
            ],
            'seasonality' => ['Q1' => 0.6, 'Q2' => 0.8, 'Q3' => 1.2, 'Q4' => 1.5],
        ],

        // Hotels - booking cancellation, overbooking risk
        7 => [
            'name' => 'Hotels',
            'base_risk_level' => 'medium',
            'typical_amount_min' => 10000,
            'typical_amount_max' => 5000000,
            'risk_factors' => [
                'cancellation_risk' => true,
                'no_show_risk' => true,
                'seasonal_demand' => true,
            ],
            'ml_weights' => [
                'balance_ratio' => 0.2,
                'transaction_history' => 0.35,
                'vertical_experience' => 0.25,
                'payment_reliability' => 0.15,
                'seasonal_factor' => 0.05,
            ],
            'fraud_indicators' => [
                'fake_bookings' => true,
                'payment_chargebacks' => true,
                'cancellation_abuse' => true,
            ],
            'seasonality' => ['Q1' => 0.7, 'Q2' => 1.1, 'Q3' => 1.3, 'Q4' => 1.0],
        ],

        // Default configuration for unknown verticals
        'default' => [
            'name' => 'Default',
            'base_risk_level' => 'medium',
            'typical_amount_min' => 100000,
            'typical_amount_max' => 1000000,
            'risk_factors' => [],
            'ml_weights' => [
                'balance_ratio' => 0.3,
                'transaction_history' => 0.3,
                'vertical_experience' => 0.2,
                'payment_reliability' => 0.2,
            ],
            'fraud_indicators' => [
                'sudden_large_orders' => true,
                'payment_delays' => true,
            ],
            'seasonality' => ['Q1' => 1.0, 'Q2' => 1.0, 'Q3' => 1.0, 'Q4' => 1.0],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Thresholds
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'credit_score' => [
            'excellent' => 90,
            'good' => 75,
            'fair' => 60,
            'poor' => 40,
        ],
        'guarantee_eligibility' => [
            'low' => 60,
            'medium' => 70,
            'high' => 80,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ML Model Configuration
    |--------------------------------------------------------------------------
    */
    'ml' => [
        'model_version' => '1.0',
        'features' => [
            'balance_ratio',
            'transaction_history',
            'vertical_experience',
            'payment_reliability',
            'geographic_risk',
            'reputation_score',
            'seasonal_factor',
            'legal_compliance',
        ],
        'fallback_strategy' => 'rule_based', // 'rule_based' or 'deny_all'
    ],
];
