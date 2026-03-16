<?php
declare(strict_types=1);

/**
 * Find the correct Resource file for each Pages directory
 * Map Pages directories to their Resource classes
 */

$resourcesDir = 'app/Filament/Tenant/Resources';

// Find all Resource files
$resourceFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourcesDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php' || !str_ends_with($file->getBasename(), 'Resource.php')) {
        continue;
    }
    
    $path = $file->getPathname();
    $relativePath = str_replace("$resourcesDir/", '', $path);
    $relativePath = str_replace('\\', '/', $relativePath);
    $relativePath = str_replace('.php', '', $relativePath);
    
    $resourceFiles[$relativePath] = $file->getBasename('.php');
}

echo "Found " . count($resourceFiles) . " Resource files:\n";
foreach (array_slice($resourceFiles, 0, 20) as $path => $name) {
    echo "  $path => $name\n";
}
echo "  ...\n\n";

// Now find all Pages directories
$pagesDirs = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourcesDir),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $dir) {
    if ($dir->getBasename() === 'Pages' && $dir->isDir()) {
        $pagesDirs[] = $dir->getPathname();
    }
}

echo "Found " . count($pagesDirs) . " Pages directories\n\n";

// For each Pages directory, find its Resource
$fixed = 0;
foreach ($pagesDirs as $pagesDir) {
    // Get parent directory of Pages
    $parentDir = dirname($pagesDir);
    $parentName = basename($parentDir);
    
    // Look for corresponding Resource file
    // Strategy: find Resource file in the same directory as Pages
    $resourcesInParent = glob("$parentDir/*Resource.php");
    
    if (empty($resourcesInParent)) {
        // Try parent directory
        $grandParentDir = dirname($parentDir);
        $resourcesInParent = glob("$grandParentDir/*Resource.php");
        if (empty($resourcesInParent)) {
            // Try to find by matching name
            foreach ($resourceFiles as $path => $name) {
                if (str_contains($path, $parentName)) {
                    $resourceClass = $name;
                    break;
                }
            }
            if (!isset($resourceClass)) {
                echo "⚠️  Cannot find Resource for Pages dir: $pagesDir\n";
                continue;
            }
        } else {
            $resourceClass = basename($resourcesInParent[0], '.php');
        }
    } else {
        $resourceClass = basename($resourcesInParent[0], '.php');
    }
    
    // Build use statement path
    $parentRelative = str_replace("$resourcesDir/", '', $parentDir);
    $useStatement = "use App\\Filament\\Tenant\\Resources\\$parentRelative\\$resourceClass;";

    // Fix all Pages in this directory
    $pageFiles = [];
    $pagesIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pagesDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($pagesIterator as $file) {
        if ($file->getExtension() === 'php') {
            $pageFiles[] = $file->getPathname();
        }
    }

    foreach ($pageFiles as $pageFile) {
        $content = file_get_contents($pageFile);

        // Replace any use statement that looks wrong
        $newContent = preg_replace(
            '/use App\\\\Filament\\\\Tenant\\\\Resources[^;]*;/',
            $useStatement,
            $content
        );

        // Also update the $resource property
        $newContent = preg_replace(
            '/protected static string \$resource = [^;]+;/',
            "protected static string \$resource = $resourceClass::class;",
            $newContent
        );

        if ($newContent !== $content) {
            file_put_contents($pageFile, $newContent);
            $fixed++;
            echo "✅ Fixed: " . basename($pageFile) . "\n";
        }
    }
}

echo "\n✅ Total fixed: $fixed files\n";
