<?php declare(strict_types=1);

/**
 * Comprehensive route file syntax fixer
 * Analyzes each file and removes unmatched closing braces
 */

$routesDir = __DIR__ . '/routes';
$files = glob($routesDir . '/*.api.php');

$fixed = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    
    $openCount = 0;
    $lastValidLine = 0;
    $output = [];
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $trimmed = trim($line);
        
        // Count braces
        $lineOpens = substr_count($line, '{') - substr_count($line, '\'{\'' ) - substr_count($line, '"{"');
        $lineCloses = substr_count($line, '}') - substr_count($line, '\'}\'' ) - substr_count($line, '"}"');
        
        $openCount += $lineOpens;
        $openCount -= $lineCloses;
        
        // Skip lines that would make count negative
        if ($openCount < 0) {
            echo basename($file) . " Line " . ($i + 1) . ": Removing unmatched '}'\n";
            $openCount = 0;
            continue;
        }
        
        $output[] = $line;
        if (!empty($trimmed)) {
            $lastValidLine = $i;
        }
    }
    
    // Trim trailing empty lines
    while ($lastValidLine > 0 && trim($output[$lastValidLine]) === '') {
        $lastValidLine--;
    }
    
    $output = array_slice($output, 0, $lastValidLine + 1);
    
    // Add missing closing braces
    while ($openCount > 0) {
        echo basename($file) . ": Adding closing brace\n";
        $output[] = '});';
        $openCount--;
    }
    
    $newContent = implode("\n", $output) . "\n";
    file_put_contents($file, $newContent);
    $fixed[] = basename($file);
}

echo "\n✅ Fixed " . count($fixed) . " route files\n";
