<?php
/**
 * Правильная распаковка сжатых PHP миграций с использованием tokenizer
 */

function unpackPhpCode($code) {
    // Используем PHP tokenizer для правильной распаковки
    $tokens = @token_get_all($code);
    if (!$tokens) return $code;
    
    $result = '';
    $indent = 0;
    $prevWasKeyword = false;
    $inClass = false;
    
    foreach ($tokens as $i => $token) {
        if (is_array($token)) {
            list($type, $text, $line) = $token;
            
            switch ($type) {
                case T_OPEN_TAG:
                    $result .= $text . "\n";
                    break;
                    
                case T_NAMESPACE:
                case T_USE:
                    if ($result && !str_ends_with($result, "\n")) {
                        $result .= "\n";
                    }
                    $result .= $text;
                    $prevWasKeyword = true;
                    break;
                    
                case T_CLASS:
                case T_FINAL:
                case T_FUNCTION:
                case T_RETURN:
                case T_IF:
                case T_FOREACH:
                case T_FOR:
                case T_WHILE:
                case T_SWITCH:
                    if ($result && !str_ends_with($result, "\n")) {
                        $result .= "\n";
                    }
                    $result .= str_repeat("    ", $indent) . $text;
                    $prevWasKeyword = true;
                    break;
                    
                case T_WHITESPACE:
                    if (str_contains($text, "\n")) {
                        $result .= "\n";
                    } elseif (!$prevWasKeyword) {
                        $result .= " ";
                    }
                    break;
                    
                case T_COMMENT:
                case T_DOC_COMMENT:
                    if (!str_ends_with($result, "\n")) {
                        $result .= "\n" . str_repeat("    ", $indent);
                    }
                    $result .= $text . "\n";
                    break;
                    
                default:
                    $result .= $text;
                    $prevWasKeyword = false;
            }
        } else {
            // Однобайтовый токен
            $char = $token;
            
            if ($char === '{') {
                $result .= " {\n";
                $indent++;
            } elseif ($char === '}') {
                $indent = max(0, $indent - 1);
                if (!str_ends_with($result, "\n")) {
                    $result .= "\n";
                }
                $result .= str_repeat("    ", $indent) . "}\n";
            } elseif ($char === ';') {
                $result .= ";\n";
            } elseif ($char === ',') {
                $result .= ",\n" . str_repeat("    ", $indent + 1);
            } else {
                $result .= $char;
            }
            
            $prevWasKeyword = false;
        }
    }
    
    return $result;
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
        
        // Если сжато (менее 10 строк для файла миграции это очень мало)
        if ($lines < 10 && strlen($content) > 500) {
            $unpacked = unpackPhpCode($content);
            file_put_contents($file, $unpacked);
            $count++;
            
            echo basename($file) . " ✓\n";
        }
    }
}

echo "\n✅ Распакованных файлов: $count\n";
