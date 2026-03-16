<?php
/**
 * Финальный скрипт распаковки ALL миграций 146 штук
 * Стратегия: использовать regex для добавления переносов в правильных местах
 */

function unpackMigration($code) {
    // Шаг 1: Нормализуем пробелы вокруг PHP конструкций
    $replacements = [
        // Before use statements
        '/^<\?php\s+use/' => "<?php\n\nuse",
        
        // After use statements before return
        '/\);\s+return new class/' => ");\n\nreturn new class",
        
        // Функции
        '/\s+public function/' => "\n    public function",
        '/\s+private function/' => "\n    private function",
        '/\s+protected function/' => "\n    protected function",
        
        // Открывающие скобки
        '/\{\s+/' => " {\n        ",
        
        // Закрывающие скобки
        '/\s+\}/' => "\n    }",
        '/\}\s+public/' => "}\n\n    public",
        '/\}\s+private/' => "}\n\n    private",
        '/\}\s+\);/' => "});\n        ",
        
        // Точки запятой перед })
        '/;\s+}/' => ";\n        }",
        
        // Конец файла
        '/\};\s*$/' => "};\n",
    ];
    
    foreach ($replacements as $pattern => $replacement) {
        $code = preg_replace($pattern, $replacement, $code);
    }
    
    // Шаг 2: Добавляем переносы после // комментариев
    $code = preg_replace('/\/\/\s+/', ";\n        // ", $code);
    
    // Шаг 3: Убираем множественные пробелы
    $code = preg_replace('/\s+/', ' ', $code);
    
    // Шаг 4: Добавляем переносы у $table-> вызовов
    $code = preg_replace('/;\s+\$table->/', ";\n            \$table->", $code);
    
    // Шаг 5: Переносы после Schema::create
    $code = preg_replace('/\)\s*,\s*function/', "),\n        function", $code);
    
    // Шаг 6: Финальная очистка множественных переносов
    $code = preg_replace('/\n\s*\n/', "\n", $code);
    
    return $code;
}

// Найти все миграции
$dirs = [
    __DIR__ . '/database/migrations',
    __DIR__ . '/database/migrations/tenant',
];

$processed = 0;
$errors = [];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $files = glob($dir . '/*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        // Если это миграция (содержит Migration и extends)
        if (strpos($content, 'extends Migration') === false) {
            continue;
        }
        
        // Если файл сжат в одну-две строки
        $lines = substr_count($content, "\n");
        if ($lines < 10) {
            try {
                $unpacked = unpackMigration($content);
                file_put_contents($file, $unpacked);
                $processed++;
                echo "✓ " . basename($file) . "\n";
            } catch (Exception $e) {
                $errors[] = basename($file) . ": " . $e->getMessage();
            }
        }
    }
}

echo "\n✅ Распакованных миграций: $processed\n";
if (!empty($errors)) {
    echo "⚠️  Ошибок: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}
