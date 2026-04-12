<?php
/**
 * Full PHP syntax check across ALL php files in app/.
 * Reports only errors.
 */

$baseDir = __DIR__ . '/app';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$total = 0;
$errors = 0;
$errorFiles = [];

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    
    $total++;
    $output = [];
    exec("php -l \"{$file->getPathname()}\" 2>&1", $output, $exitCode);
    
    if ($exitCode !== 0) {
        $errors++;
        $relPath = str_replace(__DIR__ . '/', '', $file->getPathname());
        $errorMsg = implode(' ', $output);
        $errorFiles[] = ['path' => $relPath, 'error' => $errorMsg];
        
        // Print as we go
        if ($errors <= 50) {
            echo "❌ {$relPath}\n";
            echo "   {$errorMsg}\n";
        }
    }
    
    // Progress
    if ($total % 500 === 0) {
        echo "... checked {$total} files, {$errors} errors so far...\n";
    }
}

echo "\n=== FULL SYNTAX CHECK ===\n";
echo "Total files: {$total}\n";
echo "Errors: {$errors}\n";
echo "Pass rate: " . round(($total - $errors) / $total * 100, 1) . "%\n";

if ($errors === 0) {
    echo "\n🎉 ALL {$total} FILES PASS SYNTAX CHECK!\n";
} elseif ($errors > 50) {
    echo "\n(First 50 errors shown above, {$errors} total)\n";
}
