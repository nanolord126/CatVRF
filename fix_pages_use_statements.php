<?php

$pagesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$fixedCount = 0;

function fixPageFile($filePath) {
    global $fixedCount;
    
    if (filesize($filePath) < 10) {
        return; // File too small
    }
    
    $content = file_get_contents($filePath);
    
    // Fix invalid "use App\Filament\Tenant\Resources\.\Pages;" pattern
    if (preg_match('/use\s+App\\Filament\\Tenant\\Resources\\\\\.\\\Pages;/', $content)) {
        // Extract the correct resource FQN from namespace
        preg_match('/namespace\s+(App\\Filament\\Tenant\\Resources\\\\(.+?)\\\\Pages);/', $content, $nsMatch);
        if (!empty($nsMatch[2])) {
            $resourcePath = $nsMatch[2];
            $resourceFqn = "App\\Filament\\Tenant\\Resources\\" . str_replace('\\Pages', '', $resourcePath);
            $content = preg_replace(
                '/use\s+App\\Filament\\Tenant\\Resources\\\\\.\\\Pages;/',
                "use $resourceFqn;",
                $content
            );
            file_put_contents($filePath, $content);
            $fixedCount++;
            echo "✓ Fixed: " . basename($filePath) . "\n";
        }
    }
}

function processDirectory($dir) {
    $files = glob($dir . '*/Pages/*.php');
    foreach ($files as $file) {
        fixPageFile($file);
    }
}

processDirectory($pagesDir . '/');

$subdirs = glob($pagesDir . '/*/', GLOB_ONLYDIR);
foreach ($subdirs as $subdir) {
    processDirectory($subdir);
    
    $nestedDirs = glob($subdir . '*/', GLOB_ONLYDIR);
    foreach ($nestedDirs as $nestedDir) {
        processDirectory($nestedDir);
    }
}

echo "\n✅ Fixed: $fixedCount invalid Page files\n";
