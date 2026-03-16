<?php
/**
 * Профессиональная распаковка миграций с простой стратегией
 */

$migrationsDir = __DIR__ . '/database/migrations';

// Список файлов
$files = array_merge(
    glob($migrationsDir . '/*.php'),
    glob($migrationsDir . '/tenant/*.php')
);

echo "Найдено миграций: " . count($files) . "\n\n";

foreach ($files as $file) {
    $code = file_get_contents($file);
    
    // Если это однострочный файл
    if (substr_count($code, "\n") < 3) {
        // Просто добавляем переносы в правильных местах
        $code = str_replace(
            ['<?php use ', 'use Illuminate', 'return new class', 'public function', ' { ', ' } ', '; '],
            ["<?php\n\nuse ", "\nuse Illuminate", "\n\nreturn new class", "\n    public function", " {\n        ", "\n    }\n", ";\n        "],
            $code
        );
        
        // Убираем double spaces
        $code = preg_replace('/\s+/', ' ', $code);
        
        // Снова добавляем форматирование более аккуратно
        $lines = [];
        $parts = preg_split('/(\s*[{}];?\s*)/', $code, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $indent = 0;
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            if (str_ends_with($part, '}')) {
                $indent = max(0, $indent - 1);
                $lines[] = str_repeat("    ", $indent) . $part;
            } elseif (str_ends_with($part, '{')) {
                $lines[] = str_repeat("    ", $indent) . $part;
                $indent++;
            } else {
                $lines[] = str_repeat("    ", $indent) . $part;
            }
        }
        
        $formatted = implode("\n", $lines) . "\n";
        
        // Финальный проход для очистки
        $formatted = preg_replace('/\n\n+/', "\n", $formatted);
        
        file_put_contents($file, $formatted);
        
        echo "✓ " . basename($file) . "\n";
    }
}

echo "\n✅ Распаковано!\n";
