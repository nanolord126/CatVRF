<?php

declare(strict_types=1);

/**
 * ETAP 1: Full Controller Refactoring Script
 * 
 * Удаляет все дублирующиеся middleware-related коды из контроллеров
 * и применяет правильные patterns из middleware
 */

use Symfony\Component\Finder\Finder;

require __DIR__ . '/vendor/autoload.php';

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║   ETAP 1: Full Controller Refactoring - Code Cleanup           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

$totalControllers = 0;
$totalLinesRemoved = 0;
$filesModified = [];

// ────────────────────────────────────────────────────────────────────
// PATTERNS TO REMOVE FROM CONTROLLERS
// ────────────────────────────────────────────────────────────────────

// Полные блоки для удаления (наиболее опасные - удаляем целые методы/блоки)
$blocksToRemove = [
    // Fraud check blocks
    [
        'name' => 'fraud_check_try_catch',
        'pattern' => '/try\s*\{\s*\$this->fraudControl.*?\} catch \(\Exception \$e\) \{[^}]*return response\(\)->json.*?403\);?\s*\}/s',
        'safe' => false,
    ],
    
    // Correlation ID generation (standalone)
    [
        'name' => 'correlation_id_standalone',
        'pattern' => '/\$correlationId\s*=\s*(?:\$request->header|Str::uuid)[^;]*;/i',
        'safe' => true,  // безопасно удалять, middleware его сгенерирует
    ],
];

// Lines to replace/remove
$patternsToReplace = [
    // Fraud checks - SAFE TO REMOVE
    [
        'name' => 'fraud_check_call',
        'pattern' => '/\s*\$this->fraudControl(?:Service)?->check\([^)]*\);?\s*/i',
        'replacement' => '',
        'safe' => true,
        'reason' => 'FraudCheckMiddleware handles this',
    ],
    
    // Rate limiting - SAFE TO REMOVE  
    [
        'name' => 'rate_limit_call',
        'pattern' => '/\s*\$this->rateLimiter->(?:check|ensureLimit)\([^)]*\);?\s*/i',
        'replacement' => '',
        'safe' => true,
        'reason' => 'RateLimitingMiddleware handles this',
    ],
    
    // Correlation ID from string (not from attributes)
    [
        'name' => 'correlation_id_from_header',
        'pattern' => '/\$correlationId\s*=\s*\$request->header\([\'"](X-)?Correlation-?ID[\'"]\)\s*\?\?\s*(?:Str::uuid\(\)->toString\(\)|.*?);?\s*/i',
        'replacement' => '$correlationId = $request->attributes->get(\'correlation_id\') ?? $request->header(\'X-Correlation-ID\');',
        'safe' => true,
        'reason' => 'Get from middleware-injected request attributes',
    ],
    
    // B2B mode checks that duplicate middleware logic
    [
        'name' => 'b2b_duplicate_check',
        'pattern' => '/\s*\$isB2B\s*=\s*!empty\(\$inn\)\s*&&\s*!empty\(\$businessCardId\);?\s*/i',
        'replacement' => '// B2B mode determined in B2CB2BMiddleware',
        'safe' => true,
        'reason' => 'B2CB2BMiddleware already handles mode determination',
    ],
];

// ────────────────────────────────────────────────────────────────────
// PROCESS ALL CONTROLLERS
// ────────────────────────────────────────────────────────────────────

echo "\nProcessing controllers...\n";

$finder = new Finder();
$finder->files()
    ->in(__DIR__ . '/app/Http/Controllers/Api')
    ->name('*.php')
    ->notName('BaseApiController.php');

