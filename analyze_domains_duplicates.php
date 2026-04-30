<?php

/**
 * Скрипт для анализа дубликатов между app/Domains и modules
 * 
 * Использование: php analyze_domains_duplicates.php
 */

$domainsToCheck = [
    'Payment',
    'Payments', 
    'Wallet',
    'Auto',
    'Beauty',
    'Fashion',
    'Veterinary',
    'Cart',
    'Webhooks',
];

$domainsPath = __DIR__ . '/app/Domains';
$modulesPath = __DIR__ . '/modules';
$routesPath = __DIR__ . '/routes';

echo "=== Анализ дубликатов между app/Domains и modules ===\n\n";

foreach ($domainsToCheck as $domain) {
    $domainPath = $domainsPath . '/' . $domain;
    if (!is_dir($domainPath)) {
        echo "❌ $domain - не найден в app/Domains\n";
        continue;
    }
    
    echo "📦 $domain:\n";
    
    // Подсчет файлов
    $fileCount = countFiles($domainPath);
    echo "  Файлов в app/Domains/$domain: $fileCount\n";
    
    // Проверка в modules
    $modulePath = findModulePath($modulesPath, $domain);
    if ($modulePath) {
        $moduleFileCount = countFiles($modulePath);
        echo "  Файлов в modules/$modulePath: $moduleFileCount\n";
        echo "  Статус: ⚠️  Дубликат\n";
    } else {
        echo "  Статус: ✅ Уникальный (нет в modules)\n";
    }
    
    // Поиск использований в routes
    $usages = findUsagesInRoutes($routesPath, "App\\Domains\\$domain");
    if ($usages > 0) {
        echo "  Использований в routes: $usages ⚠️\n";
    }
    
    // Поиск использований в app
    $appUsages = findUsagesInApp(__DIR__ . '/app', "App\\Domains\\$domain");
    if ($appUsages > 0) {
        echo "  Использований в app/: $appUsages ⚠️\n";
    }
    
    echo "\n";
}

function countFiles($path): int {
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $count++;
        }
    }
    return $count;
}

function findModulePath($modulesPath, $domain): ?string {
    $moduleMap = [
        'Payments' => 'Payment',
        'Beauty' => 'BeautyMasters',
        'Veterinary' => 'VetGrooming',
    ];
    
    $moduleName = $moduleMap[$domain] ?? $domain;
    $path = $modulesPath . '/' . $moduleName;
    
    return is_dir($path) ? $path : null;
}

function findUsagesInRoutes($routesPath, $namespace): int {
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($routesPath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (str_contains($content, $namespace)) {
                $count++;
            }
        }
    }
    return $count;
}

function findUsagesInApp($appPath, $namespace): int {
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($appPath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (str_contains($content, $namespace)) {
                $count++;
            }
        }
    }
    return $count;
}
