<?php

declare(strict_types=1);

/**
 * Полный аудит Pages на предмет:
 * 1. Соответствие с Resource классами
 * 2. Существование Model классов
 * 3. Синтаксические ошибки и lint
 * 4. Зависимости (Guard, LogManager и т.д.)
 * 5. Пустые или stub-реализации
 */

$baseDir = __DIR__;
$pagesDir = "{$baseDir}/app/Filament/Tenant/Resources";

// Рекурсивно найти все Pages
$pageFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pagesDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$issues = [];
$checkedPages = 0;
$validPages = 0;

foreach ($pageFiles as $file) {
    if ($file->getExtension() !== 'php' || strpos($file->getPathname(), '\Pages\\') === false) {
        continue;
    }

    $checkedPages++;
    $pagePath = $file->getPathname();
    $content = file_get_contents($pagePath);
    
    // Проверка 1: Синтаксис PHP
    $lintOutput = [];
    $lintStatus = 0;
    exec("php -l " . escapeshellarg($pagePath) . " 2>&1", $lintOutput, $lintStatus);
    
    if ($lintStatus !== 0) {
        $issues[$pagePath][] = "❌ СИНТАКСИС: " . implode("\n", $lintOutput);
        continue;
    }

    // Проверка 2: Найти Resource класс
    if (!preg_match('/protected static string \$resource = ([^;]+);/', $content, $matches)) {
        $issues[$pagePath][] = "❌ РЕСУРС: Не найдена переменная \$resource";
        continue;
    }
    
    $resourceClass = trim($matches[1]);
    $resourcePath = str_replace('\\', '/', $resourceClass);
    $resourcePath = str_replace('App/Filament/Tenant/Resources/', "{$baseDir}/app/Filament/Tenant/Resources/", $resourcePath);
    $resourcePath = str_replace('::class', '.php', $resourcePath);
    
    if (!file_exists($resourcePath)) {
        $issues[$pagePath][] = "❌ РЕСУРС НЕ СУЩЕСТВУЕТ: {$resourceClass}";
        continue;
    }

    // Проверка 3: Model из Resource
    $resourceContent = file_get_contents($resourcePath);
    if (!preg_match('/protected static \?string \$model = ([^;]+);/', $resourceContent, $modelMatches)) {
        $issues[$pagePath][] = "⚠️  МОДЕЛЬ: Не найдена в Resource {$resourceClass}";
    } else {
        $modelClass = trim($modelMatches[1]);
        $modelPath = str_replace('\\', '/', $modelClass);
        $modelPath = str_replace('App/Models/', "{$baseDir}/app/Models/", $modelPath);
        $modelPath = str_replace('::class', '.php', $modelPath);
        
        if (!file_exists($modelPath)) {
            $issues[$pagePath][] = "❌ МОДЕЛЬ НЕ СУЩЕСТВУЕТ: {$modelClass}";
            continue;
        }
    }

    // Проверка 4: Пустые методы/stub-реализации
    $emptyMethods = [];
    if (preg_match_all('/protected function (\w+)\([^)]*\)\s*:\s*\w+\s*\{\s*\}/', $content, $matches)) {
        $emptyMethods = $matches[1];
    }
    
    if (!empty($emptyMethods)) {
        $issues[$pagePath][] = "⚠️  ПУСТЫЕ МЕТОДЫ: " . implode(", ", $emptyMethods);
    }

    // Проверка 5: Stub-комментарии
    if (strpos($content, 'TODO') !== false || strpos($content, 'FIXME') !== false) {
        $issues[$pagePath][] = "⚠️  TODO/FIXME: Найдены в коде";
    }

    // Проверка 6: Использование app() вместо DI
    if (preg_match('/app\s*\(\s*[\'"]?(?!Filament)/', $content) && !preg_match('/app\(Filament/', $content)) {
        $issues[$pagePath][] = "⚠️  ЗАВИСИМОСТИ: Используется app() вместо DI";
    }

    // Проверка 7: Правильные header actions
    if (preg_match('/getHeaderActions/', $content)) {
        if (!preg_match('/Action::|Action->/', $content)) {
            $issues[$pagePath][] = "⚠️  ACTIONS: getHeaderActions() есть, но Actions не используются";
        }
    }

    // Все проверки пройдены
    $validPages++;
}

// Вывод результатов
echo "\n" . str_repeat("=", 80) . "\n";
echo "АУДИТ PAGES ЦЕЛОСТНОСТИ\n";
echo str_repeat("=", 80) . "\n";

echo "\n📊 СТАТИСТИКА:\n";
echo "   Проверено Pages: {$checkedPages}\n";
echo "   Валидных: {$validPages}\n";
echo "   С проблемами: " . count($issues) . "\n";

if (!empty($issues)) {
    echo "\n❌ ПРОБЛЕМНЫЕ FILES:\n\n";
    
    foreach ($issues as $file => $fileIssues) {
        $relative = str_replace($baseDir . '/', '', $file);
        echo "📄 {$relative}\n";
        
        foreach ($fileIssues as $issue) {
            echo "   {$issue}\n";
        }
        echo "\n";
    }
} else {
    echo "\n✅ ВСЕ PAGES В ПОРЯДКЕ!\n";
}

echo str_repeat("=", 80) . "\n\n";
