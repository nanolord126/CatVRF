<?php

declare(strict_types=1);

// Скрипт для удаления дублирующихся импортов
$resourceDir = __DIR__ . '/app/Filament/Tenant/Resources';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourceDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$fixed = 0;

foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        $original = $content;

        // Найти все use imports и удалить дубликаты
        $lines = explode("\n", $content);
        $useLines = [];
        $newLines = [];
        
        foreach ($lines as $line) {
            // Проверяем, это use statement?
            if (preg_match('/^use\s+/', $line)) {
                // Если это дубликат, пропускаем
                if (in_array($line, $useLines)) {
                    continue;
                }
                $useLines[] = $line;
                $newLines[] = $line;
            } else {
                $newLines[] = $line;
            }
        }
        
        $content = implode("\n", $newLines);

        if ($content !== $original) {
            file_put_contents($path, $content);
            $fixed++;
            echo "Fixed: {$file->getFilename()}\n";
        }
    }
}

echo "\nTotal files fixed: $fixed\n";
