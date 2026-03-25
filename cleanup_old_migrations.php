<?php declare(strict_types=1);

// Remove all migrations except 2026_03_25_* (new canonical ones) and essential ones

$migrationsFolder = 'database/migrations';
$files = glob("{$migrationsFolder}/*.php");

// Keep patterns
$keep_patterns = [
    '/^0001_01_01/',   // Original Laravel migrations (users, sessions, jobs, cache)
    '/^2019_09_15/',   // Tenants setup (critical for multi-tenancy)
    '/^2026_03_08_132919/', // business_groups (required by 2026_03_25)
    '/^2026_03_25/',   // New canonical migrations
];

$deleted = [];
$kept = [];

foreach ($files as $file) {
    $filename = basename($file);
    
    $should_keep = false;
    foreach ($keep_patterns as $pattern) {
        if (preg_match($pattern, $filename)) {
            $should_keep = true;
            break;
        }
    }
    
    if (!$should_keep) {
        unlink($file);
        $deleted[] = $filename;
    } else {
        $kept[] = $filename;
    }
}

echo "✅ Deleted " . count($deleted) . " old/duplicate migrations\n";
echo "✅ Kept " . count($kept) . " essential migrations\n\n";

echo "Deleted files:\n";
foreach (array_slice($deleted, 0, 20) as $f) {
    echo "  - $f\n";
}
if (count($deleted) > 20) {
    echo "  ... and " . (count($deleted) - 20) . " more\n";
}
