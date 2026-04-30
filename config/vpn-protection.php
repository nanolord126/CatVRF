<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | VPN Protection Configuration
    |--------------------------------------------------------------------------
    |
    | Intelligent VPN/Proxy restriction system for CatVRF.
    | VPN/Proxy alone does NOT block - only when combined with fraud/ML/behavioral signals.
    |
    */

    'enabled' => env('VPN_PROTECTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Risk Factor Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for various risk factors that trigger VPN protection.
    |
    */
    'thresholds' => [
        // Fraud score threshold (0-1) - from FraudControlService
        'fraud_score' => 0.65,

        // Behavioral biometrics threshold (0-1) - from BehavioralBiometricsService
        // Score < 0.75 indicates anomaly
        'behavioral_score' => 0.75,

        // Insider threat threshold (0-1) - from InsiderThreatService
        'insider_threat_score' => 0.6,

        // Mass actions threshold - number of actions in time window
        'mass_actions' => [
            'count' => 15,
            'window_minutes' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Protection Levels
    |--------------------------------------------------------------------------
    |
    | Gradient protection based on risk level.
    |
    */
    'protection_levels' => [
        'low' => [
            'cooldown_hours' => 0,
            'block_financial' => false,
            'block_critical' => false,
            'require_passkey' => false,
            'require_liveness' => false,
            'notify_owners' => false,
        ],

        'medium' => [
            'cooldown_hours' => 0,
            'block_financial' => false,
            'block_critical' => false,
            'require_passkey' => false,
            'require_liveness' => false,
            'notify_owners' => false,
        ],

        'high' => [
            'cooldown_hours' => 24,
            'block_financial' => true,
            'block_critical' => true,
            'require_passkey' => false,
            'require_liveness' => false,
            'notify_owners' => true,
        ],

        'critical' => [
            'cooldown_hours' => 72,
            'block_financial' => true,
            'block_critical' => true,
            'require_passkey' => true,
            'require_liveness' => true,
            'notify_owners' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | High-Risk Actions
    |--------------------------------------------------------------------------
    |
    | Actions that are blocked when VPN + risk factors are detected.
    |
    */
    'high_risk_actions' => [
        'financial' => [
            'wallet.withdraw',
            'wallet.transfer',
            'wallet.payout',
            'payment.process',
            'refund.process',
        ],

        'critical_changes' => [
            'tenant.settings.update',
            'tenant.bank_details.update',
            'tenant.wallet.update',
            'tenant.contact.update',
            'kyb.submit',
            'kyb.approve',
            'staff.invite',
            'staff.promote',
            'staff.role.update',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Russian Territories Special Rules
    |--------------------------------------------------------------------------
    |
    | Special handling for Russian territories (Crimea, Sevastopol, DPR, LPR, Kherson, Zaporizhzhia).
    | VPN + claimed region from these territories but IP from different country → auto High risk.
    |
    */
    'russian_territories' => [
        'enabled' => true,
        'territories' => [
            'Crimea',
            'Crimean Federal District',
            'Sevastopol',
            'Donetsk People\'s Republic',
            'DPR',
            'Luhansk People\'s Republic',
            'LPR',
            'Kherson Oblast',
            'Kherson Region',
            'Zaporizhzhia Oblast',
            'Zaporizhzhia Region',
        ],
        'country_codes' => ['RU', 'UA'],
        'auto_high_risk_on_mismatch' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for VPN detection events.
    |
    */
    'logging' => [
        'log_all_detections' => true,
        'log_only_blocks' => false,
        'clickhouse_enabled' => env('VPN_PROTECTION_CLICKHOUSE_ENABLED', true),
        'clickhouse_table' => 'vpn_protection_events',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure notifications when protection is triggered.
    |
    */
    'notifications' => [
        'enabled' => true,
        'channels' => ['database', 'mail'],
        'notify_owners' => true,
        'notify_investors' => true,
        'notify_user' => false, // Don't notify user to avoid tipping off attackers
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for VPN detection results.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl_seconds' => 3600, // 1 hour
        'prefix' => 'vpn_protection:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cooldown Integration
    |--------------------------------------------------------------------------
    |
    | Integration with CooldownService for applying restrictions.
    |
    */
    'cooldown' => [
        'enabled' => true,
        'action_types' => [
            'financial' => 'FINANCIAL_OPERATIONS',
            'critical' => 'CRITICAL_CHANGES',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Management Integration
    |--------------------------------------------------------------------------
    |
    | Integration with UserDevice for tracking VPN risk.
    |
    */
    'device_management' => [
        'enabled' => true,
        'update_device_on_detection' => true,
        'track_applied_blocks' => true,
    ],
];
