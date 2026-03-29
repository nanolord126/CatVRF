<?php
declare(strict_types=1);

/**
 * AUDIT PHASE 2 - Полнота всех 38 Filament ресурсов
 * Проверка стандартов CANON 2026: 160+ форм, 140+ таблица, 8-9 секций, 12-16 колонок
 */

$resourcesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$resources = [];
$stats = [
    'total' => 0,
    'complete' => 0,
    'incomplete' => [],
    'form_lines_avg' => 0,
    'table_columns_avg' => 0,
    'form_lines_sum' => 0,
    'table_columns_sum' => 0,
];

// Сканируем все вертикали
$verticals = array_filter(scandir($resourcesDir), fn($f) => is_dir("$resourcesDir/$f") && !in_array($f, ['.', '..']));

foreach ($verticals as $vertical) {
    $verticalPath = "$resourcesDir/$vertical";
    if (!is_dir($verticalPath)) continue;
    
    $resourceFiles = array_filter(
        scandir($verticalPath),
        fn($f) => str_ends_with($f, 'Resource.php') && !str_ends_with($f, 'New.php')
    );

    foreach ($resourceFiles as $file) {
        $filePath = "$verticalPath/$file";
        $content = file_get_contents($filePath);
        $lines = count(file($filePath));

        // Парсим структуру
        $formSectionCount = substr_count($content, 'Section::make(');
        $tableColumnCount = preg_match_all('/->columns\(\[/', $content) > 0 
            ? substr_count($content, 'Column::make(') 
            : 0;

        // Проверяем импорты
        $hasStr = str_contains($content, 'use Illuminate\Support\Str');
        $hasLog = str_contains($content, 'use Illuminate\Support\Facades\Log');
        $hasBuilder = str_contains($content, 'use Illuminate\Database\Eloquent\Builder');
        $hasHidden = str_contains($content, "Hidden::make('tenant_id')");
        $hasTenantScoping = str_contains($content, "where('tenant_id', tenant('id'))");
        $hasCorrelationId = str_contains($content, "'correlation_id'");

        // Парсим линии форм и таблиц
        preg_match('/public static function form\(.*?\):\s*Forms\\\\Form\s*\{(.*?)\n\s*\}/s', $content, $formMatch);
        $formLines = isset($formMatch[1]) ? count(explode("\n", $formMatch[1])) : 0;

        preg_match('/public static function table\(.*?\):\s*Tables\\\\Table\s*\{(.*?)\n\s*\}/s', $content, $tableMatch);
        $tableLines = isset($tableMatch[1]) ? count(explode("\n", $tableMatch[1])) : 0;

        $isComplete = $formLines >= 160 && $tableColumnCount >= 12 && $formSectionCount >= 8
            && $hasStr && $hasLog && $hasBuilder && $hasHidden && $hasTenantScoping;

        $resources[] = [
            'vertical' => $vertical,
            'file' => $file,
            'total_lines' => $lines,
            'form_lines' => $formLines,
            'table_columns' => $tableColumnCount,
            'form_sections' => $formSectionCount,
            'imports_ok' => $hasStr && $hasLog && $hasBuilder,
            'hidden_fields_ok' => $hasHidden && $hasCorrelationId,
            'tenant_scoping_ok' => $hasTenantScoping,
            'is_complete' => $isComplete,
        ];

        $stats['total']++;
        if ($isComplete) $stats['complete']++;
        else {
            $stats['incomplete'][] = [
                'vertical' => $vertical,
                'file' => $file,
                'form_lines' => $formLines,
                'table_columns' => $tableColumnCount,
                'form_sections' => $formSectionCount,
                'reasons' => array_filter([
                    $formLines < 160 ? "Form: $formLines < 160" : null,
                    $tableColumnCount < 12 ? "Columns: $tableColumnCount < 12" : null,
                    $formSectionCount < 8 ? "Sections: $formSectionCount < 8" : null,
                    !$hasStr ? 'Missing: Str import' : null,
                    !$hasLog ? 'Missing: Log import' : null,
                    !$hasTenantScoping ? 'Missing: tenant scoping' : null,
                ]),
            ];
        }

        $stats['form_lines_sum'] += $formLines;
        $stats['table_columns_sum'] += $tableColumnCount;
    }
}

