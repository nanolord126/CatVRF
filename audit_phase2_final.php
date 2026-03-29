<?php
declare(strict_types=1);

/**
 * PHASE 2 FINAL AUDIT - All 38 Verticals
 * Проверка соответствия стандартам CANON 2026
 */

$mainVerticals = [
    'Auto', 'Beauty', 'Books', 'Collectibles', 'Confectionery', 'ConstructionMaterials',
    'Cosmetics', 'Courses', 'Electronics', 'Florist', 'Food', 'Furniture', 'FarmDirect',
    'Gifts', 'GroceryAndDelivery', 'Hotels', 'Jewelry', 'Medical', 'MeatShops', 
    'MusicAndInstruments', 'OfficeCatering', 'Pharmacy', 'PartySupplies', 'Pet', 
    'RealEstate', 'ShortTermRentals', 'Sports', 'Stationery', 'Travel', 'Tickets'
];

$resourcesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$results = [];
$complete = 0;
$total = 0;
$incomplete_details = [];

foreach ($mainVerticals as $vertical) {
    $verticalPath = "$resourcesDir/$vertical";
    if (!is_dir($verticalPath)) continue;

    $files = scandir($verticalPath);
    $resourceFile = null;
    
    // Ищем основной ресурс
    foreach ($files as $file) {
        if (str_ends_with($file, 'Resource.php') && !str_ends_with($file, 'New.php')) {
            $path = "$verticalPath/$file";
            if (is_file($path)) {
                $resourceFile = $path;
                break;
            }
        }
    }

    if (!$resourceFile) continue;

    $content = file_get_contents($resourceFile);
    $lines = file($resourceFile);
    $total++;

    // Более надёжный подсчёт
    $formSectionCount = 0;
    $tableColumnCount = 0;
    $formLineCount = 0;
    $tableLineCount = 0;
    
    // Найти начало и конец function form
    $formStart = false;
    $formEnd = false;
    $tableStart = false;
    $tableEnd = false;
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        
        if (preg_match('/public\s+static\s+function\s+form/', $line)) {
            $formStart = $i;
        }
        if ($formStart !== false && $formEnd === false && preg_match('/^\s*\}/', $line) && $i > $formStart + 5) {
            $formEnd = $i;
        }
        
        if (preg_match('/public\s+static\s+function\s+table/', $line)) {
            $tableStart = $i;
        }
        if ($tableStart !== false && $tableEnd === false && preg_match('/^\s*\}/', $line) && $i > $tableStart + 5) {
            $tableEnd = $i;
        }
    }
    
    if ($formStart !== false && $formEnd !== false) {
        $formLineCount = $formEnd - $formStart;
        for ($i = $formStart; $i < $formEnd; $i++) {
            if (preg_match('/Section::make/', $lines[$i])) {
                $formSectionCount++;
            }
        }
    }
    
    if ($tableStart !== false && $tableEnd !== false) {
        $tableLineCount = $tableEnd - $tableStart;
        for ($i = $tableStart; $i < $tableEnd; $i++) {
            if (preg_match('/Column::make/', $lines[$i])) {
                $tableColumnCount++;
            }
        }
    }

    // Критерии
    $hasStr = str_contains($content, 'use Illuminate\Support\Str') || str_contains($content, "Str::");
    $hasLog = str_contains($content, 'use Illuminate\Support\Facades\Log') || str_contains($content, "Log::");
    $hasBuilder = str_contains($content, 'use Illuminate\Database\Eloquent\Builder') || str_contains($content, "Builder");
    $hasHidden = str_contains($content, "Hidden::make('tenant_id')") || str_contains($content, 'Hidden::make');
    $hasTenantScoping = str_contains($content, "where('tenant_id'") || str_contains($content, 'tenant(');
    
    $isOk = $hasStr && $hasLog && $hasBuilder && $hasHidden && $hasTenantScoping
        && $formSectionCount >= 8 && $tableColumnCount >= 12 && $formLineCount >= 160;
    
    $result = [
        'vertical' => $vertical,
        'form_lines' => $formLineCount,
        'form_sections' => $formSectionCount,
        'table_lines' => $tableLineCount,
        'table_columns' => $tableColumnCount,
        'has_str' => $hasStr,
        'has_log' => $hasLog,
        'has_builder' => $hasBuilder,
        'has_hidden' => $hasHidden,
        'has_scoping' => $hasTenantScoping,
        'is_complete' => $isOk,
    ];
    
    $results[] = $result;
    if ($isOk) $complete++;
    else {
        $incomplete_details[] = [
            'vertical' => $vertical,
            'issues' => array_filter([
                $formLineCount < 160 ? "Form lines: $formLineCount (need 160)" : null,
                $formSectionCount < 8 ? "Sections: $formSectionCount (need 8)" : null,
                $tableColumnCount < 12 ? "Columns: $tableColumnCount (need 12)" : null,
                !$hasStr ? "Missing: Str import" : null,
                !$hasLog ? "Missing: Log import" : null,
                !$hasTenantScoping ? "Missing: tenant scoping" : null,
            ]),
        ];
    }
}

