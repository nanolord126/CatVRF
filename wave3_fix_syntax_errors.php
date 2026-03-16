<?php
/**
 * WAVE 3: Fix broken PHP files with syntax errors
 * Problem: Closing braces left after constructor deletion
 */

$baseDir = 'app/Filament';
$fixed = 0;
$brokenFiles = [];

function processDir($dir) {
    global $fixed, $brokenFiles;
    
    foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            processDir($path);
        } elseif (str_ends_with($item, '.php')) {
            $content = file_get_contents($path);
            $original = $content;
            
            // Find pattern: __construct(...) {} followed by tab/spaces and } (orphaned brace)
            $content = preg_replace(
                '/(\{[^}]*public\s+function\s+__construct\s*\([^)]*\)\s*\{\s*\})\s*\n\s*\}/m',
                '$1',
                $content
            );
            
            // Also fix simpler pattern: constructor ending with } then } on next line
            $content = preg_replace(
                '/(public function __construct[^}]*\}\)\s*\{)\s*\}\s*\n\s+\}/m',
                '$1',
                $content
            );
            
            if ($original !== $content) {
                file_put_contents($path, $content);
                $fixed++;
                echo "✓ " . str_replace('app/', '', $path) . "\n";
            }
        }
    }
}

processDir($baseDir);

echo "\n=== WAVE 3: Fix Syntax Errors ===\n";
echo "Files fixed: $fixed\n";
?>
