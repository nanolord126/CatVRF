<?php
declare(strict_types=1);

$base = 'c:/opt/kotvrf/CatVRF/app/Filament/Tenant/Resources';

echo "📊 PAGE STRUCTURE ANALYSIS:\n";
echo "═════════════════════════════════════════\n\n";

$total_pages = 0;
$unique_pages = 0;
$duplicates = [];
$page_files = glob($base . '/**/Pages/*.php', GLOB_BRACE);

$seen = [];
foreach ($page_files as $page) {
    $total_pages++;
    $key = basename($page);
    
    if (isset($seen[$key])) {
        $duplicates[$key][] = $page;
    } else {
        $seen[$key] = [$page];
        $unique_pages++;
    }
}

echo "Total Pages Found: $total_pages\n";
echo "Unique Page Names: $unique_pages\n";
echo "Duplicates Found: " . count($duplicates) . "\n\n";

if ($duplicates) {
    echo "🔍 Duplicate Pages:\n";
    foreach (array_slice($duplicates, 0, 10) as $name => $paths) {
        echo "  • $name (" . count($paths) . " copies)\n";
        foreach (array_slice($paths, 0, 2) as $p) {
            echo "    - " . str_replace('c:/opt/kotvrf/CatVRF/', '', $p) . "\n";
        }
    }
    echo "\n";
}

// Check for missing page types
echo "📝 Page Types Distribution:\n";
$types = ['List', 'Create', 'Edit', 'View'];
foreach ($types as $type) {
    $count = count(glob($base . '/**/Pages/' . $type . '*.php', GLOB_BRACE));
    echo "  • $type pages: $count\n";
}

echo "\n✅ Structure verification complete.\n";
