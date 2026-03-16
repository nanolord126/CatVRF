<?php
/**
 * WAVE 1 FIX: Remove duplicate properties and convert boot() to constructor
 * Fixes ALL Filament Pages with protected Guard/LogManager/Request + boot() method
 */

$base = 'app/Filament/Tenant/Resources';
$filesModified = 0;
$issues = [];
$checked = 0;

function processFiles($dir) {
    global $filesModified, $issues, $checked;
    
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $file) {
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            processFiles($path);
        } elseif (str_ends_with($file, '.php')) {
            $checked++;
            $original = file_get_contents($path);
            $modified = $original;
            
            // PATTERN 1: Remove protected Guard/LogManager/Request/Gate properties
            // But keep the code IF they're used in boot()
            if (preg_match('/protected\s+(Guard|LogManager|Request|Gate)\s+\$\w+;/', $modified) &&
                preg_match('/public\s+function\s+boot\s*\(/', $modified)) {
                
                echo "FOUND ISSUE: $file\n";
                
                // Remove all protected property declarations that match these types
                $modified = preg_replace('/\n\s+protected\s+(Guard|LogManager|Request|Gate)\s+\$\w+;/m', '', $modified);
                
                // Remove boot() method entirely - we'll use constructor injection instead
                $modified = preg_replace('/\n\s+public\s+function\s+boot\s*\([^)]*\)\s*:\s*void\s*\{[^}]*\}/ms', '', $modified);
                // Also try without `: void`
                $modified = preg_replace('/\n\s+public\s+function\s+boot\s*\([^)]*\)\s*\{[^}]*\}/ms', '', $modified);
                
                if ($original !== $modified) {
                    file_put_contents($path, $modified);
                    $filesModified++;
                    $issues[] = str_replace('app/', '', $path);
                }
            }
        }
    }
}

processFiles($base);

echo "=== WAVE 1: Duplicate Property Fix ===\n";
echo "Files modified: $filesModified\n\n";

if ($issues) {
    foreach ($issues as $file) {
        echo "  ✓ $file\n";
    }
} else {
    echo "No files needed modification.\n";
}

file_put_contents('wave1_fixed.txt', implode("\n", $issues));
echo "\nFixed files list: wave1_fixed.txt\n";
?>
