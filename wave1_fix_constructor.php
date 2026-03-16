<?php
/**
 * WAVE 1 FIX V2: Properly convert boot() to constructor injection
 * Keeps properties and adds them to constructor parameters
 */

$base = 'app/Filament/Tenant/Resources';
$fixed = [];

function processFilesV2($dir) {
    global $fixed;
    
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $file) {
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            processFilesV2($path);
        } elseif (str_ends_with($file, '.php')) {
            $content = file_get_contents($path);
            $original = $content;
            
            // Find boot() method with its parameters
            if (preg_match('/public\s+function\s+boot\s*\(\s*([^)]*?)\s*\)\s*:\s*void\s*\{\s*(.*?)\s*\}/ms', $content, $m)) {
                $bootParams = $m[1];
                $bootBody = $m[2];
                
                // Extract parameter names and types
                preg_match_all('/(\w+)\s+\$(\w+)/', $bootParams, $paramMatches);
                
                if (!empty($paramMatches[0])) {
                    // Found valid parameters
                    $paramList = [];
                    $assignments = [];
                    
                    for ($i = 0; $i < count($paramMatches[2]); $i++) {
                        $paramType = $paramMatches[1][$i];
                        $paramName = $paramMatches[2][$i];
                        
                        // Add to new constructor parameter list
                        $paramList[] = "protected $paramType \$$paramName";
                        
                        // Mark assignments for removal - they're now in constructor
                        $assignments[] = "\$this->$paramName = \$$paramName;";
                    }
                    
                    // Create new constructor with promoted properties
                    $newConstructor = "public function __construct(\n        " . 
                        implode(",\n        ", $paramList) . "\n    ) {}";
                    
                    // Replace old boot() method with new constructor
                    $content = preg_replace(
                        '/public\s+function\s+boot\s*\([^)]*\)\s*:\s*void\s*\{[^}]*\}/ms',
                        $newConstructor,
                        $content
                    );
                    
                    // Remove protected property declarations for these types
                    // Only if they're in the boot() parameters
                    foreach ($paramMatches[2] as $paramName) {
                        $content = preg_replace(
                            '/\n\s+protected\s+\w+\s+\$' . preg_quote($paramName) . ';/m',
                            '',
                            $content
                        );
                    }
                    
                    if ($original !== $content) {
                        // Ensure proper line endings
                        $content = str_replace("\r\n", "\n", $content);
                        $content = str_replace("\n", "\r\n", $content);
                        
                        file_put_contents($path, $content);
                        $fixed[] = str_replace('app/', '', $path);
                    }
                }
            }
        }
    }
}

processFilesV2($base);

echo "=== WAVE 1: Constructor Injection Fix ===\n";
echo "Files fixed: " . count($fixed) . "\n\n";

foreach ($fixed as $f) {
    echo "  ✓ $f\n";
}

file_put_contents('wave1_fixed_list.txt', implode("\n", $fixed));
?>
