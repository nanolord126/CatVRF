<?php

$resourcesDir = 'app/Filament/Tenant/Resources';
$resources = array_values(array_filter(array_map(
    fn($d) => is_dir("$resourcesDir/$d") ? $d : null,
    scandir($resourcesDir)
), fn($v) => $v !== null));

$total_resources = count($resources);
$resources_ok = 0;
$resources_errors = 0;
$pages_total = 0;
$pages_ok = 0;
$critical_errors = [];
$logic_errors = [];

echo "\n╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║           QUICK AUDIT: Verticals Logic + Resources + Pages           ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n";

foreach ($resources as $vertical) {
    $resourcePath = "$resourcesDir/$vertical";
    $pagesPath = "$resourcesDir/$vertical/Pages";
    
    // Проверка Resource файла
    $resourceFiles = glob("$resourcePath/*Resource.php");
    if (empty($resourceFiles)) {
        $critical_errors[] = "$vertical: No *Resource.php file found";
        $resources_errors++;
        continue;
    }
    
    $resourceFile = $resourceFiles[0];
    $resourceContent = file_get_contents($resourceFile);
    $resources_ok++;
    
    // Проверка Pages
    $pageTypes = ['List' => 'ListRecords', 'Create' => 'CreateRecord', 'Edit' => 'EditRecord', 'View' => 'ViewRecord'];
    $pages_found = 0;
    $pages_missing = [];
    $pages_in_dir = glob("$pagesPath/*.php") ?: [];
    
    foreach ($pageTypes as $prefix => $filamentClass) {
        $pages_total++;
        $found = false;
        
        foreach ($pages_in_dir as $page) {
            $pageName = basename($page, '.php');
            if (str_starts_with($pageName, $prefix)) {
                $found = true;
                $pages_ok++;
                $pages_found++;
                break;
            }
        }
        
        if (!$found) {
            $pages_missing[] = $prefix;
        }
    }
    
    // Проверка getPages() метода
    if (preg_match('/getPages\(\).*?\{(.*?)\}/s', $resourceContent, $match)) {
        $pagesCode = $match[1];
        $hasIndexMapping = strpos($pagesCode, "'index'") !== false || strpos($pagesCode, "'index'") !== false;
        
        if (!$hasIndexMapping) {
            $logic_errors[] = "$vertical: getPages() missing index => ListRecords mapping";
        }
    } else {
        $logic_errors[] = "$vertical: getPages() method missing or malformed";
    }
    
    // Вывод статуса вертикали
    if (count($pages_missing) > 0) {
        echo "\n❌ $vertical: Missing pages [" . implode(', ', $pages_missing) . "] - $pages_found/4 found\n";
    } else {
        echo "\n✅ $vertical: All 4 pages found\n";
    }
}

echo "\n╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║                           AUDIT SUMMARY                              ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n";

echo "\n📊 RESOURCES STATISTICS:\n";
echo "   Total verticals: $total_resources\n";
echo "   ✓ Resources OK: $resources_ok\n";
echo "   ✗ Resources errors: $resources_errors\n";
echo "   Compliance: " . round(($resources_ok / $total_resources) * 100, 1) . "%\n";

echo "\n📊 PAGES STATISTICS:\n";
echo "   Total pages needed: " . ($total_resources * 4) . "\n";
echo "   ✓ Pages found: $pages_ok\n";
echo "   ✗ Pages missing: " . ($pages_total - $pages_ok) . "\n";
echo "   Compliance: " . round(($pages_ok / $pages_total) * 100, 1) . "%\n";

if (count($critical_errors) > 0) {
    echo "\n⚠️  CRITICAL ERRORS (" . count($critical_errors) . "):\n";
    foreach (array_slice($critical_errors, 0, 10) as $err) {
        echo "   • $err\n";
    }
    if (count($critical_errors) > 10) {
        echo "   ... и еще " . (count($critical_errors) - 10) . " ошибок\n";
    }
}

if (count($logic_errors) > 0) {
    echo "\n⚠️  LOGIC ERRORS (" . count($logic_errors) . "):\n";
    foreach (array_slice($logic_errors, 0, 10) as $err) {
        echo "   • $err\n";
    }
    if (count($logic_errors) > 10) {
        echo "   ... и еще " . (count($logic_errors) - 10) . " ошибок\n";
    }
}

$compliance = (($resources_ok / $total_resources) * 0.5) + (($pages_ok / $pages_total) * 0.5);
echo "\n" . ($compliance >= 95 ? "✅" : "⚠️") . "  OVERALL COMPLIANCE: " . round($compliance, 1) . "%\n";

echo "\n" . str_repeat("═", 70) . "\n";
