<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Permissions Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines the RBAC matrix for CatVRF marketplace.
    | Permissions are organized by resource and action.
    |
    */

    'roles' => [
        'super_admin' => 'Super Administrator',
        'tenant_owner' => 'Tenant Owner',
        'tenant_manager' => 'Tenant Manager',
        'tenant_employee' => 'Tenant Employee',
        'tenant_accountant' => 'Tenant Accountant',
        'b2b_manager' => 'B2B Manager',
        'courier' => 'Courier',
        'customer' => 'Customer',
        'support_agent' => 'Support Agent',
    ],

    'permissions' => [
        // User Management
        'users.view' => ['super_admin', 'support_agent', 'tenant_owner', 'tenant_manager'],
        'users.create' => ['super_admin', 'tenant_owner'],
        'users.update' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'users.delete' => ['super_admin', 'tenant_owner'],
        'users.impersonate' => ['super_admin'],

        // Tenant Management
        'tenants.view' => ['super_admin', 'support_agent'],
        'tenants.create' => ['super_admin'],
        'tenants.update' => ['super_admin', 'tenant_owner'],
        'tenants.delete' => ['super_admin'],
        'tenants.verify' => ['super_admin'],
        'tenants.approve' => ['super_admin'],
        'tenants.reject' => ['super_admin'],
        'tenants.suspend' => ['super_admin'],

        // Orders
        'orders.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee', 'customer'],
        'orders.create' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee', 'customer'],
        'orders.update' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee'],
        'orders.delete' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'orders.cancel' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee', 'customer'],
        'orders.refund' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_accountant'],

        // Payments
        'payments.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_accountant', 'customer'],
        'payments.create' => ['super_admin', 'tenant_manager', 'tenant_employee', 'customer'],
        'payments.process' => ['super_admin', 'tenant_manager', 'tenant_accountant'],
        'payments.refund' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_accountant'],
        'payments.reconcile' => ['super_admin', 'tenant_owner', 'tenant_accountant'],

        // Wallets
        'wallets.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_accountant', 'customer'],
        'wallets.deposit' => ['super_admin', 'tenant_manager', 'tenant_accountant', 'customer'],
        'wallets.withdraw' => ['super_admin', 'tenant_manager', 'tenant_accountant', 'customer'],
        'wallets.transfer' => ['super_admin', 'tenant_manager', 'tenant_accountant', 'customer'],

        // Products/Services
        'products.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee', 'customer'],
        'products.create' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee'],
        'products.update' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee'],
        'products.delete' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'products.publish' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'products.unpublish' => ['super_admin', 'tenant_owner', 'tenant_manager'],

        // Inventory
        'inventory.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee'],
        'inventory.update' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee'],
        'inventory.adjust' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee'],

        // Customers
        'customers.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_employee'],
        'customers.create' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'customers.update' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'customers.delete' => ['super_admin', 'tenant_owner'],
        'customers.export' => ['super_admin', 'tenant_owner', 'tenant_manager'],

        // Analytics
        'analytics.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_accountant'],
        'analytics.export' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'analytics.advanced' => ['super_admin', 'tenant_owner'],

        // Reports
        'reports.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_accountant'],
        'reports.generate' => ['super_admin', 'tenant_owner', 'tenant_manager', 'tenant_accountant'],
        'reports.export' => ['super_admin', 'tenant_owner', 'tenant_manager'],

        // Settings
        'settings.view' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'settings.update' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'settings.advanced' => ['super_admin'],

        // Team Management
        'team.view' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'team.invite' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'team.remove' => ['super_admin', 'tenant_owner'],
        'team.update_role' => ['super_admin', 'tenant_owner'],

        // API Keys
        'api_keys.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'b2b_manager'],
        'api_keys.create' => ['super_admin', 'tenant_owner', 'tenant_manager', 'b2b_manager'],
        'api_keys.delete' => ['super_admin', 'tenant_owner', 'tenant_manager', 'b2b_manager'],
        'api_keys.revoke' => ['super_admin', 'tenant_owner', 'tenant_manager', 'b2b_manager'],

        // Webhooks
        'webhooks.view' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'webhooks.create' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'webhooks.update' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'webhooks.delete' => ['super_admin', 'tenant_owner', 'tenant_manager'],

        // Courier Operations
        'courier.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'courier'],
        'courier.assign' => ['super_admin', 'tenant_owner', 'tenant_manager'],
        'courier.update_status' => ['super_admin', 'tenant_manager', 'courier'],
        'courier.manage' => ['super_admin', 'tenant_owner', 'tenant_manager'],

        // Medical Specific
        'medical.patients.view' => ['super_admin', 'tenant_owner', 'tenant_manager', 'medical_provider'],
        'medical.patients.create' => ['super_admin', 'tenant_owner', 'tenant_manager', 'medical_provider'],
        'medical.patients.update' => ['super_admin', 'tenant_owner', 'tenant_manager', 'medical_provider'],
        'medical.diagnoses.create' => ['super_admin', 'tenant_owner', 'tenant_manager', 'medical_provider'],
        'medical.prescriptions.create' => ['super_admin', 'tenant_owner', 'tenant_manager', 'medical_provider'],
        'medical.emergency.handle' => ['super_admin', 'tenant_owner', 'tenant_manager', 'medical_provider'],

        // Fraud Detection
        'fraud.view' => ['super_admin', 'support_agent'],
        'fraud.investigate' => ['super_admin', 'support_agent'],
        'fraud.block' => ['super_admin'],
        'fraud.unblock' => ['super_admin'],

        // System
        'system.logs.view' => ['super_admin'],
        'system.monitoring.view' => ['super_admin'],
        'system.config.update' => ['super_admin'],
        'system.maintenance' => ['super_admin'],
    ],

    // Vertical-specific permissions mapping
    'vertical_permissions' => [
        'medical' => ['medical.patients.*', 'medical.diagnoses.*', 'medical.prescriptions.*', 'medical.emergency.*'],
        'beauty' => ['products.*', 'orders.*', 'inventory.*'],
        'food' => ['products.*', 'orders.*', 'inventory.*'],
        'real-estate' => ['products.*', 'orders.*', 'customers.*'],
        'fashion' => ['products.*', 'orders.*', 'inventory.*'],
        'travel' => ['products.*', 'orders.*', 'customers.*'],
        'auto' => ['products.*', 'orders.*', 'inventory.*'],
        'hotels' => ['products.*', 'orders.*', 'inventory.*'],
        'electronics' => ['products.*', 'orders.*', 'inventory.*'],
        'fitness' => ['products.*', 'orders.*', 'customers.*'],
        'sports' => ['products.*', 'orders.*', 'customers.*'],
        'luxury' => ['products.*', 'orders.*', 'customers.*'],
        'insurance' => ['products.*', 'orders.*', 'customers.*'],
        'legal' => ['products.*', 'orders.*', 'customers.*'],
        'logistics' => ['courier.*', 'orders.*'],
        'education' => ['products.*', 'orders.*', 'customers.*'],
        'crm' => ['customers.*', 'team.*', 'analytics.*'],
        'delivery' => ['courier.*', 'orders.*'],
        'payment' => ['payments.*', 'wallets.*'],
        'analytics' => ['analytics.*', 'reports.*'],
    ],
];
