<?php

declare(strict_types=1);

$directories = [
    __DIR__ . '/app/Domains',
    __DIR__ . '/app/Services',
];

$results = [
    'return_null' => [],
    'return_empty_array' => [],
    'return_empty_collection' => [],
    'missing_return_type' => [],
    'todo_comments' => [],
];

function scanDirectory($dir) {
    global $results;
    $files = glob($dir . '/*');
    foreach ($files as $file) {
        if (is_dir($file)) {
            scanDirectory($file);
        } elseif (is_file($file) && str_ends_with($file, '.php')) {
            analyzeFile($file);
        }
    }
}

function analyzeFile($file) {
    global $results;
    $content = file_get_contents($file);
    $shortName = str_replace(dirname(__DIR__), '', $file);
    
    // Check for "return null;"
    if (preg_match('/return\s+null\s*;/i', $content)) {
        $results['return_null'][] = $shortName;
    }
    
    // Check for "return [];" or "return array();"
    if (preg_match('/return\s+(\[\]|array\(\))\s*;/i', $content)) {
        $results['return_empty_array'][] = $shortName;
    }
    
    // Check for empty collections
    if (preg_match('/return\s+collect\(\[?\]?\)\s*;/i', $content)) {
        $results['return_empty_collection'][] = $shortName;
    }
    
    // Check for TODOs
    if (preg_match('/@todo|todo:|fixme|stub|placeholder/i', $content)) {
        $results['todo_comments'][] = $shortName;
    }

    // Checking methods for strict return types using regex
    // Looks for `public/protected/private function name(...) ` WITHOUT `: type`
    // This is a naive regex but works well enough for an audit
    if (preg_match_all('/(?:public|protected|private)\s+function\s+[a-zA-Z0-9_]+\s*\([^)]*\)\s*(?!:\s*[a-zA-Z0-9_\\\?]+)\{/', $content, $matches)) {
        // Exclude constructors and magic methods
        $missing = false;
        foreach ($matches[0] as $match) {
            if (!preg_match('/function\s+__construct/', $match) && !preg_match('/function\s+__/', $match)) {
                $missing = true;
            }
        }
        if ($missing && strpos($file, 'Services') !== false) {
             $results['missing_return_type'][] = $shortName;
        }
    }
}

foreach ($directories as $dir) {
    if (is_dir($dir)) scanDirectory($dir);
}

echo "=== глубокий Аудит Методов и Ответов (Канон 2026) ===\n";
echo "Файлов с 'return null;' (нарушение канона): " . count($results['return_null']) . "\n";
echo "Файлов с 'return [];' (нарушение канона): " . count($results['return_empty_array']) . "\n";
echo "Файлов с 'return collect();' (нарушение канона): " . count($results['return_empty_collection']) . "\n";
echo "Сервисов без строгой типизации возвращаемого значения: " . count($results['missing_return_type']) . "\n";
echo "Файлов с TODO/FIXME (нарушение): " . count($results['todo_comments']) . "\n";

file_put_contents('deep_audit_methods_results.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\nДетальный лог сохранен в: deep_audit_methods_results.json\n";
