<?php
declare(strict_types=1);

/**
 * Properly fix all Pages files with correct Resource references
 */

$resourcesDir = 'app/Filament/Tenant/Resources';
$fixed = 0;

// First, build a map of all Resource files and their locations
$resourceMap = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourcesDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $file) {
    if (!str_ends_with($file->getBasename(), 'Resource.php')) {
        continue;
    }

    $path = $file->getPathname();
    $relativePath = str_replace(['\\', '.php'], ['/', ''], str_replace("$resourcesDir/", '', $path));
    $className = $file->getBasename('.php');
    
    // Get directory where this Resource is located
    $dir = str_replace(['\\', 'Resource.php'], ['/', ''], str_replace("$resourcesDir/", '', $path));
    $dir = dirname($dir);
    if ($dir === '.') {
        $dir = '';
    }
    
    $resourceMap[$dir ? "$dir/$className" : $className] = [
        'path' => $path,
        'class' => $className,
        'dir' => $dir,
    ];
}

echo "Resource map built: " . count($resourceMap) . " resources\n\n";

// Now find all Pages files and fix them
$pagesIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator("$resourcesDir/*/Pages"),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$allPagesFiles = [];
foreach ($pagesIterator as $file) {
    if ($file->getExtension() === 'php') {
        $allPagesFiles[] = $file->getPathname();
    }
}

// Also get nested Pages
$allPagesIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourcesDir),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($allPagesIterator as $dir) {
    if ($dir->getBasename() === 'Pages' && $dir->isDir()) {
        $pagesDirPath = $dir->getPathname();
        $pagesFileIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pagesDirPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($pagesFileIterator as $file) {
            if ($file->getExtension() === 'php') {
                $allPagesFiles[] = $file->getPathname();
            }
        }
    }
}

// Remove duplicates
$allPagesFiles = array_unique($allPagesFiles);

echo "Found " . count($allPagesFiles) . " Pages files\n\n";

foreach ($allPagesFiles as $pageFile) {
    $content = file_get_contents($pageFile);
    
    // Parse namespace from file
    if (!preg_match('/namespace\s+App\\\\Filament\\\\Tenant\\\\Resources\\\\([^;]+);/', $content, $matches)) {
        continue;
    }
    
    $resourceNamespace = $matches[1];
    // Remove \Pages from the end
    $resourceNamespace = str_replace('\\Pages', '', $resourceNamespace);
    
    // Find the corresponding Resource
    // For namespace: AI\Pages, look for AI/AIConstructorResource or AI
    $parts = explode('\\', $resourceNamespace);
    $resourceClass = null;
    $resourceUse = null;
    
    // Try exact match with _parts
    for ($i = count($parts); $i >= 1; $i--) {
        $searchPath = implode('/', array_slice($parts, 0, $i));
        
        // Look for Resource files in this path
        $searchPattern = "$resourcesDir/" . str_replace('/', '\\', $searchPath) . "/*Resource.php";
        $searchPattern = str_replace('\\', '/', $searchPattern);
        
        $matches = glob($searchPattern);
        if (!empty($matches)) {
            $resourceFile = $matches[0];
            $resourceClass = basename($resourceFile, '.php');
            
            // Build the use statement
            $relativePath = str_replace("$resourcesDir/", '', $resourceFile);
            $relativePath = str_replace('.php', '', str_replace('\\', '/', $relativePath));
            $resourceUse = "use App\\Filament\\Tenant\\Resources\\" . str_replace('/', '\\', $relativePath) . ";";
            break;
        }
    }
    
    if (!$resourceClass || !$resourceUse) {
        echo "⚠️  Cannot find Resource for: $pageFile\n";
        continue;
    }
    
    // Fix the file content
    $newContent = $content;
    
    // Replace any malformed use statements
    $newContent = preg_replace(
        '/use App\\\\Filament\\\\Tenant\\\\Resources[^;]*[^;]*;/m',
        $resourceUse,
        $newContent
    );
    
    // Fix the $resource property
    $newContent = preg_replace(
        '/protected static string \$resource = [^;]*;/',
        "protected static string \$resource = $resourceClass::class;",
        $newContent
    );
    
    if ($newContent !== $content) {
        file_put_contents($pageFile, $newContent);
        $fixed++;
        echo "✅ Fixed: " . basename(dirname($pageFile)) . "/" . basename($pageFile) . "\n";
    }
}

echo "\n✅ Total fixed: $fixed files\n";
