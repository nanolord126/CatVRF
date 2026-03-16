<?php
/**
 * Count and categorize PHP errors in app/ directory
 */

$appDir = __DIR__ . '/app';
$errors = [];

function checkPhpFile($file) {
    $content = file_get_contents($file);
    $localErrors = [];
    
    // Check 1: Missing use statements for common classes
    if (preg_match('/\bThrowable\b/', $content) && !preg_match('/use\s+Throwable;/m', $content)) {
        $localErrors[] = 'Missing: use Throwable';
    }
    
    if (preg_match('/\bGate\b/', $content) && !preg_match('/use.*Gate;/m', $content)) {
        $localErrors[] = 'Missing: use Gate';
    }
    
    // Check 2: Undefined class references without imports
    if (preg_match('/\b(EmployeeResource|BeautyProductResource)\b/', $content, $m) && 
        !preg_match('/use.*' . preg_quote($m[1]) . '/m', $content)) {
        $localErrors[] = 'Undefined Resource class: ' . $m[1];
    }
    
    // Check 3: Duplicate method declarations
    if (preg_match_all('/public\s+function\s+(\w+)\s*\(/', $content, $m)) {
        $counts = array_count_values($m[1]);
        foreach ($counts as $method => $count) {
            if ($count > 1) {
                $localErrors[] = "Duplicate method: $method (x$count)";
            }
        }
    }
    
    // Check 4: Missing import but used
    if (preg_match('/DatabaseManager/', $content) && !preg_match('/use.*DatabaseManager/m', $content)) {
        $localErrors[] = 'Missing: use DatabaseManager';
    }
    
    return $localErrors;
}

function walkDir($dir, &$errors) {
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..'  || $item === 'Tests') continue;
        
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            walkDir($path, $errors);
        } elseif (str_ends_with($item, '.php')) {
            $fileErrors = checkPhpFile($path);
            if (!empty($fileErrors)) {
                $relPath = str_replace(__DIR__ . '/', '', $path);
                $errors[$relPath] = $fileErrors;
            }
        }
    }
}

walkDir($appDir, $errors);

$totalIssues = array_sum(array_map('count', $errors));

echo "=== PHP Code Quality Audit (app/ only) ===\n\n";
echo "Files with issues: " . count($errors) . "\n";
echo "Total issues found: $totalIssues\n\n";

$categories = [];
foreach ($errors as $file => $issues) {
    foreach ($issues as $issue) {
        $cat = explode(':', $issue)[0];
        $categories[$cat] = ($categories[$cat] ?? 0) + 1;
    }
}

echo "Issues by category:\n";
foreach ($categories as $cat => $count) {
    echo "  - $cat: $count\n";
}

echo "\n";
foreach (array_slice($errors, 0, 20) as $file => $issues) {
    echo "FILE: $file\n";
    foreach ($issues as $issue) {
        echo "  ⚠ $issue\n";
    }
    echo "\n";
}

if (count($errors) > 20) {
    echo "... and " . (count($errors) - 20) . " more files\n";
}

file_put_contents('php_code_audit.json', json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "\nDetailed report: php_code_audit.json\n";
?>
