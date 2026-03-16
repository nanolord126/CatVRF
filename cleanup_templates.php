<?php

declare(strict_types=1);

// Скрипт для удаления всех PHP файлов со следствиями {NAMESPACE}
$pagesDir = __DIR__ . '/app/Filament/Tenant/Resources/Pages';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pagesDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$deleted = 0;
$checked = 0;

foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $checked++;
        $content = file_get_contents($file->getRealPath());
        
        // Проверяем, содержит ли файл шаблонные переменные
        if (str_contains($content, '{NAMESPACE}') || 
            str_contains($content, '{RESOURCE_CLASS}') ||
            str_contains($content, '{RESOURCE_NAME}') ||
            str_contains($content, '{MODEL_NAME}')) {
            
            unlink($file->getRealPath());
            $deleted++;
            echo "Deleted: " . $file->getRealPath() . "\n";
        }
    }
}

echo "\nTotal checked: $checked\n";
echo "Total deleted: $deleted\n";
