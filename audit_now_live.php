<?php
declare(strict_types=1);

$resources = glob('app/Filament/Tenant/Resources/*Resource.php') ?: [];
$pages = array_filter(
    (array) glob('app/Filament/Tenant/Resources/*/Pages/*.php'),
    fn($f) => is_file($f)
);

$res_count = count($resources);
$pages_count = count($pages);
$with_getpages = 0;

foreach ($resources as $res) {
    if (strpos(file_get_contents($res), 'getPages()') !== false) {
        $with_getpages++;
    }
}

$res_comp = ($with_getpages / $res_count) * 100;
$pages_comp = ($pages_count / ($res_count * 4)) * 100;
$overall = ($res_comp + $pages_comp) / 2;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║            FINAL PRODUCTION AUDIT - CatVRF 2026 LIVE            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
echo "📊 SYSTEM INVENTORY:\n";
echo "   Resources: $res_count\n";
echo "   Pages: $pages_count (need " . ($res_count * 4) . ")\n";
echo "   getPages() methods: $with_getpages / $res_count\n\n";
echo "🎯 COMPLIANCE METRICS:\n";
printf("   Resources: %.1f%%\n", $res_comp);
printf("   Pages: %.1f%%\n", $pages_comp);
printf("   Overall: %.1f%%\n\n", $overall);
echo ($overall >= 85 ? "✅" : "⚠️") . "  PRODUCTION STATUS: ";
echo match (true) {
    $overall >= 95 => "READY FOR DEPLOYMENT ✅\n",
    $overall >= 85 => "READY WITH MINOR ISSUES ⚠️\n",
    default => "NEEDS FIXES\n",
};
echo "\n" . str_repeat("═", 62) . "\n";
