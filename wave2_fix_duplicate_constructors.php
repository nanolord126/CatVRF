<?php
/**
 * WAVE 2: Remove duplicate constructors
 * If a file has 2x __construct(), delete the FIRST one
 */

$baseDir = 'app/Filament';
$fixed = 0;

function processDir($dir) {
    global $fixed;
    
    foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            processDir($path);
        } elseif (str_ends_with($item, '.php')) {
            $content = file_get_contents($path);
            $original = $content;
            
            // Count occurrences of public function __construct(
            $count = substr_count($content, 'public function __construct(');
            
            if ($count === 2) {
                // Remove FIRST occurrence of __construct block (from "public function __construct(" to "}")
                // This regex matches from the first constructor to its closing brace
                $content = preg_replace(
                    '/public\s+function\s+__construct\s*\([^)]*\)\s*\{[^}]*\}/m',
                    '',
                    $content,
                    1  // Only first occurrence
                );
                
                // Clean up extra whitespace
                $content = preg_replace('/\n\n\n+/m', "\n\n", $content);
                
                if ($original !== $content) {
                    file_put_contents($path, $content);
                    $fixed++;
                    echo "✓ " . str_replace('app/', '', $path) . "\n";
                }
            }
        }
    }
}

processDir($baseDir);

echo "\n=== WAVE 2: Duplicate Constructor Fix ===\n";
echo "Files fixed: $fixed\n";
?>
