<?php
declare(strict_types=1);

$errors = [];
$dir = new RecursiveDirectoryIterator('app', RecursiveDirectoryIterator::SKIP_DOTS);
$iter = new RecursiveIteratorIterator($dir);
$checked = 0;
$failed = 0;

foreach ($iter as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $output = [];
    $code = 0;
    exec('php -l ' . escapeshellarg($path) . ' 2>&1', $output, $code);
    $checked++;
    if ($code !== 0) {
        $failed++;
        $errors[] = $path . ': ' . implode(' ', $output);
    }
}

echo "=== PHP SYNTAX CHECK ===\n";
echo "Checked: {$checked}\n";
echo "Passed:  " . ($checked - $failed) . "\n";
echo "Failed:  {$failed}\n";

if ($failed > 0) {
    echo "\n=== ERRORS ===\n";
    foreach ($errors as $e) {
        echo $e . "\n";
    }
}
