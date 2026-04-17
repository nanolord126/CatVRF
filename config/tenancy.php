<?php

declare(strict_types=1);

/**
 * CatVRF 2026 — Multi-Tenancy Configuration.
 *
 * Based on stancl/tenancy package.
 * Each tenant has isolated database + storage + cache.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    */
    'tenant_model' => \App\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    | Domains that should not be treated as tenant domains.
    */
    'central_domains' => [
        env('CENTRAL_DOMAIN', 'catvrf.ru'),
        env('CENTRAL_ADMIN_DOMAIN', 'admin.catvrf.ru'),
        'localhost',
        '127.0.0.1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'prefix' => 'tenant_',
        'suffix' => '',
        'template_connection' => null,
        'separate_by' => 'database', // 'database' or 'schema'
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'tag_base' => 'tenant_',
        'prefix_base' => 'tenant_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'root_override' => true,
        'suffix_base' => 'tenant_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Identification
    |--------------------------------------------------------------------------
    | How tenants are resolved from incoming requests.
    */
    'identification' => [
        'resolvers' => [
            'domain'    => true,
            'subdomain' => true,
            'path'      => true,
            // SECURITY: Header resolver MUST be disabled in production.
            // Enable only in trusted internal networks behind auth:sanctum + rate-limit.
            // Set TENANT_HEADER_RESOLVER=false in production .env
            'header'      => env('TENANT_HEADER_RESOLVER', false),
            'header_name' => 'X-Tenant-ID',
        ],
        // SECURITY: early_identification must be false when header resolver is on
        'early_identification' => env('TENANT_HEADER_RESOLVER', false) ? false : true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bootstrappers
    |--------------------------------------------------------------------------
    | Tasks that should be performed when a tenant is identified.
    */
    'bootstrappers' => [
        \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        'user_impersonation' => false,
        'tenant_config' => true,
        'cross_tenant_access' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Scoping
    |--------------------------------------------------------------------------
    | Applied to all models with tenant_id column.
    */
    'global_scoping' => [
        'enabled' => true,
        'column' => 'tenant_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeding
    |--------------------------------------------------------------------------
    */
    'seeding' => [
        'shared_seeder' => \Database\Seeders\DatabaseSeeder::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => '',
        'middleware' => ['tenant', 'auth:sanctum'],
    ],
];
