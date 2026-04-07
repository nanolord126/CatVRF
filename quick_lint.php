<?php
// Quick PHP lint scan - outputs only failures
$dir = $argv[1] ?? 'app/';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$ok = 0;
$errors = [];

foreach ($it as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    $out = shell_exec("php -l " . escapeshellarg($path) . " 2>&1");
    if (preg_match('/Parse error|Fatal error/', $out)) {
        $errors[] = ['file' => $path, 'error' => trim($out)];
    } else {
        $ok++;
    }
}

echo "\n=== SCAN COMPLETE ===\n";
echo "OK: $ok | ERRORS: " . count($errors) . "\n\n";
foreach ($errors as $e) {
    echo "FAIL: {$e['file']}\n  {$e['error']}\n\n";
}
