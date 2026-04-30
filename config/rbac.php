<?php

declare(strict_types=1);

return [
    /**
     * RBAC Roles and their associated abilities
     */
    'roles' => [
        'super_admin' => [
            'description' => 'Super administrator - full platform access',
            'abilities' => [
                'view-tenant', 'manage-tenant', 'approve-tenant', 'reject-tenant', 'update-settings',
                'view-financials', 'process-payout', 'view-audit-log',
                'manage-team', 'view-analytics', 'export-reports',
                'manage-drivers', 'view-surge-analytics',
                'manage-masters', 'manage-consumables',
                'view-kds', 'manage-menu',
                'manage-rooms', 'manage-guests',
                'view-inventory', 'manage-inventory', 'import-inventory',
                'view-payments', 'process-refund', 'view-fraud-score',
                'view-forecast', 'manage-recommendations', 'view-fraud-ml',
                'access-admin-panel', 'manage-users', 'view-users', 'ban-users',
            ],
        ],

        'support_agent' => [
            'description' => 'Support agent - limited admin access',
            'abilities' => [
                'view-tenant', 'view-financials', 'view-audit-log',
                'view-analytics', 'export-reports',
                'view-payments', 'view-fraud-score',
                'view-forecast', 'view-fraud-ml',
                'view-users', 'access-admin-panel',
            ],
        ],

        'admin' => [
            'description' => 'Full platform access',
            'abilities' => [
                'view-tenant', 'manage-tenant', 'update-settings',
                'view-financials', 'process-payout', 'view-audit-log',
                'manage-team', 'view-analytics', 'export-reports',
                'manage-drivers', 'view-surge-analytics',
                'manage-masters', 'manage-consumables',
                'view-kds', 'manage-menu',
                'manage-rooms', 'manage-guests',
                'view-inventory', 'manage-inventory', 'import-inventory',
                'view-payments', 'process-refund', 'view-fraud-score',
                'view-forecast', 'manage-recommendations', 'view-fraud-ml',
                'access-admin-panel',
            ],
        ],

        'business_owner' => [
            'description' => 'Business owner - full business access',
            'abilities' => [
                'view-tenant', 'manage-tenant', 'update-settings',
                'view-financials', 'process-payout', 'view-audit-log',
                'manage-team', 'invite-team', 'view-analytics', 'export-reports',
                'manage-drivers', 'view-surge-analytics',
                'manage-masters', 'manage-consumables',
                'manage-menu', 'manage-rooms',
                'view-inventory', 'manage-inventory', 'import-inventory',
                'view-payments', 'process-refund',
                'view-forecast', 'manage-recommendations',
                'access-business-panel',
            ],
        ],

        'manager' => [
            'description' => 'Department/team manager',
            'abilities' => [
                'view-tenant', 'view-financials', 'view-audit-log',
                'view-analytics', 'export-reports',
                'manage-drivers', 'view-surge-analytics',
                'manage-masters', 'manage-consumables',
                'view-kds', 'manage-menu',
                'manage-rooms', 'manage-guests',
                'view-inventory', 'manage-inventory', 'import-inventory',
                'view-payments', 'process-refund',
                'view-forecast', 'manage-recommendations',
                'access-employee-panel', 'invite-team',
            ],
        ],

        'accountant' => [
            'description' => 'Finance & accounting specialist',
            'abilities' => [
                'view-tenant', 'view-financials', 'view-audit-log',
                'view-analytics', 'export-reports',
                'view-inventory', 'import-inventory',
                'view-payments', 'process-refund', 'view-fraud-score',
                'view-forecast', 'view-fraud-ml',
                'access-employee-panel',
            ],
        ],

        'employee' => [
            'description' => 'Regular employee/staff member',
            'abilities' => [
                'view-tenant', 'view-analytics',
                'manage-consumables', 'view-kds',
                'manage-guests', 'view-inventory',
                'manage-inventory',
                'access-employee-panel',
            ],
        ],

        'manager_taxi' => [
            'description' => 'Taxi dispatch manager',
            'abilities' => [
                'view-tenant', 'manage-drivers',
                'view-surge-analytics', 'view-analytics',
                'access-employee-panel',
            ],
        ],

        'manager_beauty' => [
            'description' => 'Beauty salon manager',
            'abilities' => [
                'view-tenant', 'manage-masters',
                'manage-consumables', 'manage-menu',
                'view-inventory', 'manage-inventory',
                'view-analytics', 'access-employee-panel',
            ],
        ],

        'manager_restaurant' => [
            'description' => 'Restaurant manager',
            'abilities' => [
                'view-tenant', 'view-kds', 'manage-menu',
                'manage-guests', 'view-inventory',
                'manage-inventory', 'view-analytics',
                'access-employee-panel',
            ],
        ],

        'manager_hotel' => [
            'description' => 'Hotel manager',
            'abilities' => [
                'view-tenant', 'manage-rooms',
                'manage-guests', 'view-inventory',
                'view-analytics', 'access-employee-panel',
            ],
        ],

        'customer' => [
            'description' => 'End customer/user',
            'abilities' => [
                'manage-devices', 'enable-2fa',
                // Customers have minimal system access
                // They interact primarily through API/public endpoints
            ],
        ],

        'guest' => [
            'description' => 'Guest user (no account)',
            'abilities' => [
                // Guests can only browse publicly available items
            ],
        ],
    ],

    /**
     * Role hierarchy (parent -> children)
     * Admin inherits all abilities
     */
    'hierarchy' => [
        'admin' => ['business_owner', 'manager', 'accountant', 'employee'],
        'business_owner' => ['manager', 'accountant'],
        'manager' => ['employee'],
    ],

    /**
     * Default role for new users
     */
    'default_role' => 'customer',

    /**
     * Reserved roles (cannot be deleted)
     */
    'reserved_roles' => ['admin', 'business_owner', 'customer'],

    /**
     * Vertical-specific role assignments
     */
    'vertical_roles' => [
        'Auto' => ['manager_taxi'],
        'Beauty' => ['manager_beauty'],
        'Food' => ['manager_restaurant'],
        'Hotels' => ['manager_hotel'],
    ],

    /**
     * Permission cache TTL (minutes)
     */
    'cache_ttl' => 60,

    /**
     * Log permission checks
     */
    'log_checks' => env('APP_DEBUG', false),

    /**
     * Auth-specific permissions
     */
    'auth_permissions' => [
        'manage-users' => 'Manage user accounts',
        'view-users' => 'View user accounts',
        'ban-users' => 'Ban/unban users',
        'approve-tenant' => 'Approve tenant verification',
        'reject-tenant' => 'Reject tenant verification',
        'invite-team' => 'Invite team members to tenant',
        'manage-devices' => 'Manage own devices',
        'enable-2fa' => 'Enable/disable 2FA',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Limits (Zero-Duplicate Isolation)
    |--------------------------------------------------------------------------
    |
    | Maximum number of roles per user and per tenant to prevent privilege
    | escalation. These limits are enforced by RoleIsolationService.
    |
    */

    'limits' => [
        'max_roles_per_user' => env('RBAC_MAX_ROLES_PER_USER', 5),
        'max_owners_per_tenant' => env('RBAC_MAX_OWNERS_PER_TENANT', 3),
        'max_staff_per_tenant' => env('RBAC_MAX_STAFF_PER_TENANT', 10),
        'enforce_limits' => env('RBAC_ENFORCE_LIMITS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile Isolation
    |--------------------------------------------------------------------------
    |
    | Enforce strict separation between client (B2C) and business (B2B) profiles.
    | One email/phone = one profile type (client OR business).
    |
    */

    'isolation' => [
        'enabled' => env('RBAC_ISOLATION_ENABLED', true),
        'allow_profile_transition' => env('RBAC_ALLOW_PROFILE_TRANSITION', false),
        'transition_requires_kyb' => env('RBAC_TRANSITION_REQUIRES_KYB', true),
    ],
];