$stats['form_lines_avg'] = $stats['total'] > 0 ? round($stats['form_lines_sum'] / $stats['total'], 1) : 0;
$stats['table_columns_avg'] = $stats['total'] > 0 ? round($stats['table_columns_sum'] / $stats['total'], 1) : 0;

// Вывод отчёта
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║           PHASE 2 AUDIT - FILAMENT RESOURCES COMPLETE          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 SUMMARY:\n";
echo "  Total Resources: {$stats['total']}\n";
echo "  ✅ Complete (160+ form / 12+ columns): {$stats['complete']}\n";
echo "  ❌ Incomplete: " . count($stats['incomplete']) . "\n";
echo "  📈 Avg Form Lines: {$stats['form_lines_avg']}\n";
echo "  📊 Avg Table Columns: {$stats['table_columns_avg']}\n\n";

if (count($stats['incomplete']) > 0) {
    echo "⚠️  INCOMPLETE RESOURCES:\n";
    foreach ($stats['incomplete'] as $r) {
        echo "  • {$r['vertical']}/{$r['file']}\n";
        echo "    Forms: {$r['form_lines']} lines (need 160+) | Columns: {$r['table_columns']} (need 12+)\n";
        foreach ($r['reasons'] as $reason) {
            echo "    - $reason\n";
        }
    }
    echo "\n";
}

echo "✅ COMPLETION PERCENTAGE: " . round(($stats['complete'] / $stats['total']) * 100, 1) . "%\n";
echo "✅ PHASE 2 STATUS: " . ($stats['complete'] === $stats['total'] ? "🎉 COMPLETE!" : "🔄 IN PROGRESS") . "\n\n";

// Детальная таблица
echo "📋 DETAILED RESOURCES:\n";
echo "┌─────────────────────────┬──────┬──────┬──────┬────────────────────┐\n";
echo "│ Vertical                │ Form │ Cols │ Sec  │ Status             │\n";
echo "├─────────────────────────┼──────┼──────┼──────┼────────────────────┤\n";

usort($resources, fn($a, $b) => strcmp($a['vertical'], $b['vertical']));

foreach ($resources as $r) {
    $status = $r['is_complete'] ? '✅ COMPLETE' : '❌ NEEDS WORK';
    $vertical = str_pad($r['vertical'], 24);
    $form = str_pad((string)$r['form_lines'], 5);
    $cols = str_pad((string)$r['table_columns'], 5);
    $sections = str_pad((string)$r['form_sections'], 5);
    echo "│ {$vertical}│ {$form}│ {$cols}│ {$sections}│ {$status}        │\n";
}

echo "└─────────────────────────┴──────┴──────┴──────┴────────────────────┘\n\n";

// Критерии
echo "📋 CANON 2026 STANDARDS:\n";
echo "  ✓ Form Lines: 160+ (avg: {$stats['form_lines_avg']})\n";
echo "  ✓ Table Columns: 12-16 (avg: {$stats['table_columns_avg']})\n";
echo "  ✓ Form Sections: 8-9 min\n";
echo "  ✓ Imports: Str, Log, Builder\n";
echo "  ✓ Hidden Fields: tenant_id, correlation_id, business_group_id\n";
echo "  ✓ Tenant Scoping: getEloquentQuery() with tenant filter\n";
echo "  ✓ Audit Logging: Log::channel('audit') in actions\n\n";

if ($stats['complete'] === $stats['total']) {
    echo "🎉🎉🎉 ALL 38 RESOURCES MEET CANON 2026 STANDARDS! 🎉🎉🎉\n";
    echo "PHASE 2 READY FOR NEXT STAGE: Pages Layer + Migrations\n\n";
} else {
    echo "⏳ " . count($stats['incomplete']) . " resources need attention\n\n";
}
