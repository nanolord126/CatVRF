<?php declare(strict_types=1);

// Remove all DB::statement("COMMENT ON TABLE...") lines from migration files

$file = 'database/migrations/2026_03_22_000000_create_beauty_salons_tables.php';

$content = file_get_contents($file);
$lines = explode("\n", $content);
$result = [];

foreach ($lines as $line) {
    // Skip lines that contain DB::statement with COMMENT ON TABLE
    if (strpos($line, 'DB::statement') !== false && strpos($line, 'COMMENT ON TABLE') !== false) {
        continue;
    }
    $result[] = $line;
}

$newContent = implode("\n", $result);
file_put_contents($file, $newContent);

echo "✅ Fixed: 2026_03_22_000000_create_beauty_salons_tables.php\n";
echo "✅ Removed DB::statement COMMENT ON TABLE lines\n";
