<?php

declare(strict_types=1);

// Параметры директории
$pagesDir = './app/Filament/Tenant/Resources';

// Функция для рекурсивного поиска файлов
function findPages($dir)
{
    $files = [];
    
    if (!is_dir($dir)) {
        return $files;
    }
    
    $handle = opendir($dir);
    if (!$handle) {
        return $files;
    }
    
    while (($file = readdir($handle)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            if (basename($path) === 'Pages') {
                // Это директория Pages, ищем php файлы
                $pagesHandle = opendir($path);
                while (($pageFile = readdir($pagesHandle)) !== false) {
                    if (substr($pageFile, -4) === '.php') {
                        $files[] = $path . '/' . $pageFile;
                    }
                }
                closedir($pagesHandle);
            } else {
                // Рекурсивный поиск
                $files = array_merge($files, findPages($path));
            }
        }
    }
    closedir($handle);
    
    return $files;
}

$pages = findPages($pagesDir);

echo "Found: " . count($pages) . " Pages\n";

$fixed = 0;
foreach ($pages as $path) {
    $content = file_get_contents($path);
    $original = $content;
    
    // Извлекаем имя ресурса из пути файла
    // app/Filament/Tenant/Resources/WishlistResource/Pages/CreateWishlist.php
    // => WishlistResource
    if (preg_match('/Resources\/([^\/]+)\/Pages\//', $path, $m)) {
        $resourceName = $m[1]; // WishlistResource
        
        // Заменяем неправильный импорт
        $content = str_replace(
            'use App\\Filament\\Tenant\\Resources\\app\\Filament\\Tenant\\Resources\\AiAssistantChatResource;',
            'use App\\Filament\\Tenant\\Resources\\' . $resourceName . ';',
            $content
        );
        
        // Заменяем resource
        $content = str_replace(
            'protected static string $resource = AiAssistantChatResource::class;',
            'protected static string $resource = ' . $resourceName . '::class;',
            $content
        );
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            $fixed++;
            echo ".";
        }
    }
}

echo "\n✅ Fixed: $fixed Pages\n";
