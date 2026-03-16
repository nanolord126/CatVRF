<?php

/**
 * Скрипт для добавления всех Policy регистраций в AuthServiceProvider
 */

$policiesDir = __DIR__ . '/app/Policies';
$files = array_diff(scandir($policiesDir), ['..', '.', 'BasePolicy.php', 'BaseSecurityPolicy.php']);

$policies = [];

foreach ($files as $file) {
    if (is_dir("$policiesDir/$file") || !str_ends_with($file, 'Policy.php')) {
        continue;
    }
    
    $className = str_replace('Policy.php', '', $file);
    $policies[$className] = "App\\Policies\\{$className}Policy";
}

// Также добавить Marketplace policies
$marketplaceDir = __DIR__ . '/app/Policies/Marketplace';
$mpFiles = array_diff(scandir($marketplaceDir), ['..', '.']);

foreach ($mpFiles as $file) {
    if (!str_ends_with($file, 'Policy.php')) {
        continue;
    }
    
    $className = str_replace('Policy.php', '', $file);
    $policies[$className] = "App\\Policies\\Marketplace\\{$className}Policy";
}

echo "Total policies found: " . count($policies) . "\n";
foreach ($policies as $model => $policy) {
    echo "  $model => $policy\n";
}
