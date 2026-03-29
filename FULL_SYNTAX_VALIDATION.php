<?php
declare(strict_types=1);

/**
 * FULL SYNTAX VALIDATION - 215 ресурсов
 * Находит и отчитывает о всех PHP parse errors
 */

$errors = [];
$valid = [];
$checked = 0;

$iterator = new RecursiveDirectoryIterator('app/Filament/Tenant/Resources');
$recursiveIterator = new RecursiveIteratorIterator($iterator);
$regex = new RegexIterator($recursiveIterator, '/.*Resource\.php$/');

foreach ($regex as $file) {
    $path = $file->getRealPath();
    if (strpos($path, 'Pages') !== false) continue;
    
    $checked++;
    
    // Запустить php -l через system
    $output = [];
    $returnVar = 0;
    exec('php -l "' . $path . '" 2>&1', $output, $returnVar);
    
    if ($returnVar !== 0 || strpos(implode("\n", $output), 'error') !== false) {
        $errors[] = [
            'file' => basename($path),
            'path' => $path,
            'error' => implode("\n", $output),
        ];
        echo "❌ " . basename($path) . "\n";
    } else {
        $valid[] = basename($path);
        echo "✅ " . basename($path) . "\n";
    }
}

echo "\n\n=== РЕЗУЛЬТАТЫ ===\n";
echo "Проверено: $checked файлов\n";
echo "Валидных: " . count($valid) . "\n";
echo "С ошибками: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\n\n=== НАЙДЕННЫЕ ОШИБКИ ===\n";
    foreach ($errors as $err) {
        echo "\n📌 " . $err['file'] . "\n";
        echo "   " . trim($err['error']) . "\n";
    }
    
    // Сохранить в файл
    file_put_contents('SYNTAX_ERRORS.txt', "ОШИБКИ СИНТАКСИСА (" . count($errors) . " файлов):\n\n");
    foreach ($errors as $err) {
        file_put_contents('SYNTAX_ERRORS.txt', 
            "❌ " . $err['file'] . "\n" .
            "   Path: " . $err['path'] . "\n" .
            "   Error: " . trim($err['error']) . "\n\n",
            FILE_APPEND);
    }
    
    echo "\n📄 Сохранено в: SYNTAX_ERRORS.txt\n";
}
