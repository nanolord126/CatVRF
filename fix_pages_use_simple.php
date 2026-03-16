<?php
declare(strict_types=1);

/**
 * Fix corrupted use statements in Pages files.
 * Replaces invalid: use App\Filament\Tenant\Resources\.\Pages;
 * With correct path based on resource directory structure.
 */

$resourcesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$pagesToFix = [];
$fixed = 0;

// Find all Pages files
function findPagesFiles($dir, &$files = []) {
    $items = @scandir($dir);
    if ($items === false) {
        return $files;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            if ($item === 'Pages') {
                findPagesFilesInPages($path, $files);
            } else {
                findPagesFiles($path, $files);
            }
        }
    }

    return $files;
}

function findPagesFilesInPages($dir, &$files = []) {
    $items = @scandir($dir);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . '/' . $item;
        if (is_file($path) && str_ends_with($path, '.php')) {
            $files[] = $path;
        } elseif (is_dir($path)) {
            findPagesFilesInPages($path, $files);
        }
    }
}

findPagesFiles($resourcesDir, $pagesToFix);

echo "Found " . count($pagesToFix) . " Pages files.\n";

foreach ($pagesToFix as $filePath) {
    $content = file_get_contents($filePath);

    // Check if file has the corrupted use statement
    if (strpos($content, 'use App\Filament\Tenant\Resources\.\Pages;') === false) {
        continue;
    }

    // Extract resource name from path
    // Example: app/Filament/Tenant/Resources/ProductResource/Pages/ListProducts.php
    // Should become: use App\Filament\Tenant\Resources\ProductResource\Pages;

    $relativePath = str_replace($resourcesDir . '/', '', $filePath);
    $parts = explode('/', $relativePath);

    // Navigate up from Pages subdirectory to find resource name
    $resourceName = null;
    $pagesIndex = array_search('Pages', $parts);

    if ($pagesIndex > 0) {
        $resourceName = $parts[$pagesIndex - 1];
    }

    if ($resourceName === null) {
        echo "⚠️  Cannot extract resource name from: $filePath\n";
        continue;
    }

    // Check if there are subdirectories in the path
    $useStatement = 'use App\Filament\Tenant\Resources';
    for ($i = 0; $i < $pagesIndex - 1; $i++) {
        if ($parts[$i] !== 'Resources') {
            $useStatement .= '\\' . $parts[$i];
        }
    }
    $useStatement .= '\\' . $resourceName . '\Pages;';

    $newContent = str_replace(
        'use App\Filament\Tenant\Resources\.\Pages;',
        $useStatement,
        $content
    );

    if ($newContent !== $content) {
        file_put_contents($filePath, $newContent);
        $fixed++;
        echo "✅ Fixed: $filePath\n";
        echo "   → $useStatement\n";
    }
}

echo "\n✅ Fixed: $fixed files\n";
