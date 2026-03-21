<?php

$dir = __DIR__ . '/app/Policies';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    if (strpos($file, 'Domain') !== false) continue;
    $content = file_get_contents($file);
    
    // We want to add tenant_id check in policies.
    // Most policies have methods: view, create, update, delete.
    // Actually, I can just do this safely with a robust overwrite.
    
    // Let's just output the methods and what arguments they take to understand.
    preg_match_all('/public function (\w+)\(([^)]+)\)/', $content, $matches);
    echo "--- " . basename($file) . " ---\n";
    foreach($matches[1] as $idx => $m) {
        // echo "  $m: " . $matches[2][$idx] . "\n";
    }
}
