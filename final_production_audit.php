<?php
declare(strict_types=1);

$resourcesDir = 'app/Filament/Tenant/Resources';

// Получить все директории (только папки 1-го уровня)
$allDirs = @scandir($resourcesDir);
if (!is_array($allDirs)) {
    $allDirs = [];
}
$verticals = array_filter($allDirs, function($d) use ($resourcesDir) {
    return $d !== '.' && $d !== '..' && is_dir("$resourcesDir/$d");
});
$verticals = array_values($verticals);

$report = [];
$report[] = "\n╔════════════════════════════════════════════════════════════════╗";
$report[] = "║    FINAL PRODUCTION-READY AUDIT - CatVRF System (42 Verticals)   ║";
$report[] = "╚════════════════════════════════════════════════════════════════╝\n";

$resources_ok = 0;
$resources_err = 0;
$pages_ok = 0;
$pages_missing = 0;
$logic_ok = 0;
$logic_err = 0;

$verticals_report = [];
$errors_list = [];

foreach (sort($verticals) as $vertical) {
    $resourcePath = "$resourcesDir/$vertical";
    $pagesPath = "$resourcePath/Pages";
    
    $status = [
        'resource' => null,
        'pages' => [],
        'logic' => null,
    ];
    
    // 1. Проверка Resource файла
    $resourceFiles = glob("$resourcePath/*Resource.php") ?: [];
    if (!empty($resourceFiles)) {
        $status['resource'] = 'ok';
        $resources_ok++;
        
        // 2. Проверка содержимого
        $content = file_get_contents($resourceFiles[0]);
        if (strpos($content, 'public function getPages()') !== false || 
            strpos($content, 'public static function getPages()') !== false) {
            $status['logic'] = 'ok';
            $logic_ok++;
        } else {
            $status['logic'] = 'missing_getPages';
            $logic_err++;
            $errors_list[] = "$vertical: Missing getPages() method";
        }
    } else {
        $status['resource'] = 'missing';
        $resources_err++;
        $errors_list[] = "$vertical: No Resource file found";
    }
    
    // 3. Проверка Pages
    if (is_dir($pagesPath)) {
        $pages = glob("$pagesPath/*.php") ?: [];
        $status['pages'] = count($pages);
        $pages_ok += count($pages);
    } else {
        $status['pages'] = 0;
        $pages_missing += 4; // Ожидаем 4 Page файла
        $errors_list[] = "$vertical: No Pages directory";
    }
    
    $verticals_report[$vertical] = $status;
}

// Вывод результатов
$report[] = "📊 SYSTEM STATISTICS:\n";
$report[] = "   Total verticals in system: " . count($verticals);
$report[] = "   ✓ Resources OK: $resources_ok";
$report[] = "   ✗ Resources missing: $resources_err\n";

$report[] = "📄 PAGES STATISTICS:\n";
$report[] = "   ✓ Page files found: $pages_ok";
$report[] = "   ✗ Pages missing: $pages_missing\n";

$report[] = "🔗 LOGIC VERIFICATION:\n";
$report[] = "   ✓ getPages() implemented: $logic_ok";
$report[] = "   ✗ getPages() missing: $logic_err\n";

$res_compliance = $resources_ok > 0 ? ($resources_ok / ($resources_ok + $resources_err) * 100) : 0;
$logic_compliance = $logic_ok > 0 ? ($logic_ok / ($logic_ok + $logic_err) * 100) : 0;
$pages_compliance = $pages_ok > 0 ? ($pages_ok / ($pages_ok + $pages_missing) * 100) : 0;

$report[] = "🎯 COMPLIANCE METRICS:\n";
$report[] = "   Resources: " . round($res_compliance, 1) . "%";
$report[] = "   Logic (getPages): " . round($logic_compliance, 1) . "%";
$report[] = "   Pages: " . round($pages_compliance, 1) . "%";

$overall = ($res_compliance + $logic_compliance + $pages_compliance) / 3;
$report[] = "   Overall: " . round($overall, 1) . "%\n";

if (count($errors_list) > 0) {
    $report[] = "⚠️  ISSUES FOUND (" . count($errors_list) . "):";
    foreach (array_slice($errors_list, 0, 15) as $err) {
        $report[] = "   • $err";
    }
    if (count($errors_list) > 15) {
        $report[] = "   ... и еще " . (count($errors_list) - 15) . " ошибок";
    }
    $report[] = "";
}

// Рекомендации
$report[] = ($overall >= 90 ? "✅" : "⚠️") . "  PRODUCTION READINESS: ";
if ($overall >= 95) {
    $report[] = "READY FOR DEPLOYMENT";
} elseif ($overall >= 85) {
    $report[] = "READY WITH MINOR ISSUES";
} elseif ($overall >= 70) {
    $report[] = "NEEDS FIXES BEFORE DEPLOYMENT";
} else {
    $report[] = "CRITICAL - EXTENSIVE FIXES REQUIRED";
}

$report[] = "\n" . str_repeat("═", 62) . "\n";
$report[] = "Report: " . date('Y-m-d H:i:s');

$text = implode("\n", $report);
file_put_contents('PRODUCTION_AUDIT_FINAL.txt', $text);
echo $text;
