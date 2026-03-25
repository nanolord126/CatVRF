<?php declare(strict_types=1);

// Fix: Remove COMMENT ON TABLE statements from migrations (SQLite incompatible)

$migrationsFolder = 'database/migrations';
$pattern = 'COMMENT ON TABLE';

$files = glob("{$migrationsFolder}/*.php");
$fixed = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Skip if no matches
    if (strpos($content, $pattern) === false) {
        continue;
    }
    
    // Remove DB::statement("COMMENT ON TABLE...") lines
    $original = $content;
    $content = preg_replace(
        '/\s*DB::statement\s*\(\s*["\']COMMENT ON TABLE[^)]+\)\s*;?\s*\n/i',
        "\n",
        $content
    );
    
    // Also remove raw COMMENT ON TABLE statements (from raw SQL blocks)
    $content = preg_replace(
        '/\s*COMMENT ON TABLE[^\;]+\;?\s*\n/i',
        "\n",
        $content
    );
    
    if ($original !== $content) {
        file_put_contents($file, $content);
        echo "✅ Fixed: " . basename($file) . "\n";
        $fixed++;
    }
}

echo "\n✅ Fixed {$fixed} migration files\n";
echo "✅ All COMMENT ON TABLE statements removed (SQLite compatible)\n";
