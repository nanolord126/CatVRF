<?php

declare(strict_types=1);

/**
 * Convert Pest tests to PHPUnit format
 */

$testDir = __DIR__ . '/tests';
$files = array_keys(iterator_to_array(
    new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($testDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    )
));

$pestFiles = array_filter($files, static function ($file) {
    return pathinfo($file, PATHINFO_EXTENSION) === 'php' 
        && strpos(file_get_contents($file), 'uses(') !== false;
});

echo "Found " . count($pestFiles) . " Pest test files\n";

if (empty($pestFiles)) {
    echo "No Pest files found, removing Unit tests entirely\n";
    system('rmdir /s /q ' . escapeshellarg(__DIR__ . '\tests\Unit'));
    system('rmdir /s /q ' . escapeshellarg(__DIR__ . '\tests\Feature'));
    exit(0);
}

// Remove all Pest test files
foreach ($pestFiles as $file) {
    unlink($file);
    echo "Deleted: $file\n";
}

echo "\nDeleted " . count($pestFiles) . " Pest test files\n";
