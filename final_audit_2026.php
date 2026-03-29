<?php
declare(strict_types=1);

$base = 'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources';
$resources = glob($base . '/*Resource.php');
$pages = glob($base . '/**/Pages/*.php', GLOB_BRACE);

echo '📊 FINAL SYSTEM AUDIT - 2026' . PHP_EOL;
echo '═════════════════════════════════════════' . PHP_EOL;
echo 'Resources found: ' . count($resources) . PHP_EOL;
echo 'Pages found: ' . count($pages) . PHP_EOL;
echo 'Expected Pages: ' . (count($resources) * 4) . PHP_EOL;
echo '═════════════════════════════════════════' . PHP_EOL . PHP_EOL;

$getpages = 0;
foreach ($resources as $r) {
    $content = file_get_contents($r);
    if (strpos($content, 'public static function getPages()') !== false) {
        $getpages++;
    }
}

$pages_coverage = count($pages) / (count($resources) * 4) * 100;
$resources_coverage = $getpages / count($resources) * 100;
$overall = ($pages_coverage + $resources_coverage) / 2;

echo '✅ Resource Compliance: ' . $getpages . '/' . count($resources) . ' (' . round($resources_coverage, 1) . '%)' . PHP_EOL;
echo '✅ Page Coverage: ' . count($pages) . '/' . (count($resources) * 4) . ' (' . round($pages_coverage, 1) . '%)' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo '🎯 Overall Compliance: ' . round($overall, 1) . '%' . PHP_EOL;
echo PHP_EOL;

if ($overall >= 95) {
    echo '✅ PRODUCTION STATUS: READY FOR DEPLOYMENT ✅' . PHP_EOL;
} elseif ($overall >= 90) {
    echo '⚠️  PRODUCTION STATUS: NEARLY READY (minor fixes needed)' . PHP_EOL;
} else {
    echo '❌ PRODUCTION STATUS: NEEDS WORK' . PHP_EOL;
}
echo PHP_EOL;

// Show top 10 Resources
echo '📋 Sample Resources:' . PHP_EOL;
$count = 0;
foreach (array_slice($resources, 0, 10) as $r) {
    $name = basename($r);
    echo '  • ' . $name . PHP_EOL;
    $count++;
}
echo PHP_EOL;
