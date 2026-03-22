<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Running Beauty migrations...\n";

$migrator = app('migrator');
$migrator->run([__DIR__ . '/database/migrations'], ['pretend' => false]);

echo "✅ Beauty migrations completed!\n";
echo "\nChecking created tables:\n";

$tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE 'beauty%' OR tablename IN ('masters', 'appointments', 'portfolio_items')");

foreach ($tables as $table) {
    echo "  ✓ {$table->tablename}\n";
}

echo "\nTotal Beauty tables: " . count($tables) . "\n";
