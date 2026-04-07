<?php
$broken = file('_broken_files.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$ok = 0;
$err = 0;
foreach ($broken as $f) {
    $f = trim($f);
    if (!file_exists($f)) continue;
    $out = shell_exec("php -l " . escapeshellarg($f) . " 2>&1");
    if (strpos($out, 'Parse error') !== false || strpos($out, 'Fatal error') !== false) {
        $err++;
        $short = trim(preg_replace('/^.*?(Parse error|Fatal error)/', '$1', $out));
        $short = preg_replace('/\s+/', ' ', $short);
        echo "ERR: " . basename($f) . " → " . substr($short, 0, 120) . "\n";
    } else {
        $ok++;
    }
}
echo "\n=== FINAL: OK=$ok BROKEN=$err ===\n";
