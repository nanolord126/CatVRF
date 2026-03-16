<?php

$pagesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$issues = [];
$totalFiles = 0;
$productionReady = 0;

function auditPagesDirectory($dir) {
    global $issues, $totalFiles, $productionReady;
    
    $files = glob($dir . '*/Pages/*.php');
    
    foreach ($files as $file) {
        $totalFiles++;
        $content = file_get_contents($file);
        $path = str_replace(__DIR__ . '/', '', $file);
        
        $fileIssues = [];
        
        // 1. Check for declare(strict_types=1)
        if (!preg_match('/^<\?php\s*\n\s*declare\(strict_types=1\)/m', $content)) {
            $fileIssues[] = 'Missing declare(strict_types=1)';
        }
        
        // 2. Check for final class
        if (!preg_match('/\bfinal\s+class\b/', $content)) {
            $fileIssues[] = 'Class not final';
        }
        
        // 3. Check for proper namespace
        if (!preg_match('/namespace App\\Filament\\Tenant\\Resources\\.*Pages;/', $content)) {
            $fileIssues[] = 'Incorrect or missing namespace';
        }
        
        // 4. Check for proper inheritance
        if (!preg_match('/extends\s+(ListRecords|CreateRecord|EditRecord|ViewRecord)/', $content)) {
            // Custom Page classes with $view are OK
            if (!preg_match('/protected\s+static\s+string\s+\$view\s*=/', $content)) {
                $fileIssues[] = 'Invalid inheritance (not standard Page type)';
            }
        }
        
        // 5. Check for use statements
        if (!preg_match('/use\s+Filament\\Resources\\Pages/', $content)) {
            // Only warn if using standard Page types
            if (preg_match('/extends\s+(ListRecords|CreateRecord|EditRecord|ViewRecord)/', $content)) {
                $fileIssues[] = 'Missing Filament\\Resources\\Pages use statement';
            }
        }
        
        // 6. Check for empty/stub code
        if (preg_match('/\{[\s\n]*\}/', $content)) {
            // Check if it's intentionally empty (allowed for custom pages with $view)
            if (preg_match('/protected\s+static\s+string\s+\$resource/', $content)) {
                // OK - has $resource
            } else {
                $fileIssues[] = 'Potentially empty class body';
            }
        }
        
        // 7. Check encoding (UTF-8 no BOM)
        $bom = substr($content, 0, 3);
        if ($bom === "\xef\xbb\xbf") {
            $fileIssues[] = 'File has BOM (must be UTF-8 no BOM)';
        }
        
        // 8. Check line endings (CRLF)
        if (strpos($content, "\r\n") === false) {
            $fileIssues[] = 'File does not use CRLF line endings';
        }
        
        if (empty($fileIssues)) {
            $productionReady++;
        } else {
            $issues[$path] = $fileIssues;
        }
    }
}

// Audit root resources
auditPagesDirectory($pagesDir . '/');

// Audit subdirectories
$subdirs = glob($pagesDir . '/*/', GLOB_ONLYDIR);
foreach ($subdirs as $subdir) {
    auditPagesDirectory($subdir);
    
    // Check nested dirs (like Marketplace/Taxi/)
    $nestedDirs = glob($subdir . '*/', GLOB_ONLYDIR);
    foreach ($nestedDirs as $nestedDir) {
        auditPagesDirectory($nestedDir);
    }
}

echo "=== PAGES AUDIT REPORT ===\n\n";
echo "Total Files: $totalFiles\n";
echo "Production Ready: $productionReady\n";
echo "Issues Found: " . count($issues) . "\n\n";

if (!empty($issues)) {
    echo "=== ISSUES BY FILE ===\n\n";
    foreach ($issues as $file => $fileIssues) {
        echo "❌ $file\n";
        foreach ($fileIssues as $issue) {
            echo "   - $issue\n";
        }
        echo "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Production Ready: " . round(($productionReady / $totalFiles) * 100, 1) . "%\n";
