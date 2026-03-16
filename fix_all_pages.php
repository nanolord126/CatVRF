<?php

declare(strict_types=1);

$pagesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pagesDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$fixed = 0;
$errors = [];

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php' || !str_contains($file->getPathname(), '/Pages/')) {
        continue;
    }
    
    $path = $file->getPathname();
    $content = file_get_contents($path);
    $original = $content;
    
    // Extract resource name from namespace
    if (preg_match('/namespace App\\\\Filament\\\\Tenant\\\\Resources\\\\([^\\\\]+)\\\\Pages;/', $content, $m)) {
        $resourceName = $m[1];
        
        // Fix import
        $content = preg_replace(
            '/use App\\\\Filament\\\\Tenant\\\\Resources\\\\[^;]*AiAssistantChatResource[^;]*;/',
            'use App\\Filament\\Tenant\\Resources\\' . $resourceName . 'Resource;',
            $content
        );
        
        // Fix resource property
        $content = preg_replace(
            '/protected static string \$resource = [^;]+;/',
            'protected static string $resource = ' . $resourceName . 'Resource::class;',
            $content
        );
        
        // Add declare if missing
        if (!str_starts_with(trim($content), 'declare')) {
            $content = "<?php\n\ndeclare(strict_types=1);\n\n" . substr($content, 5);
        }
        
        // Make class final
        if (str_contains($content, 'class ') && !str_contains($content, 'final class')) {
            $content = preg_replace('/^class /', 'final class ', $content, 1);
        }
        
        if ($content !== $original) {
            if (file_put_contents($path, $content)) {
                $fixed++;
                echo "✅ Fixed: {$resourceName} Pages\n";
            } else {
                $errors[] = "Failed to write: $path";
            }
        }
    } else {
        $errors[] = "Could not extract resource from: $path";
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "✅ FIXED: $fixed Pages files\n";

if (!empty($errors)) {
    echo "\n❌ ERRORS:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
}
