<?php

declare(strict_types=1);

use Pest\Platform\Services\Platform;
use Pest\Plugin\Laravel;

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
|
| Here you can configure the behavior of Pest. Feel free to customize
| the configuration to fit your needs and preferences.
|
*/

$testFiles = implode(' ', array_map(fn ($file) => escapeshellarg($file), array_diff(
    glob(__DIR__.'/tests/**/*.php'),
    glob(__DIR__.'/tests/Pest.php'),
    glob(__DIR__.'/tests/TestCase.php')
)));

Platform::detect();

return [
    /*
    |--------------------------------------------------------------------------
    | Plugin Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the plugins that Pest will use.
    |
    */
    'plugins' => [
        Laravel::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Files
    |--------------------------------------------------------------------------
    |
    | Here you can define the test files that should be run by Pest.
    |
    */
    'tests' => [
        'tests/Unit',
        'tests/Feature',
        'tests/E2E',
        'tests/Chaos',
        'tests/Contract',
        'tests/Integration',
        'tests/Security',
        'tests/Load',
        'tests/Smoke',
        'tests/Sanity',
    ],

    /*
    |--------------------------------------------------------------------------
    | Source Files
    |--------------------------------------------------------------------------
    |
    | Here you can define the source files that should be used for coverage.
    |
    */
    'source' => [
        'app',
        'modules',
    ],

    /*
    |--------------------------------------------------------------------------
    | Coverage
    |--------------------------------------------------------------------------
    |
    | Here you can configure the coverage settings.
    |
    */
    'coverage' => [
        'include' => [
            'app',
            'modules',
        ],
        'exclude' => [
            'app/Domains/Archived',
            'app/Stubs',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Parallel Testing
    |--------------------------------------------------------------------------
    |
    | Here you can configure the parallel testing settings.
    |
    */
    'parallel' => [
        'processes' => null, // Auto-detect
    ],

    /*
    |--------------------------------------------------------------------------
    | Snapshots
    |--------------------------------------------------------------------------
    |
    | Here you can configure the snapshot settings.
    |
    */
    'snapshots' => [
        'directory' => 'tests/__snapshots__',
    ],

    /*
    |--------------------------------------------------------------------------
    | Print Output
    |--------------------------------------------------------------------------
    |
    | Here you can configure the print output settings.
    |
    */
    'printOutput' => false,

    /*
    |--------------------------------------------------------------------------
    | Ignore High Maintenance Tests
    |--------------------------------------------------------------------------
    |
    | Here you can configure whether to ignore high maintenance tests.
    |
    */
    'ignoreHighMaintenance' => false,
];
