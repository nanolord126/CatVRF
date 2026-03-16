<?php

declare(strict_types=1);

/**
 * Аудит полноты реализации marketplace вертикалей
 * Проверяет наличие: Model, Migration, Policy, Seeder, Resource, Pages
 */

$resourcesDir = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';
$modelsDir = __DIR__ . '/app/Models';
$policiesDir = __DIR__ . '/app/Policies';
$seedersDir = __DIR__ . '/database/seeders';
$migrationsDir = __DIR__ . '/database/migrations';

$audit = [];
$allResources = [];

// Найти все Resource файлы
$files = new RecursiveDirectoryIterator($resourcesDir);
$iterator = new RecursiveIteratorIterator($files);

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php' && strpos($file->getFilename(), 'Resource.php') !== false) {
        $filename = $file->getFilename();
        $resourceName = str_replace('Resource.php', '', $filename);
        
        // Получить namespace для определения модели
        $content = file_get_contents($file->getPathname());
        
        // Попытаться найти имя модели в коде
        if (preg_match('/protected static \?string \$model = (.*?)::class/', $content, $matches)) {
            $modelClass = $matches[1];
            $modelName = class_basename($modelClass);
        } else {
            $modelName = $resourceName;
        }

        $allResources[$resourceName] = [
            'resource_file' => $file->getFilename(),
            'resource_path' => str_replace(__DIR__ . '/', '', $file->getPathname()),
            'model_name' => $modelName,
            'checks' => [],
        ];
    }
}

// Для каждого Resource проверить наличие companion файлов
foreach ($allResources as $resourceName => $resource) {
    $modelName = $resource['model_name'];

    // 1. Проверить Model
    $modelPaths = [
        "$modelsDir/{$modelName}.php",
        "$modelsDir/Marketplace/{$modelName}.php",
        "$modelsDir/Tenants/{$modelName}.php",
    ];
    
    $modelExists = false;
    $modelPath = null;
    foreach ($modelPaths as $path) {
        if (file_exists($path)) {
            $modelExists = true;
            $modelPath = str_replace(__DIR__ . '/', '', $path);
            break;
        }
    }
    $resource['checks']['Model'] = $modelExists ? "✅ {$modelPath}" : "❌ Не найден ($modelName)";

    // 2. Проверить Migration
    $migrationPattern = "*create_{$modelName}*";
    $migrations = glob("$migrationsDir/$migrationPattern");
    $migrationExists = !empty($migrations);
    $resource['checks']['Migration'] = $migrationExists ? ("✅ " . basename($migrations[0] ?? '')) : "❌ Не найдена ({$modelName})";

    // 3. Проверить Policy
    $policyPath = "$policiesDir/{$modelName}Policy.php";
    $policyExists = file_exists($policyPath);
    $resource['checks']['Policy'] = $policyExists ? "✅ {$modelName}Policy" : "❌ Не найдена";

    // 4. Проверить Seeder
    $seederPath = "$seedersDir/{$modelName}Seeder.php";
    $seederExists = file_exists($seederPath);
    $resource['checks']['Seeder'] = $seederExists ? "✅ {$modelName}Seeder" : "⚠️  Не найден";

    // 5. Проверить Pages
    $resourcePagesDir = dirname($resource['resource_path']) . "/{$resourceName}/Pages";
    $pageFiles = glob(__DIR__ . "/{$resourcePagesDir}/*.php");
    $pageCount = count($pageFiles) ?? 0;
    $resource['checks']['Pages'] = ($pageCount >= 3) ? "✅ {$pageCount} страниц" : "❌ Недостаточно страниц ({$pageCount})";

    $allResources[$resourceName] = $resource;
}

// Вывести отчет
echo "\n╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║               АУДИТ ПОЛНОТЫ РЕАЛИЗАЦИИ MARKETPLACE ВЕРТИКАЛЕЙ              ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

ksort($allResources);

$totalOk = 0;
$totalIssues = 0;

foreach ($allResources as $resourceName => $resource) {
    echo "📦 {$resourceName}\n";
    echo "   Модель: {$resource['model_name']}\n";
    
    $hasIssues = false;
    foreach ($resource['checks'] as $checkName => $checkResult) {
        echo "   {$checkResult}\n";
        if (strpos($checkResult, '❌') !== false || strpos($checkResult, '⚠️') !== false) {
            $hasIssues = true;
            $totalIssues++;
        } else {
            $totalOk++;
        }
    }
    echo "\n";
}

echo str_repeat("═", 80) . "\n";
echo "Всего проверок: " . (($totalOk + $totalIssues)) . " | ✅ OK: {$totalOk} | ⚠️  Проблемы: {$totalIssues}\n";
echo str_repeat("═", 80) . "\n\n";

if ($totalIssues > 0) {
    echo "⚠️  НЕОБХОДИМЫ ДЕЙСТВИЯ: Обнаружены пропущенные компоненты\n";
}
