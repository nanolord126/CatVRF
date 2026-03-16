<?php

declare(strict_types=1);

$dir = 'app/Filament/Tenant/Resources';

function removeBomsRecursive($path): int
{
    $fixed = 0;
    
    if (!is_dir($path)) {
        return 0;
    }
    
    $dh = @opendir($path);
    if (!$dh) {
        return 0;
    }
    
    while (($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $fullPath = $path . '/' . $file;
        
        if (is_dir($fullPath)) {
            $fixed += removeBomsRecursive($fullPath);
        } elseif (str_ends_with($file, '.php')) {
            $content = file_get_contents($fullPath);
            
            // Удаляем BOM если есть
            if (str_starts_with($content, "\xef\xbb\xbf")) {
                $content = substr($content, 3);
            }
            
            // Конвертируем в CRLF
            $content = str_replace("\r\n", "\n", $content);
            $content = str_replace("\n", "\r\n", $content);
            
            // Пишем без BOM в UTF-8
            file_put_contents($fullPath, $content);
            $fixed++;
            
            if ($fixed % 10 === 0) {
                echo ".";
            }
        }
    }
    closedir($dh);
    
    return $fixed;
}

$fixed = removeBomsRecursive($dir);
echo "\n✅ Fixed: $fixed files\n";
