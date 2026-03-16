<?php

declare(strict_types=1);

$pagesDir = 'app/Filament/Tenant/Resources';

// Функция рекурсивного поиска Pages
function findPages($dir)
{
    $files = [];
    
    if (!is_dir($dir)) {
        return $files;
    }
    
    $dh = @opendir($dir);
    if (!$dh) {
        return $files;
    }
    
    while (($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            if (basename($path) === 'Pages') {
                // Это папка Pages
                $pagesHandle = @opendir($path);
                if ($pagesHandle) {
                    while (($page = readdir($pagesHandle)) !== false) {
                        if (str_ends_with($page, '.php')) {
                            $files[] = $path . '/' . $page;
                        }
                    }
                    closedir($pagesHandle);
                }
            } else {
                $files = array_merge($files, findPages($path));
            }
        }
    }
    closedir($dh);
    
    return $files;
}

$pages = findPages($pagesDir);

foreach ($pages as $path) {
    $content = file_get_contents($path);
    
    // Извлекаем имя ресурса из пути
    if (preg_match('/Resources\/([^\/]+)\/Pages\/([^\/]+)\.php/', $path, $m)) {
        $resourceName = $m[1]; // WishlistResource
        $pageName = $m[2]; // CreateWishlist
        
        // Определяем parent class
        if (str_starts_with($pageName, 'Create')) {
            $parentClass = 'CreateRecord';
            $use = 'use Filament\\Resources\\Pages\\CreateRecord;';
        } elseif (str_starts_with($pageName, 'Edit')) {
            $parentClass = 'EditRecord';
            $use = 'use Filament\\Resources\\Pages\\EditRecord;';
        } elseif (str_starts_with($pageName, 'List')) {
            $parentClass = 'ListRecords';
            $use = 'use Filament\\Resources\\Pages\\ListRecords;';
        } elseif (str_starts_with($pageName, 'Manage')) {
            $parentClass = 'ManageRecords';
            $use = 'use Filament\\Resources\\Pages\\ManageRecords;';
        } elseif (str_starts_with($pageName, 'View')) {
            $parentClass = 'ViewRecord';
            $use = 'use Filament\\Resources\\Pages\\ViewRecord;';
        } else {
            continue; // Пропускаем неизвестные страницы
        }
        
        // Создаём правильное содержимое
        $ns = 'namespace App\\Filament\\Tenant\\Resources\\' . $resourceName . '\\Pages;';
        $useResource = 'use App\\Filament\\Tenant\\Resources\\' . $resourceName . ';';
        
        $newContent = "<?php\n\ndeclare(strict_types=1);\n\n{$ns}\n\n{$useResource}\n{$use}\n\nfinal class {$pageName} extends {$parentClass}\n{\n    protected static string \$resource = {$resourceName}::class;\n}\n";
        
        file_put_contents($path, $newContent);
        echo ".";
    }
}

echo "\n✅ All Pages fixed\n";
