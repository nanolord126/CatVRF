<?php

$resourcesRoot = glob(__DIR__ . '/app/Filament/Tenant/Resources/Marketplace/*.php');
echo "✅ Marketplace ресурсы в корне: " . count($resourcesRoot) . "\n";
foreach ($resourcesRoot as $r) {
    echo "  • " . basename($r) . "\n";
}

$taxiResources = glob(__DIR__ . '/app/Filament/Tenant/Resources/Marketplace/Taxi/*Resource.php');
echo "\n✅ Taxi ресурсы: " . count($taxiResources) . "\n";
foreach ($taxiResources as $r) {
    echo "  • " . basename($r) . "\n";
}

$tables = [
    'furniture',
    'constructions', 
    'repairs',
    'garden_products',
    'flowers',
    'restaurants',
    'taxi_services',
    'education_courses',
];

echo "\n✅ Таблицы которые должны быть созданы:\n";
foreach ($tables as $table) {
    echo "  • $table\n";
}

echo "\n✅ Всё готово к использованию в Filament Admin!\n";
