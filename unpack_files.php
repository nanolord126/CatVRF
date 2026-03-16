<?php
/**
 * Распаковать все сжатые PHP файлы
 * Конвертирует одну строку кода в нормальный формат с отступами
 */

$files = explode("\n", trim(<<<'EOF'
app\Actions\Fortify\CreateNewUser.php
app\Actions\Fortify\PasswordValidationRules.php
app\Actions\Fortify\ResetUserPassword.php
app\Actions\Fortify\UpdateUserPassword.php
app\Actions\Fortify\UpdateUserProfileInformation.php
app\Console\Commands\AnalyzePerformanceMetricsCommand.php
app\Console\Commands\CleanupVideoCalls.php
app\Console\Commands\Common\SendHealthReminders.php
app\Console\Commands\MLRecalculateCommand.php
app\Console\Commands\ResetAiQuotas.php
app\Console\Commands\Security\SyncForbiddenDatabases.php
EOF
));

function unpackPhpFile($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    
    // Уже распакован?
    if (substr_count($content, "\n") > 50) {
        return false;
    }
    
    // Используем PHP tokenizer для правильной распаковки
    $tokens = @token_get_all($content);
    if (!$tokens) {
        return false;
    }
    
    $unpacked = '';
    $indent = 0;
    $lastWasKeyword = false;
    
    foreach ($tokens as $token) {
        if (is_array($token)) {
            list($type, $value) = $token;
            
            // Ключевые слова и структуры
            if (in_array($type, [T_CLASS, T_TRAIT, T_INTERFACE, T_NAMESPACE, T_FUNCTION, T_IF, T_FOREACH, T_FOR, T_WHILE, T_DO, T_SWITCH, T_TRY, T_CATCH, T_FINALLY])) {
                if ($unpacked && !str_ends_with($unpacked, "\n")) {
                    $unpacked .= "\n";
                }
                $unpacked .= str_repeat("    ", $indent) . token_name($type) . " ";
                $lastWasKeyword = true;
            } elseif ($type === T_WHITESPACE) {
                // Нормализуем пробелы
                if (!$lastWasKeyword && !str_ends_with($unpacked, "\n")) {
                    $unpacked .= " ";
                }
            } else {
                $unpacked .= $value;
                $lastWasKeyword = false;
            }
        } else {
            // Однобайтовые токены
            if ($token === '{') {
                $unpacked .= " {\n";
                $indent++;
            } elseif ($token === '}') {
                if ($unpacked && !str_ends_with($unpacked, "\n")) {
                    $unpacked .= "\n";
                }
                $indent = max(0, $indent - 1);
                $unpacked .= str_repeat("    ", $indent) . "}\n";
            } elseif ($token === ';') {
                $unpacked .= ";\n";
            } else {
                $unpacked .= $token;
            }
            $lastWasKeyword = false;
        }
    }
    
    // Запишем обратно
    file_put_contents($filePath, $unpacked);
    return true;
}

$count = 0;
$errors = [];
foreach ($files as $file) {
    $file = trim($file);
    if (!$file) continue;
    
    $path = __DIR__ . '/' . str_replace('\\', '/', $file);
    if (unpackPhpFile($path)) {
        $count++;
    } else {
        $errors[] = $file;
    }
}

echo "Распакованных файлов: $count\n";
if (!empty($errors)) {
    echo "Ошибок: " . count($errors) . "\n";
}
