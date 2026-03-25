<?php declare(strict_types=1);

// Replace all ->indexed() with ->index() (SQLite compatible)

$migrationsFolder = 'database/migrations';
$files = glob("{$migrationsFolder}/*.php");
$fixed = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    if (strpos($content, '->indexed()') === false) {
        continue;
    }
    
    $original = $content;
    $content = str_replace('->indexed()', '->index()', $content);
    
    if ($original !== $content) {
        file_put_contents($file, $content);
        echo "✅ Fixed: " . basename($file) . "\n";
        $fixed++;
    }
}

echo "\n✅ Fixed {$fixed} migration files (->indexed() → ->index())\n";
