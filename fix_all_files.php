<?php

declare(strict_types=1);

function fixPageFile(string $filePath): void
{
    $content = file_get_contents($filePath);

    // Удаляем BOM если есть
    if (str_starts_with($content, "\xef\xbb\xbf")) {
        $content = substr($content, 3);
    }

    // Конвертируем все в CRLF
    $content = str_replace("\r\n", "\n", $content);
    $content = str_replace("\n", "\r\n", $content);

    // Сохраняем без BOM в UTF-8
    $utf8NoBom = new \SplFileObject($filePath, 'w');
    $utf8NoBom->fwrite($content);
}

function processDirectory(string $dir, callable $callback): int
{
    $count = 0;
    if (!is_dir($dir)) {
        return 0;
    }

    $dh = @opendir($dir);
    if (!$dh) {
        return 0;
    }

    while (($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            $count += processDirectory($path, $callback);
        } elseif (str_ends_with($file, '.php')) {
            $callback($path);
            $count++;
        }
    }
    closedir($dh);

    return $count;
}

echo "Fixing all PHP files in Resources...\n";
$count = processDirectory('app/Filament/Tenant', function ($path) {
    fixPageFile($path);
});

echo "✅ Fixed $count files\n";
