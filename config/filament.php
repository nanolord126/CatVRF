<?php

declare(strict_types=1);

/**
 * CatVRF 2026 — Filament Configuration.
 *
 * Global Filament settings shared across all panels
 * (Admin, Tenant, B2B, CRM, Emergency).
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    */
    'broadcasting' => [
        'echo' => [
            'broadcaster' => 'reverb',
            'key' => env('VITE_REVERB_APP_KEY'),
            'cluster' => env('VITE_REVERB_APP_CLUSTER'),
            'wsHost' => env('VITE_REVERB_HOST'),
            'wsPort' => env('VITE_REVERB_PORT'),
            'wssPort' => env('VITE_REVERB_PORT'),
            'forceTLS' => env('VITE_REVERB_SCHEME', 'https') === 'https',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */
    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Assets Path
    |--------------------------------------------------------------------------
    */
    'assets_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Cache Path
    |--------------------------------------------------------------------------
    */
    'cache_path' => base_path('bootstrap/cache/filament'),

    /*
    |--------------------------------------------------------------------------
    | Livewire Loading Delay
    |--------------------------------------------------------------------------
    */
    'livewire_loading_delay' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Dark Mode
    |--------------------------------------------------------------------------
    */
    'dark_mode' => [
        'enabled' => true,
        'default' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    */
    'layout' => [
        'sidebar' => [
            'is_collapsible_on_desktop' => true,
            'width' => null,
            'collapsed_width' => null,
        ],
        'max_content_width' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Search
    |--------------------------------------------------------------------------
    */
    'global_search' => [
        'debounce' => 500,
        'key_bindings' => ['command+k', 'ctrl+k'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Panels
    |--------------------------------------------------------------------------
    | Registered panel providers. Each panel has its own provider class.
    */
    'panels' => [
        // Temporarily disabled for seeder
        /*
        'admin' => [
            'provider' => \App\Providers\Filament\AdminPanelProvider::class,
            'path' => 'admin',
            'domain' => env('FILAMENT_ADMIN_DOMAIN'),
        ],
        'tenant' => [
            'provider' => \App\Providers\Filament\TenantPanelProvider::class,
            'path' => 'tenant',
            'domain' => env('FILAMENT_TENANT_DOMAIN'),
        ],
        'b2b' => [
            'provider' => \App\Providers\Filament\B2BPanelProvider::class,
            'path' => 'b2b',
            'domain' => env('FILAMENT_B2B_DOMAIN'),
        ],
        'crm' => [
            'provider' => \App\Providers\Filament\CRMPanelProvider::class,
            'path' => 'crm',
            'domain' => env('FILAMENT_CRM_DOMAIN'),
        ],
        'emergency' => [
            'provider' => \App\Providers\Filament\EmergencyPanelProvider::class,
            'path' => 'emergency',
            'domain' => env('FILAMENT_EMERGENCY_DOMAIN'),
        ],
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Date/Time Format
    |--------------------------------------------------------------------------
    */
    'date_format' => 'd.m.Y',
    'time_format' => 'H:i',
    'datetime_format' => 'd.m.Y H:i',

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'default_paginator' => 25,
    'paginator_options' => [10, 25, 50, 100],
];
