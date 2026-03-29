<?php
declare(strict_types=1);

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║   COMPREHENSIVE AUDIT - CatVRF 42 Verticals + Pages System   ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$resourcesDir = 'app/Filament/Tenant/Resources';

// 1. Подсчитать все Resource.php файлы
$resourceFiles = glob("$resourcesDir/*Resource.php") ?: [];
echo "📊 RESOURCES FOUND: " . count($resourceFiles) . " files\n";

// 2. Подсчитать все Page*.php файлы рекурсивно  
$pageDirs = glob("$resourcesDir/*/Pages", GLOB_ONLYDIR) ?: [];
$pageFiles = [];
foreach ($pageDirs as $pageDir) {
    $files = glob("$pageDir/*.php") ?: [];
    $pageFiles = array_merge($pageFiles, $files);
}
echo "📄 PAGES FOUND: " . count($pageFiles) . " files in " . count($pageDirs) . " directories\n\n";

// 3. Проверить getPages() методы
$with_getpages = 0;
$without_getpages = [];

foreach ($resourceFiles as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'public function getPages()') !== false || 
        strpos($content, 'public static function getPages()') !== false) {
        $with_getpages++;
    } else {
        $className = basename($file, '.php');
        $without_getpages[] = $className;
    }
}

echo "🔗 LOGIC VERIFICATION:\n";
echo "   ✓ Resources with getPages(): $with_getpages\n";
echo "   ✗ Resources missing getPages(): " . count($without_getpages) . "\n";

if (count($without_getpages) > 0 && count($without_getpages) <= 10) {
    foreach ($without_getpages as $res) {
        echo "      • $res\n";
    }
}

// 4. Анализ примеров Pages
if (!empty($pageFiles)) {
    echo "\n📋 SAMPLE PAGE FILES:\n";
    foreach (array_slice($pageFiles, 0, 5) as $pf) {
        $name = basename($pf);
        $dir = basename(dirname($pf));
        $parent = basename(dirname(dirname($pf)));
        echo "   • $parent/$dir/$name\n";
    }
}

// 5. Compliance расчет
$res_compliance = count($resourceFiles) > 0 ? ($with_getpages / count($resourceFiles) * 100) : 0;
$pages_compliance = count($resourceFiles) > 0 ? (count($pageFiles) / (count($resourceFiles) * 4) * 100) : 0;

echo "\n🎯 COMPLIANCE:\n";
echo "   Resources getPages() implementation: " . round($res_compliance, 1) . "%\n";
echo "   Pages created: " . round($pages_compliance, 1) . "% (need " . (count($resourceFiles) * 4) . " total)\n";

$overall = ($res_compliance + $pages_compliance) / 2;
echo "   Overall: " . round($overall, 1) . "%\n";

echo "\n" . ($overall >= 90 ? "✅" : ($overall >= 70 ? "⚠️" : "❌")) . "  STATUS: ";
echo match (true) {
    $overall >= 95 => "PRODUCTION READY ✅\n",
    $overall >= 85 => "READY WITH MINOR ISSUES ⚠️\n",
    $overall >= 70 => "NEEDS FIXES ⚠️\n",
    default => "CRITICAL - EXTENSIVE WORK NEEDED ❌\n",
};

echo "\n" . str_repeat("═", 62) . "\n";
echo "Audit: " . date('Y-m-d H:i:s') . "\n";

// Сохранить результат
$report = ob_get_clean();
file_put_contents('AUDIT_SUMMARY.txt', ob_get_clean() ?: "");
