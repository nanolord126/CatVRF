#!/usr/bin/env php
<?php
/**
 * ФИНАЛЬНАЯ распаковка миграций используя PHP tokenizer для правильности
 */

function beautyFormatPhp($code) {
    $tokens = token_get_all($code);
    if ($tokens === false) return $code;
    
    $result = '<?php' . "\n\n";
    $indent = 0;
    $skipNextSpace = false;
    $prevTokenType = null;
    
    for ($i = 0; $i < count($tokens); $i++) {
        $token = $tokens[$i];
        
        if (!is_array($token)) {
            // Однобайтовый токен
            $char = $token;
            
            switch ($char) {
                case '{':
                    $result .= " {\n";
                    $indent++;
                    $skipNextSpace = true;
                    break;
                    
                case '}':
                    $indent = max(0, $indent - 1);
                    if (!str_ends_with(rtrim($result), '}')) {
                        $result = rtrim($result) . "\n";
                    }
                    $result .= str_repeat("    ", $indent) . "}\n";
                    $skipNextSpace = true;
                    break;
                    
                case ';':
                    $result .= ";";
                    // Если это конец блока функции, не добавляем переносы
                    if ($i + 1 < count($tokens)) {
                        $nextToken = $tokens[$i + 1];
                        if (is_array($nextToken) && $nextToken[0] == T_WHITESPACE) {
                            $result .= "\n";
                            $skipNextSpace = true;
                        } else {
                            $result .= "\n";
                        }
                    }
                    break;
                    
                case '(':
                case ')':
                case ',':
                case '[':
                case ']':
                    $result .= $char;
                    break;
                    
                default:
                    $result .= $char;
            }
            
            $prevTokenType = null;
            continue;
        }
        
        // Массивный токен (ключевое слово, идентификатор и т.д.)
        list($type, $text, $line) = $token;
        
        switch ($type) {
            case T_OPEN_TAG:
                // Уже добавили выше
                break;
                
            case T_NAMESPACE:
            case T_USE:
                if (!str_ends_with($result, "\n")) {
                    $result .= "\n";
                }
                $result .= $text;
                break;
                
            case T_CLASS:
            case T_FUNCTION:
            case T_RETURN:
            case T_IF:
            case T_FOREACH:
            case T_FOR:
            case T_WHILE:
            case T_SWITCH:
            case T_TRY:
            case T_CATCH:
            case T_FINAL:
            case T_PUBLIC:
            case T_PRIVATE:
            case T_PROTECTED:
                if ($prevTokenType !== T_WHITESPACE && !str_ends_with($result, ' ')) {
                    if (!str_ends_with($result, "\n")) {
                        $result .= "\n" . str_repeat("    ", $indent);
                    }
                }
                $result .= $text . " ";
                break;
                
            case T_WHITESPACE:
                if (!$skipNextSpace) {
                    // Сохраняем только одиночный пробел
                    if (!str_ends_with($result, " ") && !str_ends_with($result, "\n")) {
                        $result .= " ";
                    }
                }
                $skipNextSpace = false;
                break;
                
            case T_COMMENT:
            case T_DOC_COMMENT:
                if (!str_ends_with($result, "\n")) {
                    $result .= "\n" . str_repeat("    ", $indent);
                }
                $result .= $text . "\n";
                $skipNextSpace = true;
                break;
                
            case T_VARIABLE:
            case T_STRING:
                $result .= $text;
                break;
                
            default:
                $result .= $text;
        }
        
        $prevTokenType = $type;
    }
    
    // Убираем лишние пробелы в конце
    $result = rtrim($result) . "\n";
    
    // Убираем множественные переносы
    $result = preg_replace('/\n{3,}/', "\n\n", $result);
    
    // Убираем пробелы перед запятыми и точками запятой
    $result = preg_replace('/\s+([,;])/', '$1', $result);
    
    return $result;
}

$dirs = [
    __DIR__ . '/database/migrations',
    __DIR__ . '/database/migrations/tenant',
];

$count = 0;

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        if (substr($file, -4) !== '.php') continue;
        
        $path = $dir . '/' . $file;
        $content = file_get_contents($path);
        
        // Если содержит миграцию
        if (strpos($content, 'extends Migration') === false) {
            continue;
        }
        
        $formatted = beautyFormatPhp($content);
        file_put_contents($path, $formatted);
        $count++;
        echo ".";
    }
}

echo "\n✅ Переформатировано: $count\n";
