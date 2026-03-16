<?php

declare(strict_types=1);

/**
 * Полный аудит целостности всех Pages, Resources и Models
 */

$baseDir = getcwd();
if (!is_dir("app/Filament/Tenant/Resources")) {
    echo "❌ Запустить из корня проекта!\n";
    exit(1);
}

$errors = [];
$warnings = [];
$checked = 0;
$valid = 0;

// Найти все Pages
$pageFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator("app/Filament/Tenant/Resources", RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($pageFiles as $file) {
    if ($file->getExtension() !== 'php' || strpos($file->getPathname(), '/Pages/') === false) {
        continue;
    }

    $checked++;
    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    $relativePath = str_replace("\\", "/", $filePath);
    
    // Проверка 1: Есть ли declare(strict_types=1)
    if (!preg_match('/^<\?php\s+declare\(strict_types=1\)/m', $content)) {
        $warnings[] = "⚠️  NO_DECLARE: {$relativePath}";
    }

    // Проверка 2: Есть ли protected static string $resource
    if (!preg_match('/protected static string \$resource = ([^;]+);/', $content, $resMatches)) {
        $errors[] = "❌ NO_RESOURCE: {$relativePath} - не найдена переменная \$resource";
        continue;
    }

    $resourceClass = trim($resMatches[1]);
    
    // Проверка 3: Существует ли Resource класс
    $resourcePath = str_replace('::class', '.php', str_replace('\\', '/', "app/Filament/Tenant/Resources/{$resourceClass}"));
    if (!file_exists($resourcePath)) {
        // Попробуем найти в app/Filament/Tenant/Resources/
        $shortName = basename($resourceClass, '\\');
        $possiblePath = "app/Filament/Tenant/Resources/{$shortName}.php";
        
        if (file_exists($possiblePath)) {
            $resourcePath = $possiblePath;
        } else {
            $errors[] = "❌ RESOURCE_NOT_FOUND: {$relativePath} -> {$resourceClass}";
            continue;
        }
    }

    // Проверка 4: Есть ли Model в Resource
    $resourceContent = file_get_contents($resourcePath);
    if (!preg_match('/protected static \?string \$model = ([^;]+);/', $resourceContent, $modelMatches)) {
        $warnings[] = "⚠️  NO_MODEL_DEF: {$resourcePath} - Resource не определяет Model";
        $valid++;
        continue;
    }

    $modelClass = trim($modelMatches[1]);
    
    // Проверка 5: Существует ли Model класс
    $modelPath = str_replace('::class', '.php', str_replace('\\', '/', "app/Models/{$modelClass}"));
    if (!file_exists($modelPath)) {
        $errors[] = "❌ MODEL_NOT_FOUND: {$resourcePath} references {$modelClass}";
        continue;
    }

    $valid++;
}

// Вывод результатов
echo "\n" . str_repeat("═", 80) . "\n";
echo "ПОЛНЫЙ АУДИТ ЦЕЛОСТНОСТИ PAGES → RESOURCES → MODELS\n";
echo str_repeat("═", 80) . "\n";
echo "\n📊 СТАТИСТИКА:\n";
echo "   Pages: {$checked}\n";
echo "   Валидных: {$valid}\n";
echo "   Ошибок: " . count($errors) . "\n";
echo "   Предупреждений: " . count($warnings) . "\n";

if (!empty($errors)) {
    echo "\n❌ КРИТИЧЕСКИЕ ОШИБКИ:\n";
    foreach ($errors as $err) {
        echo "   {$err}\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️  ПРЕДУПРЕЖДЕНИЯ:\n";
    foreach (array_slice($warnings, 0, 20) as $warn) {
        echo "   {$warn}\n";
    }
    if (count($warnings) > 20) {
        echo "   ... и ещё " . (count($warnings) - 20) . " предупреждений\n";
    }
}

if (empty($errors)) {
    echo "\n✅ ВСЕ PAGES КОРРЕКТНО СВЯЗАНЫ С RESOURCES И MODELS!\n";
}

echo "\n" . str_repeat("═", 80) . "\n";

exit(empty($errors) ? 0 : 1);
