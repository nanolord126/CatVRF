<?php

$dir = 'app/Filament/Tenant/Resources/HR';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getRealPath());
        $matches = [];
        preg_match_all('/^final\s+class\s+\w+/m', $content, $matches);
        
        if (count($matches[0]) > 1) {
            echo "Found duplicates in: {$file->getRealPath()}\n";
            
            // Find and remove the second occurrence of "final class"
            $pattern = '/^\/\*\*[\s\S]*?\*\/\s*final\s+class\s+\w+[\s\S]*?^}/m';
            $cleaned = preg_replace($pattern, '', $content, count($matches[0]) - 1);
            
            file_put_contents($file->getRealPath(), $cleaned);
            echo "  ✓ Cleaned\n";
        }
    }
}

echo "Done!\n";
