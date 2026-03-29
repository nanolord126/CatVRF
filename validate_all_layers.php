<?php
declare(strict_types=1);

$layers = [
    'Controllers' => 'app/Http/Controllers',
    'Models' => 'app/Models',
    'Services' => 'app/Services',
    'Jobs' => 'app/Jobs',
    'Events' => 'app/Events',
    'Tests' => 'tests',
];

$total_checked = 0;
$total_errors = 0;
$error_files = [];

foreach ($layers as $name => $path) {
    if (!is_dir($path)) {
        echo "$name: DIR NOT FOUND\n";
        continue;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    $layer_errors = 0;
    foreach ($files as $file) {
        if (!str_ends_with($file->getPathname(), '.php')) {
            continue;
        }

        $total_checked++;
        $result = shell_exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1');
        if (strpos($result, 'Parse error') !== false || strpos($result, 'No syntax errors') === false) {
            $layer_errors++;
            $total_errors++;
            $error_files[] = $file->getFilename() . ': ' . trim($result);
        }
    }

    echo "$name: " . ($layer_errors === 0 ? "✅ OK\n" : "⚠️  $layer_errors errors\n");
}

echo "\n========================================\n";
echo "Total checked: $total_checked\n";
echo "Total errors: $total_errors\n";

if ($total_errors > 0) {
    echo "\nFirst 10 errors:\n";
    foreach (array_slice($error_files, 0, 10) as $error) {
        echo "  ⚠️  $error\n";
    }
}
