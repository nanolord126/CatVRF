#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 🔥 ЛЮТЫЙ РЕЖИМ 11.0 — УДАЛЕНИЕ FACADE ПАТТЕРНОВ
 * 
 * Этот скрипт:
 * - Находит все app/Facades/* файлы и удаляет их
 * - Находит и предупреждает о Facade использованиях
 * - Замечает статические вызовы: Auth::, Response::, Request::, Cache::, etc
 * 
 * Запуск: php cleanup_facade.php
 */

$projectRoot = __DIR__;
$reports = [
    'facades_deleted' => [],
    'facade_usages' => [],
    'static_calls' => [],
];

// 1. Удалить все Facade классы
echo "🔥 УДАЛЕНИЕ FACADE\n";
echo "==================\n\n";

$facadesDir = $projectRoot . '/app/Facades';
if (is_dir($facadesDir)) {
    $files = glob($facadesDir . '/*.php');
    foreach ($files as $file) {
        echo "❌ УДАЛЯЮ FACADE: {$file}\n";
        unlink($file);
        $reports['facades_deleted'][] = basename($file);
    }
    
    // Удалить директорию
    rmdir($facadesDir);
    echo "❌ Удаленa директория: app/Facades\n";
}

// 2. Найти Facade использования
echo "\n🔎 ПОИСК FACADE ИСПОЛЬЗОВАНИЙ\n";
echo "=============================\n\n";

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectRoot, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$staticPatterns = [
    'Auth::' => 'Auth Facade',
    'Response::' => 'Response Facade',
    'Request::' => 'Request Facade',
    'Route::' => 'Route Facade',
    'Cache::' => 'Cache Facade',
    'Queue::' => 'Queue Facade',
    'Mail::' => 'Mail Facade',
    'Session::' => 'Session Facade',
    'config(' => 'config() helper',
    'auth(' => 'auth() helper',
    'response(' => 'response() helper',
    'request(' => 'request() helper',
    'cache(' => 'cache() helper',
    'session(' => 'session() helper',
];

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getRealPath();
    
    if (strpos($path, '/vendor/') !== false || 
        strpos($path, '/node_modules/') !== false ||
        strpos($path, '/.git/') !== false ||
        strpos($path, 'cleanup') !== false) {
        continue;
    }

    $content = file_get_contents($path);
    
    foreach ($staticPatterns as $pattern => $description) {
        if (strpos($content, $pattern) !== false) {
            $reports['static_calls'][] = [
                'file' => $path,
                'pattern' => $pattern,
                'type' => $description,
            ];
            echo "⚠️  {$description} в: {$path}\n";
        }
    }
}

// 3. Вывести отчёт
echo "\n\n========== ОТЧЁТ УДАЛЕНИЯ FACADE ==========\n";
echo "Удалено Facade классов: " . count($reports['facades_deleted']) . "\n";
echo "Обнаружено статических вызовов: " . count($reports['static_calls']) . "\n";

if (!empty($reports['static_calls'])) {
    echo "\n⚠️  ТРЕБУЮТ ЗАМЕНЫ НА CONSTRUCTOR INJECTION:\n";
    $grouped = [];
    foreach ($reports['static_calls'] as $item) {
        $grouped[$item['file']][] = $item['pattern'];
    }
    
    foreach ($grouped as $file => $patterns) {
        echo "\n  {$file}\n";
        foreach (array_unique($patterns) as $p) {
            echo "    - {$p}\n";
        }
    }
}

echo "\n✅ Зачистка Facade завершена!\n";

// Сохранить отчёт
file_put_contents(
    $projectRoot . '/CLEANUP_FACADE_REPORT.json',
    json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "📊 Отчёт сохранён: CLEANUP_FACADE_REPORT.json\n";
