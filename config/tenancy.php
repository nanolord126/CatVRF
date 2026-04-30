<?php

declare(strict_types=1);
use App\Models\Tenant;
use Database\Seeders\DatabaseSeeder;
use Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;

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
    'tenant_model' => Tenant::class,

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
    | Security Settings
    |--------------------------------------------------------------------------
    | Security hardening for tenant identification and access.
    */
    'security' => [
        // Signature secret for header-based tenant identification
        // Each tenant can have its own secret in meta.api_secret
        'header_signature_secret' => env('TENANT_HEADER_SIGNATURE_SECRET'),

        // IP whitelist for header-based tenant identification
        // Array of allowed IPs or CIDR ranges
        'ip_whitelist_enabled' => env('TENANT_IP_WHITELIST_ENABLED', false),
        'ip_whitelist' => array_filter(explode(',', env('TENANT_IP_WHITELIST', ''))),

        // Rate limiting for tenant resolution attempts
        'rate_limit_enabled' => env('TENANT_RATE_LIMIT_ENABLED', true),
        'rate_limit_max_attempts' => env('TENANT_RATE_LIMIT_MAX_ATTEMPTS', 100),
        'rate_limit_decay_minutes' => env('TENANT_RATE_LIMIT_DECAY_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bootstrappers
    |--------------------------------------------------------------------------
    | Tasks that should be performed when a tenant is identified.
    */
    'bootstrappers' => [
        DatabaseTenancyBootstrapper::class,
        CacheTenancyBootstrapper::class,
        FilesystemTenancyBootstrapper::class,
        QueueTenancyBootstrapper::class,
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
        'shared_seeder' => DatabaseSeeder::class,
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
