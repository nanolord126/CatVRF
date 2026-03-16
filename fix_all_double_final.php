<?php
// Найти и исправить все файлы с double 'final' modifier

$pattern = 'C:\\opt\\kotvrf\\CatVRF\\app\\Filament\\**\\*.php';
$files = glob($pattern, GLOB_BRACE);

$fixed = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Ищем double final
    if (preg_match('/final\s+final\s+class/', $content)) {
        // Удаляем первый final перед вторым
        $newContent = preg_replace('/(\s+)final\s+final(\s+class)/', '$1final$2', $content);
        file_put_contents($file, $newContent);
        $fixed++;
        echo "Fixed: " . basename($file) . "\n";
    }
    
    // Удаляем BOM если есть
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $newContent = substr($content, 3);
        file_put_contents($file, $newContent);
        echo "BOM removed: " . basename($file) . "\n";
    }
}

echo "\nTotal fixed: $fixed\n";