foreach ($finder as $file) {
    $totalControllers++;
    $filePath = $file->getRealPath();
    $originalContent = file_get_contents($filePath);
    $modifiedContent = $originalContent;
    $linesRemovedInFile = 0;
    
    // Count original lines
    $originalLineCount = count(explode("\n", $originalContent));
    
    // Apply safe replacements
    foreach ($patternsToReplace as $patternInfo) {
        if ($patternInfo['safe']) {
            $matches = [];
            if (preg_match_all($patternInfo['pattern'], $modifiedContent, $matches)) {
                foreach ($matches[0] as $match) {
                    $linesRemovedInFile += substr_count($match, "\n");
                }
                $modifiedContent = preg_replace($patternInfo['pattern'], $patternInfo['replacement'], $modifiedContent);
            }
        }
    }
    
    // Remove unnecessary use statements (fraud/rate limiting related)
    $modifiedContent = preg_replace(
        '/use\s+App\\Services\\(?:FraudControl|Security\\RateLimiter)Service;?\n/i',
        '',
        $modifiedContent
    );
    
    // Count final lines
    $finalLineCount = count(explode("\n", $modifiedContent));
    $actualLinesRemoved = $originalLineCount - $finalLineCount;
    
    // Save if changed
    if ($modifiedContent !== $originalContent) {
        file_put_contents($filePath, $modifiedContent);
        $totalLinesRemoved += $actualLinesRemoved;
        
        $filesModified[] = [
            'file' => $file->getFilename(),
            'lines_removed' => $actualLinesRemoved,
            'path' => str_replace(__DIR__, '', $filePath),
        ];
        
        echo "  ✓ {$file->getFilename()} - Removed {$actualLinesRemoved} lines\n";
    }
}

// ────────────────────────────────────────────────────────────────────
// GENERATE MIGRATION GUIDE FOR ROUTES
// ────────────────────────────────────────────────────────────────────

echo "\n[Routes Update Required]\n";
echo "  ✓ Update routes/api.php with middleware order:\n";
echo "    Route::middleware([\n";
echo "      'correlation-id',      // 1. Inject/validate correlation_id\n";
echo "      'auth:sanctum',        // 2. Authenticate user\n";
echo "      'tenant',              // 3. Tenant scoping\n";
echo "      'b2c-b2b',             // 4. Determine B2C/B2B mode\n";
echo "      'rate-limit',          // 5. Rate limiting\n";
echo "      'fraud-check',         // 6. Fraud detection\n";
echo "      'age-verify',          // 7. Age verification (if needed)\n";
echo "    ])->group(function () {\n";
echo "      // Your routes here\n";
echo "    });\n";

// ────────────────────────────────────────────────────────────────────
// SUMMARY
// ────────────────────────────────────────────────────────────────────

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║              CONTROLLER REFACTORING COMPLETE                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

echo "\n✓ SUMMARY:\n";
echo "  - Controllers processed: {$totalControllers}\n";
echo "  - Files modified: " . count($filesModified) . "\n";
echo "  - Lines removed: {$totalLinesRemoved}\n";

if (count($filesModified) > 0) {
    echo "\n✓ MODIFIED FILES:\n";
    foreach ($filesModified as $info) {
        echo "  - {$info['file']}: -{$info['lines_removed']} lines\n";
    }
}

// ────────────────────────────────────────────────────────────────────
// SAVE REFACTORING REPORT
// ────────────────────────────────────────────────────────────────────

$refactorReport = [
    'timestamp' => date('Y-m-d H:i:s'),
    'phase' => 'ETAP 1: Full Refactoring',
    'status' => 'COMPLETED',
    'controllers_processed' => $totalControllers,
    'files_modified' => count($filesModified),
    'total_lines_removed' => $totalLinesRemoved,
    'modified_files' => $filesModified,
    'middleware_order' => [
        1 => 'correlation-id - Inject/validate correlation_id',
        2 => 'auth:sanctum - Authenticate user',
        3 => 'tenant - Tenant scoping',
        4 => 'b2c-b2b - Determine B2C/B2B mode',
        5 => 'rate-limit - Rate limiting',
        6 => 'fraud-check - Fraud detection',
        7 => 'age-verify - Age verification',
    ],
    'next_steps' => [
        'Update routes/api.php with middleware order',
        'Update routes/api-v1.php with middleware order',
        'Update all vertical route files with middleware order',
        'Test all API endpoints with new middleware pipeline',
        'Verify fraud detection, rate limiting, and age verification work',
    ],
];

file_put_contents(
    __DIR__ . '/MIDDLEWARE_REFACTOR_COMPLETE.json',
    json_encode($refactorReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\n✓ Report saved: MIDDLEWARE_REFACTOR_COMPLETE.json\n\n";
