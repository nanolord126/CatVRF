<?php
/**
 * WAVE 1: Fix all duplicate protected Guard/LogManager/Request properties in Filament Pages
 * Strategy: Replace protected properties + boot() with constructor injection
 */

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources';
$count = 0;
$patterns = [];

function walkPhpFiles($dir) {
    global $count, $patterns;
    
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') return;
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            walkPhpFiles($path);
        } elseif (substr($item, -4) === '.php') {
            $content = file_get_contents($path);
            
            // Check for the problematic pattern
            if (preg_match_all('/protected\s+(Guard|LogManager|Request)\s+\$(\w+);/m', $content, $m1) &&
                preg_match('/public\s+function\s+boot\s*\((.*?)\)\s*:\s*void\s*\{(.*?)\}/ms', $content, $m2)) {
                
                $patterns[] = [
                    'file' => str_replace(__DIR__ . '/', '', $path),
                    'boot_params' => trim($m2[1]),
                    'boot_body' => trim($m2[2])
                ];
                $count++;
            }
        }
    }
}

walkPhpFiles($baseDir);

echo "=== WAVE 1: Duplicate Properties Audit ===\n";
echo "Found: $count files with duplicate property/boot patterns\n\n";

usort($patterns, fn($a, $b) => strcmp($a['file'], $b['file']));

foreach ($patterns as $p) {
    echo "FILE: " . $p['file'] . "\n";
    echo "  BOOT PARAMS: " . substr($p['boot_params'], 0, 60) . "...\n";
    echo "\n";
}

file_put_contents('wave1_duplicates.json', json_encode($patterns, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "\nAudit saved to: wave1_duplicates.json\n";
?>
