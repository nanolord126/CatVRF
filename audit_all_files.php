<?php
/**
 * COMPLETE PROJECT AUDIT SCRIPT
 * Checks all PHP/Blade files for completeness (min 60 lines)
 */

$directories = [
    'app/Models' => 'Models',
    'app/Models/Tenants' => 'Tenant Models',
    'app/Filament/Resources' => 'Filament Resources',
    'app/Filament/Tenant/Resources' => 'Tenant Resources',
    'app/Filament/Tenant/Pages' => 'Tenant Pages',
    'app/Filament/Tenant/Widgets' => 'Tenant Widgets',
    'app/Policies' => 'Policies',
    'app/Http/Controllers' => 'Controllers',
    'app/Services' => 'Services',
    'database/migrations' => 'Migrations',
    'database/migrations/tenant' => 'Tenant Migrations',
    'database/seeders' => 'Seeders',
    'database/seeders/Tenant' => 'Tenant Seeders',
    'resources/views' => 'Blade Views',
];

$results = [];
$totalFiles = 0;
$totalPass = 0;
$totalFail = 0;

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║         COMPLETE PROJECT AUDIT - FILE COMPLETENESS           ║\n";
echo "║  (Files with < 60 lines marked as FAIL - Incomplete/Stub)    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

foreach ($directories as $path => $label) {
    if (!is_dir($path)) {
        continue;
    }
    
    $files = glob($path . '/**/*.php', GLOB_RECURSIVE);
    $files = array_merge($files, glob($path . '/**/*.blade.php', GLOB_RECURSIVE));
    $files = array_filter(array_unique($files));
    
    if (empty($files)) {
        continue;
    }
    
    echo "📁 $label ($path)\n";
    echo str_repeat("-", 60) . "\n";
    
    $categoryPass = 0;
    $categoryFail = 0;
    $categoryFiles = [];
    
    foreach ($files as $file) {
        $lines = count(file($file));
        $totalFiles++;
        
        $status = $lines >= 60 ? "✅ PASS" : "❌ FAIL";
        
        if ($lines >= 60) {
            $totalPass++;
            $categoryPass++;
        } else {
            $totalFail++;
            $categoryFail++;
            $categoryFiles[] = [
                'file' => basename($file),
                'path' => $file,
                'lines' => $lines,
                'status' => 'FAIL'
            ];
        }
        
        if ($lines < 60) {
            printf("  %-50s %s (%d lines)\n", basename($file), $status, $lines);
        }
    }
    
    echo "\n  Summary: $categoryPass OK, $categoryFail INCOMPLETE\n\n";
    
    $results[$label] = [
        'pass' => $categoryPass,
        'fail' => $categoryFail,
        'files' => $categoryFiles
    ];
}

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    AUDIT SUMMARY                             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

printf("Total Files Checked: %d\n", $totalFiles);
printf("✅ Complete (≥60 lines): %d (%.1f%%)\n", $totalPass, $totalPass/$totalFiles*100);
printf("❌ Incomplete (<60 lines): %d (%.1f%%)\n\n", $totalFail, $totalFail/$totalFiles*100);

if ($totalFail > 0) {
    echo "⚠️  INCOMPLETE FILES BY CATEGORY:\n";
    echo str_repeat("=", 60) . "\n\n";
    
    foreach ($results as $category => $data) {
        if ($data['fail'] > 0) {
            printf("%s (%d incomplete):\n", $category, $data['fail']);
            foreach ($data['files'] as $file) {
                printf("  %-40s %d lines\n", $file['file'], $file['lines']);
            }
            echo "\n";
        }
    }
}

// Generate JSON report for processing
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_files' => $totalFiles,
    'pass' => $totalPass,
    'fail' => $totalFail,
    'categories' => $results
];

file_put_contents('audit_report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "📄 Detailed report saved to: audit_report.json\n";
