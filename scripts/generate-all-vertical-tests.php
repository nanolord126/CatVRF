#!/usr/bin/env php
<?php

use Illuminate\Contracts\Console\Kernel;

declare(strict_types=1);

/**
 * Batch script to generate Pest tests for all 64 verticals
 *
 * Usage: php scripts/generate-all-vertical-tests.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$verticals = [
    'Medical', 'Payment', 'FraudML', 'Food', 'RealEstate', 'Travel', 'Auto',
    'Hotels', 'Electronics', 'Fitness', 'Sports', 'Luxury', 'Insurance', 'Legal',
    'Logistics', 'Education', 'CRM', 'Delivery', 'Analytics', 'Consulting',
    'Content', 'Freelance', 'EventPlanning', 'Staff', 'Inventory', 'Taxi',
    'Tickets', 'Wallet', 'Pet', 'WeddingPlanning', 'Veterinary', 'ToysAndGames',
    'Advertising', 'CarRental', 'Finances', 'Flowers', 'Furniture', 'Pharmacy',
    'Photography', 'ShortTermRentals', 'SportsNutrition', 'PersonalDevelopment',
    'HomeServices', 'Gardening', 'Geo', 'GeoLogistics', 'GroceryAndDelivery',
    'FarmDirect', 'MeatShops', 'OfficeCatering', 'PartySupplies', 'Confectionery',
    'ConstructionAndRepair', 'CleaningServices', 'Communication', 'BooksAndLiterature',
    'Collectibles', 'HobbyAndCraft', 'HouseholdGoods', 'Marketplace',
    'MusicAndInstruments', 'VeganProducts', 'Art', 'Beauty', 'Fashion',
];

echo 'Generating Pest tests for all '.count($verticals)." verticals...\n\n";

$successCount = 0;
$failCount = 0;
$skippedCount = 0;

foreach ($verticals as $vertical) {
    echo "Processing {$vertical}... ";

    $exitCode = $app->make(Kernel::class)->call('vertical:tests', [
        'vertical' => $vertical,
        '--type' => 'all',
        '--force' => true,
    ]);

    if ($exitCode === 0) {
        echo "✅ SUCCESS\n";
        $successCount++;
    } elseif ($exitCode === 1) {
        echo "⚠️  SKIPPED (vertical not found)\n";
        $skippedCount++;
    } else {
        echo "❌ FAILED\n";
        $failCount++;
    }
}

echo "\n";
echo "========================================\n";
echo "Test Generation Summary\n";
echo "========================================\n";
echo 'Total verticals: '.count($verticals)."\n";
echo "Success: {$successCount}\n";
echo "Skipped: {$skippedCount}\n";
echo "Failed: {$failCount}\n";
echo "========================================\n";

if ($failCount > 0) {
    echo "\n⚠️  Some verticals failed. Check the output above for details.\n";
    exit(1);
} else {
    echo "\n✅ All tests generated successfully!\n";
    exit(0);
}
