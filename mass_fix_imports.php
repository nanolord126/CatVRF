<?php
/**
 * Mass fix: double commas, missing LogManager import, missing FraudControlService import.
 * 
 * 1. Double commas: ",," → "," throughout constructors and code
 * 2. Missing LogManager import: add "use Illuminate\Log\LogManager;" when LogManager type-hinted
 * 3. Missing FraudControlService import: add "use App\Services\FraudControlService;"
 */

$baseDir = __DIR__ . '/app';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$stats = [
    'double_comma' => 0,
    'logmanager_import' => 0,
    'fraud_import' => 0,
];

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    
    $content = file_get_contents($file->getPathname());
    if ($content === false) continue;
    
    $modified = false;
    
    // 1. Fix double commas (in constructors, method calls, arrays)
    // Pattern: comma followed by optional whitespace/newlines then another comma
    $prevContent = '';
    $iterations = 0;
    while ($prevContent !== $content && $iterations < 10) {
        $prevContent = $content;
        $content = preg_replace('/,(\s*),/', ',$1', $content);
        $iterations++;
    }
    if ($iterations > 1) {
        $modified = true;
        $stats['double_comma']++;
    }
    
    // 2. Add missing LogManager import
    if (preg_match('/\bLogManager\s+\$/', $content) && 
        strpos($content, 'use Illuminate\\Log\\LogManager;') === false) {
        
        // Find the last use statement and add after it
        if (preg_match('/^(use\s+[^\n]+;\s*\n)(?!use\s)/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            $content = substr($content, 0, $insertPos) . "use Illuminate\\Log\\LogManager;\n" . substr($content, $insertPos);
            $modified = true;
            $stats['logmanager_import']++;
        }
    }
    
    // 3. Add missing FraudControlService import
    if (preg_match('/FraudControlService\s+\$/', $content) &&
        strpos($content, 'use App\\Services\\FraudControlService;') === false &&
        strpos($content, 'namespace App\\Services;') === false &&
        strpos($content, 'namespace App\\Services\\') === false) {
        
        // Find the last use statement and add after it
        if (preg_match('/^(use\s+[^\n]+;\s*\n)(?!use\s)/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            $content = substr($content, 0, $insertPos) . "use App\\Services\\FraudControlService;\n" . substr($content, $insertPos);
            $modified = true;
            $stats['fraud_import']++;
        }
    }
    
    if ($modified) {
        file_put_contents($file->getPathname(), $content);
    }
}

echo "=== MASS FIX RESULTS ===\n";
echo "Double commas fixed: {$stats['double_comma']} files\n";
echo "LogManager import added: {$stats['logmanager_import']} files\n";
echo "FraudControlService import added: {$stats['fraud_import']} files\n";
echo "Total files modified: " . array_sum($stats) . "\n";
