<?php
declare(strict_types=1);

$resourcesDir = 'app/Filament/Tenant/Resources';
$output = [];

$output[] = "\n╔════════════════════════════════════════════════════════════════╗";
$output[] = "║           COMPREHENSIVE VERTICAL LOGIC AUDIT 2026             ║";
$output[] = "╚════════════════════════════════════════════════════════════════╝\n";

if (!is_dir($resourcesDir)) {
    $output[] = "ERROR: $resourcesDir not found!";
    file_put_contents('audit_logic_report.txt', implode("\n", $output));
    die();
}

$resources = array_values(array_filter(array_map(
    fn($d) => is_dir("$resourcesDir/$d") ? $d : null,
    scandir($resourcesDir) ?: []
), fn($v) => $v !== null));

$total_resources = count($resources);
$resources_ok = 0;
$pages_total = 0;
$pages_ok = 0;
$critical_errors = [];
$warnings = [];
$logic_issues = [];

$output[] = "📊 Processing " . $total_resources . " verticals...\n";

foreach ($resources as $idx => $vertical) {
    $resourcePath = "$resourcesDir/$vertical";
    $pagesPath = "$resourcesDir/$vertical/Pages";
    
    // Поиск Resource файла
    $resourceFiles = glob("$resourcePath/*Resource.php") ?: [];
    
    if (empty($resourceFiles)) {
        $critical_errors[] = "$vertical: No *Resource.php file";
        continue;
    }
    
    $resourceFile = $resourceFiles[0];
    if (!file_exists($resourceFile)) {
        $critical_errors[] = "$vertical: Resource file not readable";
        continue;
    }
    
    $resourceContent = file_get_contents($resourceFile);
    $resources_ok++;
    
    // Проверка Pages
    $pages_in_dir = is_dir($pagesPath) ? (glob("$pagesPath/*.php") ?: []) : [];
    $pages_found = count($pages_in_dir);
    $pages_total += 4;
    
    if ($pages_found >= 4) {
        $pages_ok += 4;
        $status = "✅";
    } elseif ($pages_found > 0) {
        $pages_ok += $pages_found;
        $status = "⚠️";
        $warnings[] = "$vertical: Only $pages_found/4 pages found";
    } else {
        $status = "❌";
        $critical_errors[] = "$vertical: No pages found";
    }
    
    // Проверка getPages() в Resource
    if (!preg_match('/public\s+function\s+getPages/', $resourceContent)) {
        $logic_issues[] = "$vertical: getPages() method missing";
    }
    
    // Вывод прогресса
    if (($idx + 1) % 10 === 0) {
        $num = ($idx + 1);
        $output[] = "  [$num/$total_resources] " . $status . " Processed $vertical";
    }
}

$output[] = "\n╔════════════════════════════════════════════════════════════════╗";
$output[] = "║                      AUDIT RESULTS                            ║";
$output[] = "╚════════════════════════════════════════════════════════════════╝\n";

$output[] = "📊 RESOURCES:\n";
$output[] = "   Total verticals: $total_resources";
$output[] = "   ✓ OK: $resources_ok";
$output[] = "   ✗ Errors: " . ($total_resources - $resources_ok);
$res_compliance = $total_resources > 0 ? round(($resources_ok / $total_resources) * 100, 1) : 0;
$output[] = "   Compliance: {$res_compliance}%\n";

$output[] = "📄 PAGES:\n";
$output[] = "   Total needed: " . ($total_resources * 4);
$output[] = "   ✓ Found: $pages_ok";
$output[] = "   ✗ Missing: " . ($pages_total - $pages_ok);
$pages_compliance = $pages_total > 0 ? round(($pages_ok / $pages_total) * 100, 1) : 0;
$output[] = "   Compliance: {$pages_compliance}%\n";

if (!empty($critical_errors)) {
    $output[] = "⚠️  CRITICAL ERRORS (" . count($critical_errors) . "):";
    foreach (array_slice($critical_errors, 0, 20) as $err) {
        $output[] = "   • $err";
    }
    if (count($critical_errors) > 20) {
        $output[] = "   ... и еще " . (count($critical_errors) - 20) . " ошибок";
    }
    $output[] = "";
}

if (!empty($warnings)) {
    $output[] = "⚠️  WARNINGS (" . count($warnings) . "):";
    foreach (array_slice($warnings, 0, 10) as $w) {
        $output[] = "   • $w";
    }
    if (count($warnings) > 10) {
        $output[] = "   ... и еще " . (count($warnings) - 10) . " предупреждений";
    }
    $output[] = "";
}

if (!empty($logic_issues)) {
    $output[] = "🔗 LOGIC ISSUES (" . count($logic_issues) . "):";
    foreach (array_slice($logic_issues, 0, 10) as $iss) {
        $output[] = "   • $iss";
    }
    $output[] = "";
}

$overall = ($res_compliance + $pages_compliance) / 2;
$icon = $overall >= 90 ? "✅" : ($overall >= 70 ? "⚠️" : "❌");
$output[] = "$icon  OVERALL SYSTEM COMPLIANCE: " . round($overall, 1) . "%";
$output[] = "\n" . str_repeat("═", 62) . "\n";
$output[] = "Report generated: " . date('Y-m-d H:i:s');

$report = implode("\n", $output);
file_put_contents('audit_logic_report.txt', $report);
echo $report;
