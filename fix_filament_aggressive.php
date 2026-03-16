<?php
// Агрессивная переформатировка всех Filament файлов

$path = 'C:\\opt\\kotvrf\\CatVRF\\app\\Filament';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($path),
    RecursiveIteratorIterator::SELF_FIRST
);

$formatted = 0;
$errors = 0;

foreach ($files as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    
    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    
    // Удаляем BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    
    // Удаляем double final (basic regex)
    $newContent = preg_replace('/(\s)final\s+final(\s+)/', '$1final$2', $content);
    
    // Если есть изменения, пишем файл
    if ($newContent !== $content) {
        file_put_contents($filePath, $newContent, FILE_TEXT);
        $formatted++;
        echo "Fixed: " . basename($filePath) . "\n";
    }
}

echo "\nTotal fixed: $formatted\n";
