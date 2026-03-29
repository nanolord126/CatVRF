<?php
declare(strict_types=1);

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║            FINAL PRODUCTION AUDIT - CatVRF 2026               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Подсчитать Resources
$resources = glob('app/Filament/Tenant/Resources/*Resource.php') ?: [];
$resources_count = count($resources);

// Подсчитать Pages - более надежный способ
$pages = array_values(array_filter(
    (array) glob('app/Filament/Tenant/Resources/*/Pages/*.php'),
    fn($f) => basename($f) !== '.' && basename($f) !== '..'
));
$pages_count = count($pages);

// Проверить getPages методы
$with_getpages = 0;
$issues = [];

foreach ($resources as $res) {
    $content = file_get_contents($res);
    if (strpos($content, 'getPages()') !== false) {
        $with_getpages++;
    } else {
        $issues[] = basename($res);
    }
}

echo "📊 SYSTEM INVENTORY:\n";
echo "   Resources: $resources_count\n";
echo "   Pages: $pages_count (need " . ($resources_count * 4) . ")\n";
echo "   getPages() methods: $with_getpages / $resources_count\n\n";

// Compliance
$resources_compliance = $with_getpages / $resources_count * 100;
$pages_compliance = $pages_count / ($resources_count * 4) * 100;
$overall = ($resources_compliance + $pages_compliance) / 2;

echo "🎯 COMPLIANCE METRICS:\n";
printf("   Resources: %.1f%%\n", $resources_compliance);
printf("   Pages: %.1f%%\n", $pages_compliance);
printf("   Overall: %.1f%%\n\n", $overall);

echo ($overall >= 85 ? "✅" : "⚠️") . "  PRODUCTION STATUS: ";
if ($overall >= 95) {
    echo "READY FOR DEPLOYMENT\n";
} elseif ($overall >= 85) {
    echo "READY WITH MINOR ISSUES\n";
} else {
    echo "NEEDS FIXES\n";
}

if (!empty($issues)) {
    echo "\n⚠️  Missing getPages():\n";
    foreach ($issues as $issue) {
        echo "   • $issue\n";
    }
}

echo "\n" . str_repeat("═", 62) . "\n";
echo "Report: " . date('Y-m-d H:i:s') . "\n";
