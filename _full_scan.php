<?php
// Full scan of ALL PHP files in app\Domains
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('app/Domains', RecursiveDirectoryIterator::SKIP_DOTS)
);

$ok = 0;
$err = 0;
$errors = [];

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    $out = shell_exec("php -l " . escapeshellarg($path) . " 2>&1");
    if (strpos($out, 'Parse error') !== false || strpos($out, 'Fatal error') !== false) {
        $err++;
        $short = trim(preg_replace('/^.*?(Parse error|Fatal error)/', '$1', $out));
        $short = preg_replace('/\s+/', ' ', $short);
        $errors[] = basename($path) . " → " . substr($short, 0, 100);
    } else {
        $ok++;
    }
}

echo "=== FULL SCAN: OK=$ok BROKEN=$err ===\n\n";
foreach ($errors as $e) {
    echo "ERR: $e\n";
}
