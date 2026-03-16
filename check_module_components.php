<?php
declare(strict_types=1);

/**
 * MODULE COMPONENT CHECKER: Verify all required components in modules
 * 
 * Checks for:
 * - Resources (in app/Filament/Tenant/Resources)
 * - Events (in app/Events)
 * - Seeders (in database/seeders)
 * - Migrations (in database/migrations)
 * - Policies (in app/Policies)
 * - Contracts/Interfaces (in app/Contracts)
 */

const BASE_PATH = __DIR__;
const MODULES_PATH = BASE_PATH . '/modules';
const APP_PATH = BASE_PATH . '/app';

// Define module structure
$modules = ['Beauty', 'Common', 'Finances', 'GeoLogistics', 'Payments', 'Wallet'];

$report = [
    'modules' => [],
    'summary' => [
        'total_modules' => count($modules),
        'complete_modules' => 0,
        'incomplete_modules' => 0,
        'missing_components' => 0,
    ],
];

echo "🔍 Checking Module Components...\n";
echo str_repeat('=', 80) . "\n\n";

foreach ($modules as $module) {
    $modulePath = MODULES_PATH . "/$module";
    
    if (!is_dir($modulePath)) {
        echo "❌ Module not found: $module\n";
        continue;
    }
    
    echo "📦 Module: $module\n";
    
    $components = [
        'Resources' => [],
        'Events' => [],
        'Seeders' => [],
        'Migrations' => [],
        'Policies' => [],
        'Contracts' => [],
    ];
    
    // Check Filament Resources
    $resourcesPath = APP_PATH . '/Filament/Tenant/Resources';
    if (is_dir($resourcesPath)) {
        $resources = glob("$resourcesPath/*{Resource,resource}.php", GLOB_BRACE);
        foreach ($resources as $resource) {
            $content = file_get_contents($resource);
            if (stripos($content, strtolower($module)) !== false || 
                preg_match('/' . $module . '/i', basename(dirname($resource)))) {
                $components['Resources'][] = basename($resource);
            }
        }
    }
    
    // Check Events
    $eventsPath = APP_PATH . '/Events';
    if (is_dir($eventsPath)) {
        $iterator = new RecursiveDirectoryIterator($eventsPath);
        $filter = new RecursiveIteratorIterator($iterator);
        foreach ($filter as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getRealPath());
                if (stripos($content, strtolower($module)) !== false || 
                    preg_match('/' . $module . '/i', $file->getBasename())) {
                    $components['Events'][] = $file->getBasename();
                }
            }
        }
    }
    
    // Check Seeders
    $seedersPath = BASE_PATH . '/database/seeders';
    if (is_dir($seedersPath)) {
        $seeders = glob("$seedersPath/*{Seeder,seeder}.php", GLOB_BRACE);
        foreach ($seeders as $seeder) {
            if (stripos(basename($seeder), strtolower($module)) !== false) {
                $components['Seeders'][] = basename($seeder);
            }
        }
    }
    
    // Check Migrations
    $migrationsPath = BASE_PATH . '/database/migrations';
    if (is_dir($migrationsPath)) {
        $migrations = glob("$migrationsPath/*_{module}*.php", GLOB_BRACE);
        if (empty($migrations)) {
            $migrations = glob("$migrationsPath/*.php");
            $migrations = array_filter($migrations, function($m) use ($module) {
                $content = file_get_contents($m);
                return stripos($content, strtolower($module)) !== false ||
                       preg_match('/' . $module . '/i', basename($m));
            });
        }
        foreach ($migrations as $migration) {
            $components['Migrations'][] = basename($migration);
        }
    }
    
    // Check Policies
    $policiesPath = APP_PATH . '/Policies';
    if (is_dir($policiesPath)) {
        $policies = glob("$policiesPath/*Policy.php");
        foreach ($policies as $policy) {
            if (stripos(basename($policy), strtolower($module)) !== false) {
                $components['Policies'][] = basename($policy);
            }
        }
    }
    
    // Check Contracts
    $contractsPath = APP_PATH . '/Contracts';
    if (is_dir($contractsPath)) {
        $contracts = glob("$contractsPath/*.php");
        foreach ($contracts as $contract) {
            if (stripos(basename($contract), strtolower($module)) !== false) {
                $components['Contracts'][] = basename($contract);
            }
        }
    }
    
    // Display component status
    $allPresent = true;
    foreach ($components as $type => $items) {
        $count = count($items);
        $status = $count > 0 ? '✅' : '❌';
        if ($count === 0) $allPresent = false;
        
        echo "   $status $type: $count\n";
        if ($count > 0 && $count <= 5) {
            foreach ($items as $item) {
                echo "      - $item\n";
            }
        } elseif ($count > 5) {
            for ($i = 0; $i < 3; $i++) {
                echo "      - {$items[$i]}\n";
            }
            echo "      ... and " . ($count - 3) . " more\n";
        }
    }
    
    if ($allPresent) {
        $report['summary']['complete_modules']++;
    } else {
        $report['summary']['incomplete_modules']++;
        $missingCount = array_reduce($components, function($carry, $items) {
            return $carry + (count($items) === 0 ? 1 : 0);
        }, 0);
        $report['summary']['missing_components'] += $missingCount;
    }
    
    $report['modules'][$module] = $components;
    
    echo "\n";
}

// Print summary
echo str_repeat('=', 80) . "\n";
echo "📊 SUMMARY\n";
echo str_repeat('=', 80) . "\n";
echo "Total Modules:           " . $report['summary']['total_modules'] . "\n";
echo "Complete Modules:        " . $report['summary']['complete_modules'] . "\n";
echo "Incomplete Modules:      " . $report['summary']['incomplete_modules'] . "\n";
echo "Missing Components:      " . $report['summary']['missing_components'] . "\n";

// Detailed missing list
echo "\n📋 MODULES MISSING COMPONENTS:\n";
$foundMissing = false;
foreach ($report['modules'] as $module => $components) {
    $missing = array_filter($components, fn($items) => count($items) === 0);
    if (!empty($missing)) {
        $foundMissing = true;
        echo "\n❌ $module:\n";
        foreach (array_keys($missing) as $type) {
            echo "   - Missing: $type\n";
        }
    }
}

if (!$foundMissing) {
    echo "✅ All modules have all required components!\n";
}

echo "\n✅ Check Complete!\n";
