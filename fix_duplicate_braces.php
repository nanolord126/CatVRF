<?php

declare(strict_types=1);

// Скрипт для исправления дублирования закрывающих скобок в Resource файлах
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

        // Ищем паттерн: }
        //         ];
        //     }
        // }
        // Это означает дублирование getPages() метода
        
        // Удаляем дублирование: просто удаляем вторую пару скобок в конце
        $content = preg_replace(
            '/^(\s*)}\s*}\s*\n\s*}\s*}\s*$/m',
            '$1}' . "\n" . '}',
            $content
        );

        // Более надежный способ: удалить все "        ];" перед концом файла
        $content = preg_replace(
            '/}\s*\n\s*}\s*\n\s*}\s*$/',
            '}' . "\n" . '}',
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
