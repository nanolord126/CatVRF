<?php
#!/usr/bin/env php
<?php
/**
 * МЕГА-РАСПАКОВКА всех 146 миграций за раз
 * Используется простая стратегия: добавляем правильные переносы везде
 */

function fixMigration($code) {
    // 1. Убираем объявления типов которые сжимают код
    $code = str_replace('declare(strict_types=1);use', "declare(strict_types=1);\n\nuse", $code);
    
    // 2. Разделяем use statements
    $code = preg_replace('/^use\s+([^\n;]+);/m', "use $1;\n", $code);
    $code = preg_replace("/(^use[^\n]+;\n)(use[^\n]+;)/", "$1\n$2", $code);
    
    // 3. Добавляем переносы перед return new class
    $code = preg_replace('/;\s*\/\*\*/', ";\n\n/**", $code);
    $code = preg_replace('/\*\/\s*return new class/', "*/\nreturn new class", $code);
    
    // 4. Добавляем переносы перед функциями
    $code = preg_replace('/(\{)\s+(public|private|protected)\s+function/', "$1\n    $2 function", $code);
    
    // 5. Добавляем переносы перед Schema::create
    $code = preg_replace('/(\))\s*(,)\s*(function)/', "$1$2\n        $3", $code);
    
    // 6. Добавляем переносы после $table->
    $code = preg_replace('/(\$table->[^;]+;)\s+(\$table->)/', "$1\n            $2", $code);
    
    // 7. Добавляем переносы перед комментариями
    $code = preg_replace('/;\s+(\/\/)/', ";\n            $1", $code);
    
    // 8. Добавляем переносы после закрывающих скобок функций
    $code = preg_replace('/(\})\s*\);/', "$1\n        );", $code);
    $code = preg_replace('/(\})\s*(public|private)/', "$1\n\n    $2", $code);
    
    // 9. Убираем множественные пробелы
    $code = preg_replace('/\s{2,}/', ' ', $code);
    
    // 10. Убираем множественные переносы
    $code = preg_replace('/\n{3,}/', "\n\n", $code);
    
    // 11. Финальная чистка пробелов
    $code = trim($code) . "\n";
    
    return $code;
}

$dirs = [
    __DIR__ . '/database/migrations',
    __DIR__ . '/database/migrations/tenant',
];

$count = 0;
$errors = [];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        if (substr($file, -4) !== '.php') continue;
        
        $path = $dir . '/' . $file;
        $content = file_get_contents($path);
        
        // Проверим что это миграция с возвращаемым классом
        if (strpos($content, 'return new class extends Migration') === false) {
            continue;
        }
        
        $fixed = fixMigration($content);
        
        if ($fixed !== $content) {
            file_put_contents($path, $fixed);
            $count++;
            echo "✓ $file\n";
        }
    }
}

echo "\n✅ Исправлено файлов: $count\n";
