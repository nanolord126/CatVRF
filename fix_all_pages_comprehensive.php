<?php
declare(strict_types=1);

/**
 * Fix all Pages files with corrupted import statements
 * Recreate them with proper structure and production formatting
 */

$resourcesDir = 'app/Filament/Tenant/Resources';
$fixed = 0;

// Find all directories containing Pages
function scanResourceDirectories($dir, $basePath = '') {
    $resources = [];
    $items = @scandir($dir);
    
    if ($items === false) {
        return $resources;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === 'Pages') {
            continue;
        }

        $path = $dir . '/' . $item;
        $relativePath = $basePath ? $basePath . '/' . $item : $item;

        if (is_dir($path)) {
            // Check if this directory has a Pages subdirectory
            if (is_dir($path . '/Pages')) {
                $pagesDir = $path . '/Pages';
                $pageFiles = [];
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($pagesDir)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $pageFiles[] = $file->getPathname();
                    }
                }
                
                foreach ($pageFiles as $pageFile) {
                    $resources[] = [
                        'path' => $pageFile,
                        'resourceName' => $item,
                        'resourcePath' => $relativePath,
                        'fileName' => basename($pageFile),
                    ];
                }
            }

            // Recurse into subdirectories
            $subResources = scanResourceDirectories($path, $relativePath);
            $resources = array_merge($resources, $subResources);
        }
    }

    return $resources;
}

$resources = scanResourceDirectories($resourcesDir);

echo "Found " . count($resources) . " Pages files to check/fix.\n\n";

foreach ($resources as $info) {
    $filePath = $info['path'];
    $resourceName = $info['resourceName'];
    $resourcePath = $info['resourcePath'];
    $content = file_get_contents($filePath);

    // Check if file has corrupted use statements
    if (strpos($content, 'use App\Filament\Tenant\Resources\.\Pages;') === false &&
        strpos($content, '::class;') === false) {
        // File is fine
        continue;
    }

    // Extract class name from filename
    $className = basename($filePath, '.php');
    
    // Determine parent class
    $parentClass = 'CreateRecord';
    if (str_contains($className, 'Edit')) {
        $parentClass = 'EditRecord';
    } elseif (str_contains($className, 'List')) {
        $parentClass = 'ListRecords';
    } elseif (str_contains($className, 'View')) {
        $parentClass = 'ViewRecord';
    }

    // Build correct use statement
    $useStatement = "use App\\Filament\\Tenant\\Resources\\$resourcePath\\$resourceName;";

    // Create correct file content
    $newContent = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources\\{$resourceName}\\Pages;

use App\\Filament\\Tenant\\Resources\\{$resourcePath}\\{$resourceName};
use Filament\\Resources\\Pages\\{$parentClass};

final class {$className} extends {$parentClass}
{
    protected static string \$resource = {$resourceName}::class;
}
PHP;

    file_put_contents($filePath, $newContent);
    $fixed++;
    echo "✅ Fixed: " . basename($filePath) . "\n";
}

echo "\n✅ Total fixed: $fixed files\n";
