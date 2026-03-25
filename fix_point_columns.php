<?php declare(strict_types=1);

/**
 * Fix point() columns - replace with string()
 */

$files = [
    'database/migrations/2026_03_25_000002_create_beauty_food_hotel_tables.php',
    'database/migrations/2026_03_25_000003_create_auto_inventory_commission_tables.php',
    'database/migrations/2026_03_25_000005_create_beauty_food_hotel_vertical_tables.php',
    'database/migrations/2026_03_25_000006_create_auto_forecast_embedding_tables.php',
    'database/migrations/2026_03_22_000000_create_beauty_salons_tables.php',
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    
    // Replace point() with string()
    $content = preg_replace(
        "/\\\$table->point\('(\w+)'\)([^;]*);/",
        "\$table->string('$1', 255)$2;",
        $content
    );
    
    file_put_contents($path, $content);
    echo "✅ Fixed: " . basename($file) . "\n";
}

echo "\n✅ All point() columns fixed!\n";
