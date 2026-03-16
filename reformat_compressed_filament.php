<?php
// Распаковка и переформатирование всех сжатых Filament файлов

$path = 'C:\\opt\\kotvrf\\CatVRF\\app\\Filament';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($path),
    RecursiveIteratorIterator::SELF_FIRST
);

$formatted = 0;

foreach ($files as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    
    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    
    // Проверяем, сжат ли файл (< 10 строк)
    $lines = explode("\n", $content);
    if (count($lines) < 10) {
        // Используем PHP tokenizer для правильного форматирования
        $tokens = token_get_all($content);
        $formatted_code = '';
        $indent_level = 0;
        $last_token_type = 0;
        
        foreach ($tokens as $token) {
            if (is_array($token)) {
                [$type, $value, $line] = $token;
                $text = $value;
            } else {
                $type = 0;
                $text = $token;
            }
            
            // Уменьшаем отступ перед }
            if ($text === '}') {
                $indent_level = max(0, $indent_level - 1);
                $formatted_code .= str_repeat('    ', $indent_level) . $text . "\n";
            } 
            // Пропускаем whitespace
            elseif ($type === T_WHITESPACE) {
                if (strpos($text, "\n") !== false) {
                    $formatted_code .= "\n";
                }
            }
            // Обычные токены
            else {
                if (substr(trim($formatted_code), -1) === "\n" && trim($text) !== '') {
                    $formatted_code .= str_repeat('    ', $indent_level);
                } elseif (trim($formatted_code) !== '' && trim($text) !== '' && 
                         !in_array($text, ['{', '}', ';', ',', '(', ')'])) {
                    // Добавляем пробел перед токеном если нужно
                    if (substr(trim($formatted_code), -1) !== ' ' && 
                        substr(trim($formatted_code), -1) !== '(') {
                        $formatted_code .= ' ';
                    }
                }
                
                $formatted_code .= $text;
                
                // Увеличиваем отступ после {
                if ($text === '{') {
                    $indent_level++;
                    $formatted_code .= "\n";
                }
                // Добавляем новую строку после }
                elseif ($text === ';') {
                    $formatted_code .= "\n";
                }
            }
        }
        
        file_put_contents($filePath, $formatted_code, FILE_TEXT);
        $formatted++;
        echo "Formatted: " . basename($filePath) . "\n";
    }
}

echo "\nTotal formatted: $formatted\n";
