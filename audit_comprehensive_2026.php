<?php declare(strict_types=1);

$resourcesDir = 'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources';
$verticals = scandir($resourcesDir);
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'resources' => ['total' => 0, 'pass' => 0, 'fail' => 0, 'details' => []],
    'pages' => ['total' => 0, 'pass' => 0, 'fail' => 0, 'details' => []],
    'violations' => [],
];

// РЕСУРСЫ
foreach ($verticals as $vertical) {
    if (in_array($vertical, ['.', '..'])) continue;
    $resourcePath = "$resourcesDir/$vertical/{$vertical}Resource.php";
    
    if (!file_exists($resourcePath)) continue;
    
    $report['resources']['total']++;
    $content = file_get_contents($resourcePath);
    
    $checks = [
        'declare_strict' => str_contains($content, 'declare(strict_types=1)'),
        'namespace' => str_contains($content, "namespace App\\Filament\\Tenant\\Resources"),
        'model_defined' => str_contains($content, 'protected static ?string $model'),
        'form_method' => str_contains($content, 'public static function form'),
        'table_method' => str_contains($content, 'public static function table'),
        'tenant_scoping' => str_contains($content, "filament()->getTenant()->id"),
        'correlation_id' => str_contains($content, 'correlation_id'),
        'log_channel' => str_contains($content, "Log::channel('audit')"),
        'db_transaction' => str_contains($content, 'DB::transaction'),
        'hidden_fields' => str_contains($content, 'Hidden::make'),
        'form_sections' => preg_match_all('/Section::make/', $content) >= 8,
        'table_columns' => preg_match_all('/Column::make|->columns/', $content) >= 10,
    ];
    
    $lineCount = count(file($resourcePath));
    $syntax = shell_exec("php -l \"$resourcePath\" 2>&1");
    
    if (all($checks)) {
        $report['resources']['pass']++;
        $status = '✅ PASS';
    } else {
        $report['resources']['fail']++;
        $status = '❌ FAIL';
        $report['violations'][] = "$vertical Resource: " . implode(', ', array_keys(array_filter($checks, fn($v) => !$v)));
    }
    
    $report['resources']['details'][] = [
        'vertical' => $vertical,
        'status' => $status,
        'lines' => $lineCount,
        'syntax' => $syntax ? '✓' : '✗',
        'checks' => $checks,
    ];
}

// СТРАНИЦЫ
$pagesDir = "$resourcesDir/*/Pages";
foreach (glob($pagesDir, GLOB_BRACE) as $pageDir) {
    if (!is_dir($pageDir)) continue;
    
    $pages = glob("$pageDir/*.php");
    foreach ($pages as $pagePath) {
        $report['pages']['total']++;
        $content = file_get_contents($pagePath);
        
        $checks = [
            'declare_strict' => str_contains($content, 'declare(strict_types=1)'),
            'extends_resource' => str_contains($content, 'extends ') && str_contains($content, 'Page'),
            'get_eloquent_query' => str_contains($content, 'getEloquentQuery'),
            'tenant_scoping' => str_contains($content, "filament()->getTenant()->id"),
            'correlation_id' => str_contains($content, 'correlation_id'),
            'log_channel' => str_contains($content, "Log::channel"),
        ];
        
        $syntax = shell_exec("php -l \"$pagePath\" 2>&1");
        
        if (all($checks)) {
            $report['pages']['pass']++;
            $status = '✅ PASS';
        } else {
            $report['pages']['fail']++;
            $status = '❌ FAIL';
            $report['violations'][] = basename($pagePath) . ": " . implode(', ', array_keys(array_filter($checks, fn($v) => !$v)));
        }
        
        $report['pages']['details'][] = [
            'page' => basename($pagePath),
            'status' => $status,
            'syntax' => $syntax ? '✓' : '✗',
            'checks' => $checks,
        ];
    }
}

function all(array $checks): bool {
    foreach ($checks as $check) {
        if (!$check) return false;
    }
    return true;
}

// ОТЧЁТ
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║        КОМПЛЕКСНЫЙ АУДИТ CANON 2026 - ПОЛНЫЙ ОТЧЁТ          ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "📊 РЕСУРСЫ (Filament Resources)\n";
echo "├─ Total: {$report['resources']['total']}\n";
echo "├─ Pass: {$report['resources']['pass']}\n";
echo "├─ Fail: {$report['resources']['fail']}\n";
echo "└─ Compliance: " . ($report['resources']['pass'] > 0 ? round(100 * $report['resources']['pass'] / $report['resources']['total']) : 0) . "%\n\n";

echo "📄 СТРАНИЦЫ (Filament Pages)\n";
echo "├─ Total: {$report['pages']['total']}\n";
echo "├─ Pass: {$report['pages']['pass']}\n";
echo "├─ Fail: {$report['pages']['fail']}\n";
echo "└─ Compliance: " . ($report['pages']['pass'] > 0 ? round(100 * $report['pages']['pass'] / max($report['pages']['total'], 1)) : 0) . "%\n\n";

if (!empty($report['violations'])) {
    echo "⚠️ НАРУШЕНИЯ:\n";
    foreach (array_slice($report['violations'], 0, 10) as $violation) {
        echo "   ❌ $violation\n";
    }
    if (count($report['violations']) > 10) {
        echo "   ... и ещё " . (count($report['violations']) - 10) . " нарушений\n";
    }
    echo "\n";
}

echo "✅ ИТОГО COMPLIANCE: " . round(100 * ($report['resources']['pass'] + $report['pages']['pass']) / ($report['resources']['total'] + max($report['pages']['total'], 1))) . "%\n";
echo "📅 " . $report['timestamp'] . "\n";
