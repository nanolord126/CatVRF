<?php
declare(strict_types=1);

/**
 * Fix Pages files: Find correct Resource files and update imports
 */

$resourcesDir = 'app/Filament/Tenant/Resources';
$pagesDirPattern = "$resourcesDir/*/Pages";
$pageDirs = glob($pagesDirPattern, GLOB_BRACE);

// Also get nested resources: app/Filament/Tenant/Resources/*/*/Pages
$pageDirs = array_merge($pageDirs, glob("$resourcesDir/*/*/Pages", GLOB_BRACE));
// And deeply nested: app/Filament/Tenant/Resources/*/*/*/Pages
$pageDirs = array_merge($pageDirs, glob("$resourcesDir/*/*/*/Pages", GLOB_BRACE));

$fixed = 0;

foreach ($pageDirs as $pagesDir) {
    if (!is_dir($pagesDir)) {
        continue;
    }

    // Find all PHP files in this Pages directory (recursively)
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pagesDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);

        // Get the namespace from the file
        preg_match('/^namespace\s+(.+?);/m', $content, $namespaceMatch);
        if (!isset($namespaceMatch[1])) {
            continue;
        }

        $namespace = $namespaceMatch[1];
        // Extract resource path from namespace
        // Example: App\Filament\Tenant\Resources\AiAssistantChatResource\Pages -> AiAssistantChatResource
        preg_match('/Resources\\\\([^\\\\]+)\\\\Pages/', $namespace, $resourceMatch);
        if (!isset($resourceMatch[1])) {
            // Try nested: Resources\SomeDir\ResourceName\Pages
            preg_match('/Resources\\\\(.+?)\\\\Pages/', $namespace, $resourceMatch);
            if (!isset($resourceMatch[1])) {
                continue;
            }
            // For nested resources, find the actual ResourceName (ends with Resource)
            $parts = explode('\\', $resourceMatch[1]);
            $resourceName = end($parts);
        } else {
            $resourceName = $resourceMatch[1];
        }

        // Build the correct use statement
        // Path structure: app/Filament/Tenant/Resources/SubDir/ResourceName/Pages/PageName.php
        // Use statement: use App\Filament\Tenant\Resources\SubDir\ResourceName;

        // Get relative path from Resources dir
        $relativePath = str_replace("$resourcesDir/", '', $pagesDir);
        $relativePath = str_replace('/Pages', '', $relativePath);
        
        // Build use statement
        $useStatement = "use App\\Filament\\Tenant\\Resources\\$relativePath;";

        // Check if file already has the wrong use statement
        $oldUsePattern = '/use App\\\\Filament\\\\Tenant\\\\Resources[^;]*\\\\Pages;/';
        $newContent = preg_replace($oldUsePattern, $useStatement, $content);

        // Also fix any reference to ::class in file
        if ($newContent !== $content) {
            file_put_contents($filePath, $newContent);
            $fixed++;
            echo "✅ Fixed: " . basename($filePath) . " in " . basename(dirname($pagesDir)) . "\n";
        }
    }
}

echo "\n✅ Total fixed: $fixed files\n";
