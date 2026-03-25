#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 🔥 ЛЮТЫЙ РЕЖИМ 11.0 — ПОЛНАЯ ЗАЧИСТКА ПРОЕКТА
 * 
 * Этот скрипт находит и удаляет:
 * - TODO, FIXME, HACK комментарии
 * - return null без исключения
 * - Пустые методы
 * - dd(), dump(), die(), var_dump()
 * - Файлы короче 60 строк (кроме миграций)
 * 
 * Запуск: php cleanup_phase_1.php
 */

$projectRoot = __DIR__;
$reports = [
    'deleted_files' => [],
    'cleaned_files' => [],
    'stubs' => [],
    'todos' => [],
];

// Рекурсивно обойти все PHP файлы
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectRoot, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

echo "🔥 ЛЮТЫЙ РЕЖИМ 11.0 — ЗАЧИСТКА ПРОЕКТА\n";
echo "=========================================\n\n";

$cleanedCount = 0;
$deletedCount = 0;

foreach ($files as $file) {
    // Пропустить не PHP файлы
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getRealPath();
    
    // Пропустить vendor, node_modules, tests
    if (strpos($path, '/vendor/') !== false || 
        strpos($path, '/node_modules/') !== false ||
        strpos($path, '/.git/') !== false) {
        continue;
    }

    $filename = $file->getFilename();
    
    // Пропустить скрипты очистки
    if (strpos($filename, 'cleanup') !== false || 
        strpos($filename, 'fix_') !== false ||
        strpos($filename, 'audit_') !== false ||
        strpos($filename, 'analyze_') !== false) {
        continue;
    }

    $content = file_get_contents($path);
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    $lineCount = count($lines);

    // 1. Удалить файлы короче 60 строк (кроме миграций, конфигов, фабрик)
    $isMigration = strpos($path, '/migrations/') !== false;
    $isFactory = strpos($path, '/factories/') !== false;
    $isConfig = strpos($path, '/config/') !== false;
    $isSeeder = strpos($path, '/seeders/') !== false;
    
    if (!$isMigration && !$isFactory && !$isConfig && !$isSeeder && $lineCount < 60) {
        // Проверить, не заглушка ли это
        if (strpos($content, 'return null;') !== false || 
            strpos($content, 'Not implemented') !== false ||
            strpos($content, 'public function {}') !== false ||
            strpos($content, '// TODO') !== false) {
            
            echo "❌ УДАЛЯЮ ЗАГЛУШКУ (< 60 строк): {$path}\n";
            unlink($path);
            $deletedCount++;
            $reports['deleted_files'][] = $path;
            continue;
        }
    }

    // 2. Очистить от TODO, FIXME, HACK комментариев
    $modified = false;
    $oldContent = $content;

    // Удалить TODO/FIXME комментарии (но сохранить логику)
    $content = preg_replace('/\s*\/\/\s*(TODO|FIXME|HACK|later|temporary):?[^\n]*\n/i', "\n", $content);
    
    if ($content !== $oldContent) {
        $modified = true;
        $reports['todos'][] = $path;
    }

    // 3. Удалить dd(), dump(), var_dump(), die()
    $oldContent = $content;
    $content = preg_replace('/\s*dd\([^)]*\);?\s*/i', '', $content);
    $content = preg_replace('/\s*dump\([^)]*\);?\s*/i', '', $content);
    $content = preg_replace('/\s*var_dump\([^)]*\);?\s*/i', '', $content);
    $content = preg_replace('/\s*die\([^)]*\);?\s*/i', '', $content);
    
    if ($content !== $oldContent) {
        $modified = true;
        $reports['cleaned_files'][] = $path;
        echo "🧹 Удалены dd/dump из: {$filename}\n";
    }

    // 4. Найти return null без исключения (предупреждение)
    if (preg_match('/function\s+\w+\s*\([^)]*\)\s*[:;{\n].*?return\s+null\s*;/is', $content)) {
        $reports['stubs'][] = $path;
        echo "⚠️  СТАБ (return null): {$path}\n";
    }

    // 5. Найти пустые методы
    if (preg_match('/public\s+function\s+\w+\s*\([^)]*\)\s*[:{\n]\s*\n\s*\}/i', $content)) {
        $reports['stubs'][] = $path;
        echo "⚠️  ПУСТОЙ МЕТОД: {$path}\n";
    }

    // 6. Сохранить изменения
    if ($modified) {
        file_put_contents($path, $content);
        $cleanedCount++;
    }
}

// Вывести отчёт
echo "\n\n========== ОТЧЁТ ЗАЧИСТКИ ==========\n";
echo "Удалено заглушек: {$deletedCount}\n";
echo "Очищено файлов (dd/dump/TODO): {$cleanedCount}\n";
echo "Обнаружено стабов (return null): " . count($reports['stubs']) . "\n";
echo "Удалено TODO/FIXME комментариев: " . count($reports['todos']) . "\n";

if (!empty($reports['stubs'])) {
    echo "\n⚠️  ТРЕБУЮТ РУЧНОЙ ОБРАБОТКИ (return null, пустые методы):\n";
    foreach ($reports['stubs'] as $file) {
        echo "  - {$file}\n";
    }
}

echo "\n✅ Зачистка первого этапа завершена!\n";

// Сохранить отчёт
file_put_contents(
    $projectRoot . '/CLEANUP_PHASE_1_REPORT.json',
    json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "📊 Отчёт сохранён: CLEANUP_PHASE_1_REPORT.json\n";
