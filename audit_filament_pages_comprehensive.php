<?php

declare(strict_types=1);

/**
 * Comprehensive Filament Pages Audit
 * 
 * Scans all marketplace resources for Filament Pages and checks for:
 * - Proper authorization checks
 * - Audit logging
 * - Error handling
 * - Proper method implementations
 * - Code patterns consistency
 */

$resourcePath = 'app/Filament/Tenant/Resources/Marketplace';
$pageIssues = [];
$pageStats = [
    'total_resources' => 0,
    'resources_with_pages' => 0,
    'total_pages' => 0,
    'pages_with_issues' => 0,
    'issues_found' => 0,
];

// Scan all marketplace resources
$resources = glob("$resourcePath/*/Pages/*.php");

foreach ($resources as $pagePath) {
    $pageStats['total_pages']++;
    $content = file_get_contents($pagePath);
    $relativePath = str_replace('app/Filament/Tenant/Resources/Marketplace/', '', $pagePath);
    $issues = [];
    
    // Check 1: Is file empty or minimal?
    if (strpos($content, 'final class') !== false) {
        $lines = count(explode("\n", $content));
        if ($lines < 15) {
            $issues[] = "❌ EMPTY CLASS - Only " . $lines . " lines (should have proper implementation)";
        }
    }
    
    // Check 2: Does it have boot() method (for CreateRecord/EditRecord)?
    if ((strpos($content, 'CreateRecord') !== false || strpos($content, 'EditRecord') !== false) &&
        strpos($content, 'public function boot(') === false &&
        strpos($content, 'protected function __construct(') === false) {
        // Check if it's more than just the class declaration
        if (strpos($content, 'authorizeAccess') === false && 
            strpos($content, 'handleRecordCreation') === false &&
            strpos($content, 'handleRecordUpdate') === false) {
            $issues[] = "⚠️  Missing boot() or __construct() method - no dependency injection";
        }
    }
    
    // Check 3: Does it have authorization checks?
    if (strpos($content, 'authorizeAccess') === false && 
        strpos($content, '$this->gate->allows') === false &&
        (strpos($content, 'CreateRecord') !== false || strpos($content, 'EditRecord') !== false)) {
        $issues[] = "⚠️  Missing authorization checks";
    }
    
    // Check 4: Does ViewRecord have proper audit logging?
    if (strpos($content, 'ViewRecord') !== false || strpos($content, 'ShowRecord') !== false) {
        if (strpos($content, 'authorizeAccess') === false) {
            $issues[] = "⚠️  ViewRecord missing authorizeAccess override";
        }
    }
    
    // Check 5: Does ListRecords have authorization?
    if (strpos($content, 'ListRecords') !== false) {
        if (strpos($content, 'authorizeAccess') === false) {
            $issues[] = "⚠️  ListRecords missing authorizeAccess override";
        }
    }
    
    // Check 6: Error handling in handleRecordCreation/Update?
    if (strpos($content, 'handleRecordCreation') !== false || strpos($content, 'handleRecordUpdate') !== false) {
        if (strpos($content, 'try {') === false) {
            $issues[] = "⚠️  Missing try-catch error handling in handleRecord method";
        }
    }
    
    // Check 7: Correlation ID tracking?
    if ((strpos($content, 'CreateRecord') !== false || strpos($content, 'EditRecord') !== false) &&
        strpos($content, 'handleRecord') !== false) {
        if (strpos($content, 'Correlation') === false && strpos($content, 'correlation_id') === false) {
            $issues[] = "⚠️  Missing correlation ID tracking for audit trail";
        }
    }
    
    // Check 8: Proper Guard/Gate usage?
    if (strpos($content, 'Guard') !== false || strpos($content, 'Gate') !== false) {
        if (strpos($content, '$this->guard') === false && strpos($content, 'auth()->user()') === false) {
            $issues[] = "⚠️  Guard/Gate imported but not used in methods";
        }
    }
    
    // Check 9: Constructor pattern consistency
    if (strpos($content, '__construct(') !== false && 
        strpos($content, 'public function __construct(') !== false) {
        if (strpos($content, 'public function boot(') === false) {
            // Property promotion style - should verify it's correct
            if (strpos($content, 'protected Guard $guard') !== false) {
                $issues[] = "⚠️  Using property promotion - verify compatibility with Filament";
            }
        }
    }
    
    // Check 10: Missing tenant_id isolation check?
    if (strpos($content, 'EditRecord') !== false || strpos($content, 'DeleteRecord') !== false) {
        if (strpos($content, 'tenant_id') === false && strpos($content, 'getTenant()') === false) {
            $issues[] = "⚠️  Missing tenant isolation verification";
        }
    }
    
    if (!empty($issues)) {
        $pageStats['pages_with_issues']++;
        $pageStats['issues_found'] += count($issues);
        $pageIssues[$relativePath] = $issues;
    }
}

// Output results
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  FILAMENT PAGES COMPREHENSIVE AUDIT REPORT                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 AUDIT STATISTICS\n";
echo "───────────────────\n";
echo "Total Pages Audited: " . $pageStats['total_pages'] . "\n";
echo "Pages with Issues:   " . $pageStats['pages_with_issues'] . "\n";
echo "Total Issues Found:  " . $pageStats['issues_found'] . "\n\n";

if (empty($pageIssues)) {
    echo "✅ All pages passed audit!\n\n";
} else {
    echo "❌ ISSUES FOUND:\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    foreach ($pageIssues as $path => $issues) {
        echo "📄 $path\n";
        foreach ($issues as $issue) {
            echo "   $issue\n";
        }
        echo "\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Audit completed at " . date('Y-m-d H:i:s') . "\n";
