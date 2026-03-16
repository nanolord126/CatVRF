<?php
declare(strict_types=1);

/**
 * Properly fix all Pages files with correct Resource references
 */

$resourcesDir = 'app/Filament/Tenant/Resources';
$fixed = 0;

// Find all Pages directories recursively
function findPagesDirs($dir, &$pages = []) {
    $items = scandir($dir);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = "$dir/$item";
        if (is_dir($path)) {
            if ($item === 'Pages') {
                $pages[] = $path;
            } else {
                findPagesDirs($path, $pages);
            }
        }
    }
}

// Find all .php files in a directory (recursively)
function findPhpFiles($dir, &$files = []) {
    $items = scandir($dir);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = "$dir/$item";
        if (is_dir($path)) {
            findPhpFiles($path, $files);
        } elseif (str_ends_with($item, '.php')) {
            $files[] = $path;
        }
    }
}

$pagesDirs = [];
findPagesDirs($resourcesDir, $pagesDirs);

echo "Found " . count($pagesDirs) . " Pages directories\n\n";

foreach ($pagesDirs as $pagesDir) {
    // Get the parent directory (where Resource.php should be)
    $parentDir = dirname($pagesDir);
    $parentName = basename($parentDir);
    
    // Find Resource file in parent directory
    $resourceFile = null;
    $resourceClass = null;
    
    // Method 1: Look for *Resource.php in parent directory
    $items = scandir($parentDir);
    foreach ($items as $item) {
        if (str_ends_with($item, 'Resource.php')) {
            $resourceFile = "$parentDir/$item";
            $resourceClass = basename($item, '.php');
            break;
        }
    }
    
    if (!$resourceClass) {
        // Method 2: Try parent's parent directory
        $grandParentDir = dirname($parentDir);
        $items = scandir($grandParentDir);
        foreach ($items as $item) {
            if (str_ends_with($item, 'Resource.php')) {
                $resourceFile = "$grandParentDir/$item";
                $resourceClass = basename($item, '.php');
                break;
            }
        }
    }
    
    if (!$resourceClass) {
        echo "⚠️  Cannot find Resource for Pages dir: $pagesDir\n";
        continue;
    }
    
    // Build use statement
    $relativePath = str_replace("$resourcesDir\\", '', str_replace('/', '\\', str_replace('\\', '/', $resourceFile)));
    $relativePath = str_replace('.php', '', $relativePath);
    $useStatement = "use App\\Filament\\Tenant\\Resources\\$relativePath;";

    // Find all .php files in this Pages directory (recursively)
    $phpFiles = [];
    findPhpFiles($pagesDir, $phpFiles);

    foreach ($phpFiles as $pageFile) {
        $content = file_get_contents($pageFile);
        
        $newContent = $content;
        
        // Replace any malformed use statements with the correct one
        // Pattern: use App\Filament\Tenant\Resources...anything...;
        $newContent = preg_replace(
            '/use\s+App\\\\Filament\\\\Tenant\\\\Resources[^;]*;/',
            $useStatement,
            $newContent
        );
        
        // Fix the $resource property
        $newContent = preg_replace(
            '/protected\s+static\s+string\s+\$resource\s*=\s*[^;]*;/',
            "protected static string \$resource = $resourceClass::class;",
            $newContent
        );
        
        if ($newContent !== $content) {
            file_put_contents($pageFile, $newContent);
            $fixed++;
            echo "✅ Fixed: " . basename($pageFile) . " (Resource: $resourceClass)\n";
        }
    }
}

echo "\n✅ Total fixed: $fixed files\n";
