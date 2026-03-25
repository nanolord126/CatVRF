<?php declare(strict_types=1);

/**
 * Fix route file syntax errors
 * Ensures all Route::middleware()->group() calls are properly closed
 */

$routesDir = __DIR__ . '/routes';
$files = glob($routesDir . '/*.api.php');

foreach ($files as $file) {
    echo "Processing: " . basename($file) . "\n";
    
    $content = file_get_contents($file);
    
    // Count opening and closing braces
    $opens = substr_count($content, '{') - substr_count($content, '\'{\'' ) - substr_count($content, '"{"');
    $closes = substr_count($content, '}') - substr_count($content, '\'}\'' ) - substr_count($content, '"}"');
    
    if ($opens > $closes) {
        echo "  ⚠️  Found $opens opens, $closes closes - NEEDS FIXING\n";
        
        // Add closing braces at end of file
        $diff = $opens - $closes;
        $closures = str_repeat("});\n", $diff);
        
        // Remove any trailing whitespace first
        $content = rtrim($content);
        $content .= "\n\n" . $closures;
        
        file_put_contents($file, $content);
        echo "  ✅ Added $diff closing braces\n";
    } else {
        echo "  ✅ Syntax OK\n";
    }
}

echo "\n✅ Route syntax check complete!\n";
