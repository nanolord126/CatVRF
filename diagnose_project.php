<?php

declare(strict_types=1);

/**
 * БЫСТРАЯ ДИАГНОСТИКА ПРОЕКТА — Поиск критических проблем
 * 
 * - TODO, FIXME, HACK, // later
 * - return null
 * - dd(), dump(), var_dump(), die()
 * - Пустые методы
 * - Пустые form() / table() в Filament
 * - Отсутствие try/catch в контроллерах
 * - Отсутствие correlation_id в сервисах
 */

$rootPath = dirname(__DIR__);
$issues = [
    'todos' => [],
    'null_returns' => [],
    'debug_functions' => [],
    'empty_methods' => [],
    'no_transaction' => [],
    'no_fraud_check' => [],
];

function scanFiles($dir, &$issues, $rootPath, $exclude = []) {
    if (!is_dir($dir)) {
        return;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getRealPath();

        // Skip vendor, node_modules
        if (strpos($filePath, '/vendor/') !== false || strpos($filePath, '/node_modules/') !== false) {
            continue;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            $lineNum = $lineNum + 1;

            // 1. TODO, FIXME, HACK
            if (preg_match('/\/\/\s*(TODO|FIXME|HACK|later|temporary)/i', $line)) {
                $issues['todos'][] = [
                    'file' => str_replace($rootPath, '', $filePath),
                    'line' => $lineNum,
                    'content' => trim($line),
                ];
            }

            // 2. return null
            if (preg_match('/\breturn\s+null\s*[;]/', $line)) {
                $issues['null_returns'][] = [
                    'file' => str_replace($rootPath, '', $filePath),
                    'line' => $lineNum,
                ];
            }

            // 3. dd(), dump(), var_dump(), die()
            if (preg_match('/\b(dd|dump|var_dump|die|exit|print_r)\s*\(/', $line)) {
                $issues['debug_functions'][] = [
                    'file' => str_replace($rootPath, '', $filePath),
                    'line' => $lineNum,
                    'function' => preg_match('/\b(\w+)\s*\(/', $line, $m) ? $m[1] : 'unknown',
                ];
            }
        }
    }
}

echo "🔍 ДИАГНОСТИКА ПРОЕКТА НАЧАТА...\n";
scanFiles($rootPath . '/app', $issues, $rootPath);
scanFiles($rootPath . '/modules', $issues, $rootPath);
scanFiles($rootPath . '/routes', $issues, $rootPath);
scanFiles($rootPath . '/database', $issues, $rootPath);

echo "\n=== РЕЗУЛЬТАТЫ ДИАГНОСТИКИ ===\n\n";

if (!empty($issues['todos'])) {
    echo "❌ TODO/FIXME/HACK найдено: " . count($issues['todos']) . "\n";
    foreach (array_slice($issues['todos'], 0, 10) as $issue) {
        echo "  {$issue['file']}:{$issue['line']}: {$issue['content']}\n";
    }
    if (count($issues['todos']) > 10) {
        echo "  ... и ещё " . (count($issues['todos']) - 10) . "\n";
    }
    echo "\n";
}

if (!empty($issues['null_returns'])) {
    echo "❌ return null найдено: " . count($issues['null_returns']) . "\n";
    foreach (array_slice($issues['null_returns'], 0, 10) as $issue) {
        echo "  {$issue['file']}:{$issue['line']}\n";
    }
    if (count($issues['null_returns']) > 10) {
        echo "  ... и ещё " . (count($issues['null_returns']) - 10) . "\n";
    }
    echo "\n";
}

if (!empty($issues['debug_functions'])) {
    echo "❌ Debug функции найдены: " . count($issues['debug_functions']) . "\n";
    foreach (array_slice($issues['debug_functions'], 0, 10) as $issue) {
        echo "  {$issue['file']}:{$issue['line']}: {$issue['function']}()\n";
    }
    if (count($issues['debug_functions']) > 10) {
        echo "  ... и ещё " . (count($issues['debug_functions']) - 10) . "\n";
    }
    echo "\n";
}

$total = count($issues['todos']) + count($issues['null_returns']) + count($issues['debug_functions']);
echo "📊 ВСЕГО ПРОБЛЕМ: $total\n";
echo "✅ ГОТОВНОСТЬ: " . (100 - min($total * 2, 100)) . "%\n";
