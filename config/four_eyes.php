<?php

declare(strict_types=1);

return [
    // Enable/disable four-eyes approval
    'enabled' => env('FOUR_EYES_ENABLED', true),

    // Critical operations configuration
    'critical_operations' => [
        'wallet_withdrawal' => [
            'enabled' => true,
            'thresholds' => [
                'individual' => env('FOUR_EYES_WALLET_INDIVIDUAL_THRESHOLD', 100000),
                'business' => env('FOUR_EYES_WALLET_BUSINESS_THRESHOLD', 10000),
            ],
            'approval_level' => 'high',
            'approval_window' => 3600, // 1 hour
            'required_roles' => ['finance_manager', 'admin'],
        ],
        'mass_product_edit' => [
            'enabled' => true,
            'threshold' => env('FOUR_EYES_MASS_EDIT_THRESHOLD', 100),
            'approval_level' => 'medium',
            'approval_window' => 7200, // 2 hours
            'required_roles' => ['catalog_manager', 'admin'],
        ],
        'user_role_change' => [
            'enabled' => true,
            'target_roles' => ['admin', 'manager', 'finance_manager'],
            'approval_level' => 'high',
            'approval_window' => 3600,
            'required_roles' => ['admin'],
        ],
        'payment_gateway_change' => [
            'enabled' => true,
            'always_critical' => true,
            'approval_level' => 'high',
            'approval_window' => 3600,
            'required_roles' => ['admin', 'cto'],
        ],
        'api_key_creation' => [
            'enabled' => true,
            'always_critical' => true,
            'approval_level' => 'medium',
            'approval_window' => 3600,
            'required_roles' => ['admin', 'devops_manager'],
        ],
    ],

    // Conflict detection
    'conflict_detection' => [
        'enabled' => env('FOUR_EYES_CONFLICT_DETECTION_ENABLED', true),
        'prevent_self_approval' => true,
        'prevent_same_team' => true,
        'prevent_reporting_line' => true,
        'prevent_related_business' => true,
    ],

    // Escalation
    'escalation' => [
        'enabled' => env('FOUR_EYES_ESCALATION_ENABLED', true),
        'levels' => [
            0 => ['timeout' => 3600, 'escalate_to' => 'manager'],
            1 => ['timeout' => 7200, 'escalate_to' => 'senior_manager'],
            2 => ['timeout' => 14400, 'escalate_to' => 'director'],
        ],
        'auto_approve_low_risk' => env('FOUR_EYES_AUTO_APPROVE_LOW_RISK', false),
        'auto_reject_high_risk' => env('FOUR_EYES_AUTO_REJECT_HIGH_RISK', true),
    ],

    // Notifications
    'notifications' => [
        'channels' => ['database', 'mail'],
        'slack_webhook' => env('SLACK_APPROVAL_WEBHOOK'),
    ],

    // Approval queue
    'queue' => [
        'auto_assign' => true,
        'load_balancing' => true,
        'max_approvals_per_user' => 10,
    ],
];
