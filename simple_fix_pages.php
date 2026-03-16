<?php
declare(strict_types=1);

/**
 * Простой и надежный фиксер для всех Pages ссылок
 */

$resourcesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$fixed = 0;

function fixResourcesRecursive($dir) {
    global $fixed;
    
    $items = @scandir($dir);
    if (!$items) return;
    
    foreach ($items as $item) {
        if (in_array($item, ['.', '..', 'Pages', 'Common'])) {
            continue;
        }
        
        $path = "$dir/$item";
        
        if (is_file($path) && str_ends_with($item, 'Resource.php')) {
            $content = file_get_contents($path);
            
            // Извлечь namespace
            if (!preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                continue;
            }
            
            $ns = trim($matches[1]);
            $resourceBase = str_replace('Resource.php', '', $item);
            
            // Заменить Pages\ClassName на полный путь
            $content = preg_replace(
                '/Pages\\\\([A-Za-z]+)::route\(',
                "\\\\$ns\\\\Pages\\\\$1::route(",
                $content
            );
            
            file_put_contents($path, $content);
            $fixed++;
            echo "✅ Fixed: $item\n";
        }
        
        if (is_dir($path)) {
            fixResourcesRecursive($path);
        }
    }
}

fixResourcesRecursive($resourcesDir);
echo "\n✅ Total: $fixed resources fixed\n";
