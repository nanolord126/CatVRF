<?php declare(strict_types=1);

// Replace all ->point() with ->string() for SQLite compatibility

$files = [
    'database/migrations/2026_03_22_002001_create_apartments_table.php',
    'database/migrations/2026_03_22_003001_create_grocery_stores_table.php',
    'database/migrations/2026_03_22_120000_create_grocery_tables.php',
    'database/migrations/2026_03_23_133610_create_car_dealership_tables.php',
    'database/migrations/2026_03_23_134615_create_pharmacy_tables.php',
    'database/migrations/2026_03_22_110001_create_apartments_table.php',
];

$fixed = 0;
foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "⚠️  File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Replace $table->point('...') with $table->string('...', 255)
    $content = preg_replace(
        '/\$table->point\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
        "\$table->string('\$1', 255)",
        $content
    );
    
    // Replace $table->point('...')->nullable() with $table->string('...', 255)->nullable()
    $content = preg_replace(
        '/\$table->point\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*->nullable\(/i',
        "\$table->string('\$1', 255)->nullable(",
        $content
    );
    
    if ($original !== $content) {
        file_put_contents($file, $content);
        echo "✅ Fixed: " . basename($file) . "\n";
        $fixed++;
    }
}

echo "\n✅ Fixed {$fixed} migration files (->point() → ->string())\n";
