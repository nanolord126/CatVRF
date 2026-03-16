<?php
declare(strict_types=1);

/**
 * Completely rewrite all Pages files with correct structure
 */

$resourcesDir = 'app/Filament/Tenant/Resources';
$fixed = 0;

// Step 1: Find all Resource.php files and build a map by directory
$resourcesByDir = [];

function scanResources($dir) {
    global $resourcesByDir, $resourcesDir;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $path = "$dir/$item";
        if (is_file($path) && str_ends_with($item, 'Resource.php')) {
            // Get parent directory relative path
            $parentRel = str_replace("$resourcesDir/", '', $dir);
            if (!isset($resourcesByDir[$parentRel])) {
                $resourcesByDir[$parentRel] = [];
            }
            $resourcesByDir[$parentRel][] = basename($item, '.php');
        } elseif (is_dir($path)) {
            scanResources($path);
        }
    }
}

scanResources($resourcesDir);

echo "Resources by directory:\n";
foreach ($resourcesByDir as $dir => $resources) {
    echo "  /$dir: " . implode(', ', $resources) . "\n";
}
echo "\n";

// Step 2: Find all Pages directories and fix them
function fixPages($dir) {
    global $fixed, $resourcesDir, $resourcesByDir;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $path = "$dir/$item";
        if ($item === 'Pages' && is_dir($path)) {
            // Found a Pages directory!
            // Get the parent directory info
            $parentDirFull = dirname($path);
            $parentDirRel = str_replace("$resourcesDir/", '', $parentDirFull);
            
            // Find Resource files in this directory or parent
            $resourceClass = null;
            $resourceNamespace = null;
            
            if (isset($resourcesByDir[$parentDirRel])) {
                $resourceClass = $resourcesByDir[$parentDirRel][0];
                $resourceNamespace = "App\\Filament\\Tenant\\Resources\\" . str_replace('/', '\\', $parentDirRel);
            } else {
                // Try looking in parent directories
                $parts = explode('/', $parentDirRel);
                while (count($parts) > 0) {
                    $searchDir = implode('/', $parts);
                    if (isset($resourcesByDir[$searchDir])) {
                        $resourceClass = $resourcesByDir[$searchDir][0];
                        $resourceNamespace = "App\\Filament\\Tenant\\Resources\\" . str_replace('/', '\\', $searchDir);
                        break;
                    }
                    array_pop($parts);
                }
            }
            
            if (!$resourceClass) {
                echo "⚠️  Cannot find Resource for Pages: $path\n";
                return;
            }
            
            echo "Processing: $path (Resource: $resourceClass)\n";
            
            // Fix all PHP files in this Pages directory
            fixPageFiles($path, $resourceClass, $resourceNamespace);
        } elseif (is_dir($path)) {
            fixPages($path);
        }
    }
}

function fixPageFiles($pagesDir, $resourceClass, $resourceNamespace) {
    global $fixed;
    
    $items = scandir($pagesDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $path = "$pagesDir/$item";
        if (is_file($path) && str_ends_with($item, '.php')) {
            $content = file_get_contents($path);
            
            // Extract namespace from file
            preg_match('/^namespace\s+(App\\\\Filament\\\\Tenant\\\\Resources\\\\[^;]+\\\\Pages);/m', $content, $matches);
            if (!isset($matches[1])) {
                return;
            }
            
            $fileNamespace = $matches[1];
            $className = basename($path, '.php');
            
            // Determine parent class
            $parentClass = 'CreateRecord';
            if (str_contains($className, 'Edit')) {
                $parentClass = 'EditRecord';
            } elseif (str_contains($className, 'List')) {
                $parentClass = 'ListRecords';
            } elseif (str_contains($className, 'View')) {
                $parentClass = 'ViewRecord';
            }
            
            // Build new content
            $newContent = <<<PHP
<?php

declare(strict_types=1);

namespace {$fileNamespace};

use {$resourceNamespace}\\{$resourceClass};
use Filament\\Resources\\Pages\\{$parentClass};

final class {$className} extends {$parentClass}
{
    protected static string \$resource = {$resourceClass}::class;
}
PHP;
            
            file_put_contents($path, $newContent);
            $fixed++;
            echo "  ✅ Fixed: $className\n";
        } elseif (is_dir($path)) {
            fixPageFiles($path, $resourceClass, $resourceNamespace);
        }
    }
}

fixPages($resourcesDir);

echo "\n✅ Total fixed: $fixed files\n";
