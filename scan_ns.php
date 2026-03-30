<?php
declare(strict_types=1);

$baseDir = __DIR__ . '/app';
$issues = [];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS));

foreach ($it as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);

    // Найти namespace
    if (!preg_match('/^namespace\s+(.+?);/m', $content, $m)) {
        continue;
    }

    $ns = $m[1];

    // Определить ожидаемый namespace по пути
    $rel = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path);
    $rel = str_replace('\\', '/', $rel);
    $rel = preg_replace('/\.php$/', '', $rel);
    $expectedNs = str_replace('/', '\\', dirname($rel));
    $expectedNs = implode('\\', array_map(fn($p) => $p, explode('\\', $expectedNs)));
    // PSR-4: app/ => App\
    $expectedNs = preg_replace('/^app\\\\/', 'App\\', $expectedNs);
    $expectedNs = preg_replace('/^App\\\\/', 'App\\', $expectedNs);

    $problem = null;

    // 1. 3D namespace — невалидный PHP
    if (str_contains($ns, '\\3D') || str_contains($ns, '\\3d')) {
        $problem = "INVALID_3D: $ns";
    }

    // 2. Старые domain namespace (если Domains не правильные)
    if ($problem === null && preg_match('/^App\\\\Domains\\\\(.+?)\\\\/', $ns, $dm)) {
        $domain = $dm[1];
        $validDomains = [
            'Auto','Beauty','Education','Food','Hotels','ShortTermRentals',
            'RealEstate','Travel','Taxi','Logistics','Medical','Pet','Fashion',
            'Furniture','Electronics','Sports','Tickets','EventPlanning','Photography',
            'Pharmacy','HomeServices','Freelance','Consulting','Legal','Insurance',
            'Flowers','ConstructionAndRepair','Gardening','SportsNutrition',
            'VeganProducts','Confectionery','MeatShops','OfficeCatering','FarmDirect',
            'BooksAndLiterature','ToysAndGames','HobbyAndCraft','CleaningServices',
            'CarRental','MusicAndInstruments','Art','Collectibles','HouseholdGoods',
            'Stationery','PartySupplies','Veterinary','WeddingPlanning','Luxury',
            'PersonalDevelopment','Courses',
        ];
        if (!in_array($domain, $validDomains, true)) {
            $problem = "WRONG_DOMAIN: $domain in $ns";
        }
    }

    // 3. namespace не совпадает с путём
    if ($problem === null && $ns !== $expectedNs) {
        // только для app/Domains и app/Services
        if (str_starts_with($ns, 'App\\Domains') || str_starts_with($ns, 'App\\Services')) {
            $problem = "NS_MISMATCH: ns=$ns | expected=$expectedNs";
        }
    }

    if ($problem !== null) {
        $issues[] = ['file' => $path, 'ns' => $ns, 'problem' => $problem];
    }
}

echo 'Total issues: ' . count($issues) . PHP_EOL . PHP_EOL;

// Группировка по типу
$byType = [];
foreach ($issues as $i) {
    $type = explode(':', $i['problem'])[0];
    $byType[$type][] = $i;
}

foreach ($byType as $type => $items) {
    echo "=== $type (" . count($items) . ") ===" . PHP_EOL;
    foreach (array_slice($items, 0, 15) as $item) {
        $short = str_replace('c:/opt/kotvrf/CatVRF/', '', str_replace('\\', '/', $item['file']));
        echo "  $short" . PHP_EOL;
        echo "    ns: " . $item['ns'] . PHP_EOL;
    }
    if (count($items) > 15) {
        echo "  ... and " . (count($items) - 15) . " more" . PHP_EOL;
    }
    echo PHP_EOL;
}