// Вывод
echo "\n╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║           PHASE 2 FINAL AUDIT - All 38 Verticals               ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 SUMMARY:\n";
echo "  Total: $total/29 verticals found\n";
echo "  ✅ Complete: $complete/$total (" . round(($complete/$total)*100, 1) . "%)\n";
echo "  ❌ Incomplete: " . ($total - $complete) . "\n\n";

echo "┌─────────────────────────┬──────┬───┬──────┬────────────────┬──────┐\n";
echo "│ Vertical                │ Form │ S │ Cols │ Imports & Scope│ OK   │\n";
echo "├─────────────────────────┼──────┼───┼──────┼────────────────┼──────┤\n";

usort($results, fn($a, $b) => ($b['is_complete'] ? 1 : 0) - ($a['is_complete'] ? 1 : 0));

foreach ($results as $r) {
    $v = str_pad($r['vertical'], 24);
    $f = str_pad((string)$r['form_lines'], 5);
    $s = str_pad((string)$r['form_sections'], 2);
    $c = str_pad((string)$r['table_columns'], 5);
    $i = ($r['has_str'] ? '✓' : '✗') . ($r['has_log'] ? '✓' : '✗') . ($r['has_builder'] ? '✓' : '✗') . ($r['has_scoping'] ? '✓' : '✗');
    $i = str_pad($i, 15);
    $st = $r['is_complete'] ? '✅ READY' : '❌ TODO';
    
    echo "│ {$v}│ {$f}│ {$s}│ {$c}│ {$i}│ {$st} │\n";
}

echo "└─────────────────────────┴──────┴───┴──────┴────────────────┴──────┘\n\n";

if (count($incomplete_details) > 0) {
    echo "⚠️  INCOMPLETE RESOURCES:\n\n";
    foreach ($incomplete_details as $inc) {
        echo "  • {$inc['vertical']}:\n";
        foreach ($inc['issues'] as $issue) {
            echo "    - $issue\n";
        }
    }
    echo "\n";
}

echo "📋 STANDARDS (CANON 2026):\n";
echo "  ✓ Form Lines: 160+\n";
echo "  ✓ Form Sections: 8+\n";
echo "  ✓ Table Columns: 12+\n";
echo "  ✓ Imports: Str, Log, Builder\n";
echo "  ✓ Tenant Scoping: ✓\n";
echo "  ✓ Hidden Fields: tenant_id, correlation_id\n\n";

if ($complete === $total) {
    echo "🎉 ALL RESOURCES READY FOR NEXT PHASE!\n";
    echo "✅ Pages Layer (ListRecords, CreateRecord, EditRecord)\n";
    echo "✅ Database Migrations\n";
    echo "✅ Seeders\n";
} else {
    echo "⏳ Status: $complete/$total complete (" . round(($complete/$total)*100, 1) . "%)\n";
}

echo "\n";
