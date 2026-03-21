<?php declare(strict_types=1);

return [
    /**
     * RBAC Roles and their associated abilities
     */
    'roles' => [
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
                'manage-team', 'view-analytics', 'export-reports',
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
                'access-employee-panel',
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
];
