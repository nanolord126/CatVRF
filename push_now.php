<?php
declare(strict_types=1);

// Change to repo directory
chdir('c:\opt\kotvrf\CatVRF');

// Execute git push
$cmd = 'git push origin main:master --force -v 2>&1';
echo "Executing: $cmd\n";
echo str_repeat("=", 80) . "\n";

$output = [];
$returnCode = 0;
exec($cmd, $output, $returnCode);

foreach ($output as $line) {
    echo $line . "\n";
}

echo str_repeat("=", 80) . "\n";
echo "\nReturn code: $returnCode\n";

if ($returnCode === 0) {
    echo "\n✅ PUSH SUCCESSFUL!\n";
    
    // Verify it worked
    echo "\nVerifying on GitHub...\n";
    exec('git log --oneline -1', $log);
    echo "Latest commit: " . implode("\n", $log) . "\n";
    
    exec('git branch -r', $branches);
    echo "\nRemote branches:\n";
    foreach ($branches as $b) {
        if (strpos($b, 'master') !== false || strpos($b, 'main') !== false) {
            echo "  $b\n";
        }
    }
} else {
    echo "\n❌ PUSH FAILED (return code: $returnCode)\n";
}
?>
