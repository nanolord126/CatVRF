<?php

declare(strict_types=1);

$testDir = __DIR__ . '/tests';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($testDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$count = 0;
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getRealPath();
    $content = file_get_contents($path);

    // Skip if already has extends
    if (strpos($content, 'extends TestCase') !== false || 
        strpos($content, 'extends ') !== false ||
        strpos($content, 'class') === false) {
        continue;
    }

    // Check if it has 'uses(' pattern (Pest format)
    if (strpos($content, 'uses(') === false) {
        continue;
    }

    // Get class name
    if (preg_match('/class\s+(\w+)\s*\{/', $content, $matches)) {
        $className = $matches[1];
        
        // Remove uses() calls and convert to PHPUnit format
        $new_content = $content;
        
        // Remove uses() calls
        $new_content = preg_replace('/uses\([^)]+\);\s*\n?/m', '', $new_content);
        
        // Remove beforeEach/afterEach if they exist
        $new_content = preg_replace('/beforeEach\(function\s*\(\)\s*\{[^}]*\}\);\s*\n?/m', '', $new_content);
        $new_content = preg_replace('/afterEach\(function\s*\(\)\s*\{[^}]*\}\);\s*\n?/m', '', $new_content);
        
        // Convert test() calls to public function test...()
        $new_content = preg_replace(
            "/test\('([^']+)',\s*function\s*\(\)\s*\{/",
            "public function test_\\1() {",
            $new_content
        );
        $new_content = str_replace('$this', '$this', $new_content);
        
        // Check if namespace exists
        if (strpos($new_content, 'namespace') === false) {
            $new_content = "<?php\n\ndeclare(strict_types=1);\n\n" . trim($new_content);
        } else {
            $new_content = "<?php\n\n" . trim($new_content);
        }
        
        // Add extends TestCase if not present
        if (preg_match('/^class\s+\w+\s*\{/', $new_content)) {
            $new_content = preg_replace('/^(class\s+\w+)\s*\{/', '$1 extends TestCase {', $new_content);
        }
        
        file_put_contents($path, $new_content);
        echo "Fixed: {$path}\n";
        $count++;
    }
}

echo "\nFixed $count test files.\n";
