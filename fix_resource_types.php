<?php

declare(strict_types=1);

// Скрипт для исправления типов параметров в Resource файлах
$resourceDir = __DIR__ . '/app/Filament/Tenant/Resources';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourceDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$fixed = 0;

foreach ($files as $file) {
    if ($file->getExtension() === 'php' && str_ends_with($file->getFilename(), 'Resource.php')) {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        $original = $content;

        // Заменяем конкретные типы моделей на mixed в методах canEdit, canDelete, canView
        // Pattern: public static function canEdit(SomeModel $record)
        // Replace: public static function canEdit(mixed $record)
        
        $content = preg_replace(
            '/public\s+static\s+function\s+(can(?:Edit|Delete|View|Restore|ForceDelete))\s*\(\s*[A-Za-z_][A-Za-z0-9_\\\\]*\s+(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*\)/m',
            'public static function $1(mixed $2)',
            $content
        );

        if ($content !== $original) {
            file_put_contents($path, $content);
            $fixed++;
            echo "Fixed: {$file->getFilename()}\n";
        }
    }
}

echo "\nTotal files fixed: $fixed\n";

