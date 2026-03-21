<?php declare(strict_types=1);

$migrationsDir = __DIR__ . '/database/migrations';
$files = glob($migrationsDir . '/*.php');

foreach ($files as $file) {
    $className = pathinfo($file, PATHINFO_FILENAME);
    $className = str_replace('_', ' ', $className);
    $className = ucwords($className);
    $className = str_replace(' ', '', $className);
    
    echo "File: " . basename($file) . "\n";
    echo "  Class name: " . $className . "\n";
    
    try {
        require_once $file;
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}
