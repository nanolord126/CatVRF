<?php
declare(strict_types=1);

/**
 * Автоматическое исправление всех Pages ссылок во всех Resources
 */

$resourcesDir = __DIR__ . '/app/Filament/Tenant/Resources';

function scanAndFixResources($dir) {
    $items = scandir($dir);
    $fixed = 0;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === 'Pages' || $item === 'Common') {
            continue;
        }
        
        $path = "$dir/$item";
        
        // If it's a Resource file
        if (is_file($path) && str_ends_with($item, 'Resource.php')) {
            $resourceName = str_replace('Resource.php', '', $item);
            $content = file_get_contents($path);
            $original = $content;
            
            // Extract namespace
            if (preg_match('/namespace ([^;]+);/', $content, $matches)) {
                $namespace = $matches[1];
                
                // Fix Pages references in getPages() method
                $content = preg_replace(
                    '/Pages\\\\\\\\([A-Za-z]+)::route\(/',
                    '\\\\\\\\' . $namespace . '\\\\\\\\Pages\\\\\\\\$1::route(',
                    $content
                );
                
                // Also fix direct Page references
                $content = preg_replace(
                    '/(\'index\'|\'create\'|\'edit\'|\'view\') => Pages\\\\\\\\/',
                    '$1 => \\\\\\\\' . $namespace . '\\\\\\\\Pages\\\\\\\\',
                    $content
                );
            }
            
            if ($content !== $original) {
                file_put_contents($path, $content);
                $fixed++;
                echo "✅ Fixed: $resourceName\n";
            }
        }
        
        // Recurse into subdirectories
        if (is_dir($path)) {
            $fixed += scanAndFixResources($path);
        }
    }
    
    return $fixed;
}

$fixed = scanAndFixResources($resourcesDir);

echo "\n✅ Total Resources fixed: $fixed\n";
