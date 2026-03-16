<?php

$pagesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$fixedCount = 0;
$errors = [];

function fixPagesFile($filePath) {
    global $fixedCount, $errors;
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    try {
        // 1. Add declare(strict_types=1) if missing
        if (!preg_match('/^<\?php\s*\n\s*declare\(strict_types=1\)/m', $content)) {
            $content = preg_replace('/<\?php\s*\n/', "<?php\n\ndeclare(strict_types=1);\n", $content, 1);
        }
        
        // 2. Make class final if it's not a custom page
        if (!preg_match('/protected\s+static\s+string\s+\$view\s*=/', $content)) {
            $content = preg_replace('/\bclass\s+/', 'final class ', $content);
        }
        
        // 3. Fix namespace to match file structure
        $relativePath = str_replace(__DIR__ . '/', '', $filePath);
        preg_match('/app\/Filament\/Tenant\/Resources\/(.+?)\/Pages\/.+?\.php$/', $relativePath, $matches);
        
        if (!empty($matches[1])) {
            $resourcePath = $matches[1];
            $newNamespace = "App\\Filament\\Tenant\\Resources\\" . str_replace('/', '\\', $resourcePath) . "\\Pages";
            $content = preg_replace(
                '/namespace\s+App\\Filament\\Tenant\\Resources\\[^;]*;/',
                "namespace $newNamespace;",
                $content
            );
        }
        
        // 4. Add Filament use statement if using standard Page types
        if (preg_match('/extends\s+(ListRecords|CreateRecord|EditRecord|ViewRecord)/', $content)) {
            if (!preg_match('/use\s+Filament\\Resources\\Pages/', $content)) {
                // Find namespace line and add use after it
                $content = preg_replace(
                    '/(namespace [^;]+;)\n/',
                    "$1\n\nuse Filament\Resources\Pages;\n",
                    $content
                );
            }
        }
        
        // 5. Convert to CRLF if LF
        $content = str_replace("\r\n", "\n", $content);  // Normalize first
        $content = str_replace("\n", "\r\n", $content);  // Then set to CRLF
        
        // Only write if content changed
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $fixedCount++;
        }
        
    } catch (Exception $e) {
        $errors[$filePath] = $e->getMessage();
    }
}

function processDirectory($dir) {
    $files = glob($dir . '*/Pages/*.php');
    foreach ($files as $file) {
        fixPagesFile($file);
    }
}

// Process root resources
processDirectory($pagesDir . '/');

// Process subdirectories
$subdirs = glob($pagesDir . '/*/', GLOB_ONLYDIR);
foreach ($subdirs as $subdir) {
    processDirectory($subdir);
    
    // Process nested directories (Marketplace/Taxi)
    $nestedDirs = glob($subdir . '*/', GLOB_ONLYDIR);
    foreach ($nestedDirs as $nestedDir) {
        processDirectory($nestedDir);
    }
}

echo "✅ Fixed: $fixedCount files\n";

if (!empty($errors)) {
    echo "\n❌ Errors:\n";
    foreach ($errors as $file => $error) {
        echo "  $file: $error\n";
    }
}
