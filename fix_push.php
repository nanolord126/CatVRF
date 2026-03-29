<?php
declare(strict_types=1);

chdir('c:\opt\kotvrf\CatVRF');

echo "Step 1: Check git status\n";
exec('git status --short', $out);
echo "Files staged: " . count($out) . "\n";

echo "\nStep 2: Check last commit\n";
exec('git log --oneline -1', $log);
echo implode("\n", $log) . "\n";

echo "\nStep 3: Check branches\n";
exec('git branch -a', $branches);
foreach ($branches as $b) {
    echo trim($b) . "\n";
}

echo "\nStep 4: Attempt PUSH main:master...\n";

// Try with SSH first (if configured)
$cmd = 'git push origin main:master --force 2>&1';
$output = shell_exec($cmd);

if ($output) {
    echo $output;
} else {
    echo "No output from git push (may indicate success or hanging)\n";
}

echo "\nStep 5: Verify push result\n";
exec('git status', $status);
echo implode("\n", $status) . "\n";

// Check if main:master was successful
exec('git branch -r', $newBranches);
echo "\nRemote branches after push:\n";
foreach ($newBranches as $b) {
    echo "  " . trim($b) . "\n";
}
?>
