<?php declare(strict_types=1);

$layers = [
    'Controllers (118 files)' => 'app/Http/Controllers',
    'Models (117 files)' => 'app/Models',
    'Services (207 files)' => 'app/Services',
    'Jobs (40 files)' => 'app/Jobs',
    'Events (16 files)' => 'app/Events',
    'Tests (57 files)' => 'tests',
];

$output = "PHASE 9: COMPREHENSIVE LAYER VALIDATION\n";
$output .= "Date: " . date('Y-m-d H:i:s') . "\n";
$output .= str_repeat("=", 60) . "\n\n";

$total_errors = 0;

foreach ($layers as $name => $path) {
    if (!is_dir($path)) {
        $output .= "$name: DIR NOT FOUND\n";
        continue;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    $layer_errors = [];
    $count = 0;
    
    foreach ($files as $file) {
        if (!str_ends_with($file->getPathname(), '.php')) {
            continue;
        }
        
        $count++;
        $result = shell_exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1');
        if (strpos($result, 'Parse error') !== false) {
            $layer_errors[] = $file->getFilename() . ": " . trim(explode("\n", $result)[0]);
        }
    }

    $total_errors += count($layer_errors);
    $status = count($layer_errors) === 0 ? "✅ OK" : "⚠️  " . count($layer_errors) . " errors";
    $output .= "$name: $status\n";

    if (!empty($layer_errors)) {
        foreach (array_slice($layer_errors, 0, 3) as $err) {
            $output .= "    - $err\n";
        }
    }
    $output .= "\n";
}

$output .= str_repeat("=", 60) . "\n";
$output .= "TOTAL ERRORS: $total_errors\n";

file_put_contents('validation_report.txt', $output);
echo $output;
