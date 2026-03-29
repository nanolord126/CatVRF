<?php
declare(strict_types=1);

$base = 'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources';
$resources = glob($base . '/*Resource.php');

echo "MISSING PAGES ANALYSIS:\n";
echo "═══════════════════════════════════════\n\n";

$missing = [];
foreach ($resources as $resource) {
    $name = basename($resource, 'Resource.php');
    $path = dirname($resource);
    
    $pages_dir = $path . '/' . $name . '/Pages';
    
    $types = ['List', 'Create', 'Edit', 'View'];
    foreach ($types as $type) {
        $page_file = $pages_dir . '/' . $type . $name . '.php';
        if (!file_exists($page_file)) {
            $missing[] = [
                'resource' => $name,
                'type' => $type,
                'file' => $page_file
            ];
        }
    }
}

echo "❌ Missing Pages: " . count($missing) . "\n\n";

// Group by type
$by_type = [];
foreach ($missing as $m) {
    $by_type[$m['type']][] = $m['resource'];
}

foreach ($by_type as $type => $resources_list) {
    echo "📝 Missing $type Pages (" . count($resources_list) . "):\n";
    foreach (array_slice($resources_list, 0, 5) as $r) {
        echo "   • $r\n";
    }
    if (count($resources_list) > 5) {
        echo "   ... and " . (count($resources_list) - 5) . " more\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════\n";
echo "✅ Need to create: " . count($missing) . " Pages\n";
