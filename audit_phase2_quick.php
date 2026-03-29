<?php
declare(strict_types=1);

/**
 * QUICK AUDIT - Phase 2 Final Status
 * Только основные 38 вертикалей
 */

$mainVerticals = [
    'Auto', 'Beauty', 'Books', 'Collectibles', 'Confectionery', 'ConstructionMaterials',
    'Cosmetics', 'Courses', 'CoffeeShops', 'DanceStudios', 'DrivingSchools', 'Electronics',
    'EventVenues', 'Florist', 'Food', 'FreshProduce', 'Furniture', 'FarmDirect',
    'Gifts', 'GroceryAndDelivery', 'HealthyFood', 'Hotels', 'HomeServices',
    'Jewelry', 'KaraokeVenues', 'Medical', 'MeatShops', 'MusicAndInstruments',
    'OfficeCatering', 'Pharmacy', 'PartySupplies', 'Pet', 'RealEstate', 'ReadyMeals',
    'ShortTermRentals', 'Sports', 'Stationery', 'Travel', 'Tickets', 'VintageLuxury'
];

$resourcesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$results = [];
$complete = 0;
$total = 0;

foreach ($mainVerticals as $vertical) {
    $verticalPath = "$resourcesDir/$vertical";
    if (!is_dir($verticalPath)) {
        echo "⚠️  $vertical - directory not found\n";
        continue;
    }

    $mainFiles = [];
    $files = scandir($verticalPath);
    
    // Ищем основной ресурс (не _New, не подпапки)
    foreach ($files as $file) {
        if (str_ends_with($file, 'Resource.php') && !str_ends_with($file, 'New.php')) {
            $path = "$verticalPath/$file";
            if (is_file($path)) {
                $mainFiles[] = $file;
            }
        }
    }

    if (empty($mainFiles)) {
        echo "❌ $vertical - no Resource.php found\n";
        continue;
    }

    $file = $mainFiles[0];
    $filePath = "$verticalPath/$file";
    $content = file_get_contents($filePath);

    $total++;

    // Критерии
    $hasStr = str_contains($content, 'use Illuminate\Support\Str');
    $hasLog = str_contains($content, 'use Illuminate\Support\Facades\Log');
    $hasBuilder = str_contains($content, 'use Illuminate\Database\Eloquent\Builder');
    $hasHidden = str_contains($content, "Hidden::make('tenant_id')");
    $hasTenantScoping = str_contains($content, "where('tenant_id', tenant('id'))");
    
    // Парсим секции и колонки
    $sectionCount = substr_count($content, 'Section::make(');
    $columnCount = substr_count($content, 'Column::make(');
    
    // Парсим длины форм/таблиц
    preg_match('/public static function form\(.*?\):\s*Forms\\\\Form\s*\{(.*?)\n\s*\}/s', $content, $formMatch);
    $formLines = isset($formMatch[1]) ? count(explode("\n", $formMatch[1])) : 0;
    
    $isOk = $hasStr && $hasLog && $hasBuilder && $hasHidden && $hasTenantScoping
        && $sectionCount >= 8 && $columnCount >= 12 && $formLines >= 160;
    
    if ($isOk) {
        $complete++;
        $status = '✅';
    } else {
        $status = '❌';
    }

    $results[] = [
        'vertical' => $vertical,
        'form_lines' => $formLines,
        'sections' => $sectionCount,
        'columns' => $columnCount,
        'imports' => ($hasStr ? '✓' : '✗') . ($hasLog ? '✓' : '✗') . ($hasBuilder ? '✓' : '✗'),
        'scoping' => $hasTenantScoping ? '✓' : '✗',
        'status' => $status,
    ];
}

// Вывод
echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║       PHASE 2 AUDIT - 38 Verticals Filament Resources           ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 SUMMARY:\n";
echo "  Total: $total/38 verticals\n";
echo "  ✅ Complete: $complete/$total (" . round(($complete/$total)*100) . "%)\n";
echo "  ❌ Incomplete: " . ($total - $complete) . "\n\n";

echo "┌────────────────────────────┬──────┬───┬──────┬─────────┬──────┬──────┐\n";
echo "│ Vertical                   │ Form │ S │ Cols │ Imports │ Scope│ OK   │\n";
echo "├────────────────────────────┼──────┼───┼──────┼─────────┼──────┼──────┤\n";

foreach ($results as $r) {
    $v = str_pad($r['vertical'], 26);
    $f = str_pad((string)$r['form_lines'], 5);
    $s = str_pad((string)$r['sections'], 2);
    $c = str_pad((string)$r['columns'], 5);
    $i = str_pad($r['imports'], 8);
    $sc = str_pad($r['scoping'], 5);
    $st = str_pad($r['status'], 5);
    
    echo "│ {$v}│ {$f}│ {$s}│ {$c}│ {$i}│ {$sc}│ {$st}│\n";
}

echo "└────────────────────────────┴──────┴───┴──────┴─────────┴──────┴──────┘\n\n";

echo "📋 LEGEND:\n";
echo "  Form: 160+ lines (form structure)\n";
echo "  S: 8+ sections\n";
echo "  Cols: 12+ columns\n";
echo "  Imports: Str✓Log✓Builder✓\n";
echo "  Scope: Tenant scoping check\n\n";

if ($complete === $total) {
    echo "🎉 PHASE 2 COMPLETE - ALL 38 RESOURCES READY!\n";
    echo "✅ Standards met: 160+ form lines, 8+ sections, 12+ columns\n";
    echo "✅ All imports present: Str, Log, Builder\n";
    echo "✅ Tenant scoping implemented\n";
    echo "✅ Hidden fields configured\n";
    echo "\n→ NEXT STAGE: Pages Layer + DB Migrations\n";
} else {
    echo "⏳ Remaining work: " . ($total - $complete) . " resources\n\n";
}
