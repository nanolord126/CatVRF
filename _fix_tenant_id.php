<?php declare(strict_types=1);

/**
 * Mass fix tenant('id') -> tenant()?->id in Domain Models, Services, Controllers.
 * In Models (global scopes): tenant('id') -> tenant()?->id
 * In Controllers/Services: tenant('id') -> tenant()?->id  
 */

$basePath = __DIR__ . '/app/Domains';
$fixed = 0;
$files_fixed = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    
    $content = file_get_contents($file->getPathname());
    $original = $content;
    
    // Skip Routes files (Route facade is allowed there)
    if (str_contains($file->getPathname(), 'Routes')) continue;
    
    // Fix tenant('id') -> tenant()?->id  (all patterns)
    
    // Pattern 1: tenant('id') standalone
    $content = str_replace("tenant('id')", "tenant()?->id", $content);
    
    // Pattern 2: (int) tenant()?->id  (keep cast)
    // Already handled by pattern 1
    
    if ($content !== $original) {
        file_put_contents($file->getPathname(), $content);
        $count = substr_count($original, "tenant('id')");
        $fixed += $count;
        $files_fixed++;
        $rel = str_replace(__DIR__ . '/', '', $file->getPathname());
        echo "FIXED [{$count}]: {$rel}\n";
    }
}

echo "\nTotal fixes: {$fixed} in {$files_fixed} files\n";
