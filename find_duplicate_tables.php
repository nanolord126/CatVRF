<?php declare(strict_types=1);

// Find all table creates across migrations
$migrationsFolder = 'database/migrations';
$files = glob("{$migrationsFolder}/*.php");
$tables = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Find all Schema::create('tablename'
    preg_match_all('/Schema::create\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*function/', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $table) {
            if (!isset($tables[$table])) {
                $tables[$table] = [];
            }
            $tables[$table][] = basename($file);
        }
    }
}

// Find duplicates
foreach ($tables as $table => $files) {
    if (count($files) > 1) {
        echo "⚠️  DUPLICATE TABLE: {$table}\n";
        foreach ($files as $file) {
            echo "   - {$file}\n";
        }
        echo "\n";
    }
}

echo "✅ Total tables: " . count($tables) . "\n";
echo "✅ Check complete\n";
