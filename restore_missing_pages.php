<?php

$pagesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$createdCount = 0;

function createMissingPage($filePath) {
    global $createdCount;
    
    // Check if file is empty
    if (filesize($filePath) > 50) {
        return; // File has content
    }
    
    // Extract path components
    preg_match('/app\/Filament\/Tenant\/Resources\/(.+?)\/Pages\/(\w+)\.php$/', str_replace('\\', '/', $filePath), $matches);
    
    if (empty($matches[1]) || empty($matches[2])) {
        return;
    }
    
    $resourcePath = $matches[1];
    $pageName = $matches[2];
    
    // Determine Resource class name and parent Page type
    preg_match('/^(.+)Resource$/', basename(dirname(dirname($filePath))), $resourceMatch);
    if (!empty($resourceMatch[1])) {
        $resourceClass = $resourceMatch[1] . 'Resource';
    } else {
        // For Marketplace/Taxi/TaxiCarResource -> TaxiCarResource
        $parts = explode('/', $resourcePath);
        $resourceClass = end($parts);
    }
    
    // Determine Page type from filename
    if (str_starts_with($pageName, 'List')) {
        $pageType = 'ListRecords';
    } elseif (str_starts_with($pageName, 'Create')) {
        $pageType = 'CreateRecord';
    } elseif (str_starts_with($pageName, 'Edit')) {
        $pageType = 'EditRecord';
    } elseif (str_starts_with($pageName, 'View')) {
        $pageType = 'ViewRecord';
    } else {
        $pageType = 'Page';
    }
    
    $namespace = "App\\Filament\\Tenant\\Resources\\" . str_replace('/', '\\', $resourcePath) . "\\Pages";
    $resourceFqn = "App\\Filament\\Tenant\\Resources\\" . str_replace('/', '\\', dirname($resourcePath)) . '\\' . basename(dirname($filePath));
    
    // Build content
    $content = "<?php\n\nnamespace $namespace;\n\n";
    $content .= "use $resourceFqn;\n";
    $content .= "use Filament\\Resources\\Pages\\$pageType;\n\n";
    $content .= "class $pageName extends $pageType\n{\n";
    $content .= "    protected static string \$resource = $resourceFqn::class;\n}\n";
    
    file_put_contents($filePath, $content);
    $createdCount++;
    echo "✓ Created: " . basename($filePath) . "\n";
}

function processDirectory($dir) {
    $files = glob($dir . '*/Pages/*.php');
    foreach ($files as $file) {
        createMissingPage($file);
    }
}

// Process all directories
processDirectory($pagesDir . '/');

$subdirs = glob($pagesDir . '/*/', GLOB_ONLYDIR);
foreach ($subdirs as $subdir) {
    processDirectory($subdir);
    
    $nestedDirs = glob($subdir . '*/', GLOB_ONLYDIR);
    foreach ($nestedDirs as $nestedDir) {
        processDirectory($nestedDir);
    }
}

echo "\n✅ Created: $createdCount missing Page files\n";
