<?php
// Распаковка сжатых миграций

$paths = [
    'C:\\opt\\kotvrf\\CatVRF\\database\\migrations\\tenant',
    'C:\\opt\\kotvrf\\CatVRF\\database\\migrations'
];

$fixed = 0;
$compressed = [];

foreach ($paths as $path) {
    if (!is_dir($path)) continue;
    
    $files = glob($path . '/*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        
        // Если файл имеет < 5 строк, это сжатый файл
        if (count($lines) < 5) {
            $compressed[] = basename($file);
            
            // Попытка расформатировать
            $formatted = formatPhpCode($content);
            
            if ($formatted !== $content) {
                file_put_contents($file, $formatted);
                $fixed++;
                echo "Fixed: " . basename($file) . "\n";
            }
        }
    }
}

function formatPhpCode($code) {
    // Удаляем все > 1 пробела подряд
    $code = preg_replace('/\s+/', ' ', $code);
    
    // Заменяем { на {\n
    $code = str_replace('{', "{\n", $code);
    
    // Заменяем } на \n}
    $code = str_replace('}', "\n}", $code);
    
    // Заменяем ; на ;\n
    $code = str_replace(';', ";\n", $code);
    
    // Удаляем пустые строки
    $lines = explode("\n", $code);
    $lines = array_filter($lines, fn($l) => trim($l) !== '');
    
    // Добавляем отступы (simple indentation)
    $indent = 0;
    $result = [];
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Уменьшаем отступ для закрывающихся скобок
        if (strpos($line, '}') === 0) {
            $indent = max(0, $indent - 1);
        }
        
        $result[] = str_repeat('    ', $indent) . $line;
        
        // Увеличиваем отступ для открывающихся скобок
        if (strpos($line, '{') !== false) {
            $indent++;
        }
    }
    
    return implode("\n", $result) . "\n";
}

echo "\nTotal compressed: " . count($compressed) . "\n";
echo "Fixed: $fixed\n";

if (!empty($compressed)) {
    echo "\nCompressed files:\n";
    foreach ($compressed as $f) {
        echo "  - $f\n";
    }
}
