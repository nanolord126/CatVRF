<?php
declare(strict_types=1);

/**
 * Полная ревизия и исправление всех Resources Pages ссылок
 */

$resourcesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$fixed = 0;

function processResources($dir, $depth = 0) {
    global $fixed;
    
    $items = @scandir($dir);
    if (!is_array($items)) return;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === 'Pages' || $item === 'Common') {
            continue;
        }
        
        $path = "$dir/$item";
        
        // Process Resource files
        if (is_file($path) && str_ends_with($item, 'Resource.php')) {
            $content = file_get_contents($path);
            $original = $content;
            
            // Extract namespace and resource name
            if (!preg_match('/namespace ([^;]+);/', $content, $nsMatch)) {
                continue;
            }
            
            $namespace = $nsMatch[1];
            $resourceName = str_replace('Resource.php', '', $item);
            
            // Find getPages method and rewrite it correctly
            if (preg_match('/public static function getPages\(\): array\s*\{.*?\n\s*\}/s', $content, $match)) {
                // Determine Page class names based on resource name
                $singular = preg_replace('/(Resource|Product|Service|Course)$/', '', $resourceName);
                
                if (str_ends_with($resourceName, 'Product')) {
                    $plural = $resourceName . 's';
                } elseif (str_ends_with($resourceName, 'Service')) {
                    $plural = $resourceName . 's';
                } elseif (str_ends_with($resourceName, 'Course')) {
                    $plural = $resourceName . 's';
                } else {
                    $plural = $resourceName . 's';
                }
                
                // Generate new getPages method
                $newGetPages = <<<'PHP'
public static function getPages(): array
    {
        return [
            'index' => \\PHP;
                
                $newGetPages .= $namespace . '\\' . $resourceName . 'Resource\\Pages\\List' . $plural . '::route(\'/\'),';
                $newGetPages .= "\n            'create' => \\" . $namespace . '\\' . $resourceName . 'Resource\\Pages\\Create' . $singular . '::route(\'/create\'),';
                $newGetPages .= "\n            'edit' => \\" . $namespace . '\\' . $resourceName . 'Resource\\Pages\\Edit' . $singular . '::route(\'/{record}/edit\'),';
                $newGetPages .= "\n            'view' => \\" . $namespace . '\\' . $resourceName . 'Resource\\Pages\\View' . $singular . '::route(\'/{record}\'),";
                $newGetPages .= "\n        ];\n    }";
                
                $content = preg_replace(
                    '/public static function getPages\(\): array\s*\{.*?\n\s*\}/s',
                    $newGetPages,
                    $content
                );
            }
            
            if ($content !== $original) {
                file_put_contents($path, $content);
                $fixed++;
                echo "✅ Fixed: $namespace\\$resourceName\n";
            }
        }
        
        // Recurse
        if (is_dir($path)) {
            processResources($path, $depth + 1);
        }
    }
}

processResources($resourcesDir);
echo "\n✅ Total fixed: $fixed\n";
