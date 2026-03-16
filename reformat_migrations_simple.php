<?php
/**
 * Красивая распаковка сжатых миграций - простой способ
 */

function formatMigration($code) {
    // Убераем открывающий тег если в начале
    $code = str_replace('<?php ', '<?php' . "\n", $code);
    
    // Добавляем переносы после ключевых слов
    $keywords = [
        'use ' => "use ",
        'return ' => "\n" . "return ",
        'final class' => "\n\nfinal class",
        'public function' => "\n    public function",
        'private function' => "\n    private function",
        'protected function' => "\n    protected function",
        ' { ' => " {\n        ",
        '} ' => "\n    }\n",
        '; ' => ";\n        ",
    ];
    
    foreach ($keywords as $old => $new) {
        $code = str_replace($old, $new, $code);
    }
    
    // Убираем лишние пробелы
    $code = preg_replace('/\s+/', ' ', $code);
    
    // Заново добавляем форматирование
    $code = str_replace(
        ['<?php ', 'use ', 'return ', 'function '],
        ['<?php' . "\n", "\nuse ", "\nreturn ", "\nfunction "],
        $code
    );
    
    // Добавляем отступы к фигурным скобкам
    $lines = explode("\n", $code);
    $formatted = [];
    $indent = 0;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Уменьшаем отступ перед закрывающей скобкой
        if (str_starts_with($line, '}')) {
            $indent = max(0, $indent - 1);
        }
        
        $formatted[] = str_repeat("    ", $indent) . $line;
        
        // Увеличиваем отступ после открывающей скобки
        if (str_ends_with($line, '{')) {
            $indent++;
        }
        if (str_ends_with($line, '};')) {
            $indent = max(0, $indent - 1);
        }
    }
    
    return implode("\n", $formatted) . "\n";
}

// Обработка миграций
$dirs = [
    __DIR__ . '/database/migrations',
    __DIR__ . '/database/migrations/tenant',
];

$count = 0;
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $files = glob($dir . '/*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $lines = substr_count($content, "\n");
        
        // Если сжато
        if ($lines < 5) {
            $formatted = formatMigration($content);
            file_put_contents($file, $formatted);
            $count++;
            
            echo basename($file) . " ✓\n";
        }
    }
}

echo "\n✅ Распакованных файлов: $count\n";
