<?php
declare(strict_types=1);

/**
 * MEGA FIXER v2 — Fix ALL remaining 390 syntax errors
 * 
 * Categories:
 * 1. duplicate_logger (294) — duplicate LogManager $logger in constructor
 * 2. route_private (39+10+2) — class code injected into route files  
 * 3. readonly_default (28) — readonly properties with default values
 * 4. readonly_must_type (3) — readonly properties missing type
 * 5. duplicate_imports (7) — duplicate use statements
 * 6. edge_cases (3) — semicolon, float in route, etc.
 */

$baseDir = __DIR__;
$stats = [
    'duplicate_logger' => 0,
    'route_fixed' => 0,
    'readonly_default' => 0,
    'readonly_must_type' => 0,
    'duplicate_imports' => 0,
    'edge_cases' => 0,
];

// ========================================
// 1. FIX DUPLICATE $logger IN CONSTRUCTORS
// ========================================
echo "=== PHASE 1: Fixing duplicate \$logger params ===\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir . '/app', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    
    // Quick check
    $output = [];
    exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $ret);
    if ($ret === 0) continue;
    
    $msg = implode(' ', $output);
    if (!str_contains($msg, 'Redefinition of parameter $logger')) continue;
    
    $content = file_get_contents($path);
    $original = $content;
    
    // Strategy: find __construct and remove duplicate $logger lines
    // Pattern: line containing "private readonly LogManager $logger" that appears twice
    
    // Find all lines with LogManager $logger in constructor area
    $lines = explode("\n", $content);
    $loggerLineIndices = [];
    $inConstructor = false;
    $constructorEnd = -1;
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        if (str_contains($line, '__construct')) {
            $inConstructor = true;
        }
        if ($inConstructor && str_contains($line, 'LogManager $logger')) {
            $loggerLineIndices[] = $i;
        }
        if ($inConstructor && str_contains($line, ') {')) {
            $constructorEnd = $i;
            break;
        }
        if ($inConstructor && trim($line) === ') {}') {
            $constructorEnd = $i;
            break;
        }
    }
    
    if (count($loggerLineIndices) >= 2) {
        // Remove all but the first occurrence
        $toRemove = array_slice($loggerLineIndices, 1);
        
        // Before removing, check if the previous line ends with comma - if we're removing
        // the last param before ) {}, the previous line's comma needs to stay or be removed
        foreach (array_reverse($toRemove) as $idx) {
            $removedLine = $lines[$idx];
            
            // Check if this line is the last parameter (next non-empty line is ) {} or ) {)
            $nextIdx = $idx + 1;
            while ($nextIdx < count($lines) && trim($lines[$nextIdx]) === '') $nextIdx++;
            
            $isLastParam = ($nextIdx < count($lines) && preg_match('/^\s*\)\s*\{/', $lines[$nextIdx]));
            
            // Remove the line
            unset($lines[$idx]);
            
            // If the previous line now has a trailing comma and this was the last param, clean it
            if ($isLastParam) {
                $prevIdx = $idx - 1;
                while ($prevIdx >= 0 && trim($lines[$prevIdx]) === '') $prevIdx--;
                if ($prevIdx >= 0 && isset($lines[$prevIdx])) {
                    $lines[$prevIdx] = rtrim($lines[$prevIdx]);
                    // Remove trailing comma if present
                    if (str_ends_with(rtrim($lines[$prevIdx]), ',')) {
                        // Keep the comma - it's fine before ) {}
                        // Actually in PHP, trailing comma in constructor params is allowed
                    }
                }
            }
        }
        
        // Also check if the first $logger occurrence is part of a line that contains another param
        // e.g. "private readonly \Illuminate\Database\DatabaseManager $db, private readonly LogManager $logger, private readonly Guard $guard,"
        // followed by "private readonly LogManager $logger"
        // After removing the duplicate, we need to check if the first line has a trailing comma with nothing after
        
        $content = implode("\n", $lines);
        
        // Clean up: fix double commas that might result
        $content = preg_replace('/,\s*,/', ',', $content);
        
        // Clean up: fix ",\n    ) {" → "\n    ) {"
        // Actually trailing commas are fine in PHP 8
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            $stats['duplicate_logger']++;
        }
    }
}
echo "  Fixed: {$stats['duplicate_logger']}\n\n";

// ========================================
// 2. FIX ROUTE FILES (private keyword + unclosed paren)
// ========================================
echo "=== PHASE 2: Fixing broken route files ===\n";

// Find all route files with errors
$routeFiles = [];
$routePatterns = [
    $baseDir . '/app/Domains/*/Routes/*.php',
    $baseDir . '/app/Domains/*/routes/*.php',
];

foreach ($routePatterns as $pattern) {
    foreach (glob($pattern) as $f) {
        $routeFiles[] = $f;
    }
}

foreach ($routeFiles as $routeFile) {
    $output = [];
    exec("php -l " . escapeshellarg($routeFile) . " 2>&1", $output, $ret);
    if ($ret === 0) continue;
    
    $content = file_get_contents($routeFile);
    $original = $content;
    
    // These route files had class-level code injected into them.
    // We need to figure out the domain name and create a clean route file.
    
    // Extract the domain name from path
    $relative = str_replace($baseDir . '/app/Domains/', '', $routeFile);
    $parts = explode('/', str_replace('\\', '/', $relative));
    $domainName = $parts[0];
    
    // Get the prefix from the file (try to find Route::prefix or Route::middleware patterns)
    $prefix = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $domainName));
    
    // Detect what kind of routes this file had before corruption
    // Look for Route:: calls in the content
    $hasRoutes = preg_match_all('/Route::(get|post|put|patch|delete|apiResource|resource|middleware|prefix|group)\s*\(/', $content, $routeMatches);
    
    // Strategy: extract all valid Route:: lines and rebuild
    // First, let's see if there's valid route content before the corruption
    $lines = explode("\n", $content);
    $cleanLines = [];
    $foundCorruption = false;
    $inRouteGroup = false;
    $braceCount = 0;
    
    // Find where the corruption starts
    // Corruption pattern: "private readonly" or "const VERSION" or class-level code in a route file
    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $trimmed = trim($line);
        
        // Skip empty lines at start
        if ($i < 5 && $trimmed === '') {
            $cleanLines[] = $line;
            continue;
        }
        
        // Valid route file content
        if (preg_match('/^<\?php/', $trimmed) || 
            str_starts_with($trimmed, 'declare(') ||
            str_starts_with($trimmed, 'namespace ') ||
            str_starts_with($trimmed, 'use ') ||
            str_starts_with($trimmed, '//') ||
            str_starts_with($trimmed, '/*') ||
            str_starts_with($trimmed, '*') ||
            str_starts_with($trimmed, 'Route::') ||
            str_starts_with($trimmed, '});') ||
            str_starts_with($trimmed, '});') ||
            $trimmed === '' ||
            $trimmed === '});' ||
            $trimmed === '});') {
            $cleanLines[] = $line;
            continue;
        }
        
        // If we see "private", "readonly", "const ", "final ", "class " — it's corruption
        if (preg_match('/^\s*(private|readonly|const |final |class |public function|protected|use .+;$)/', $trimmed)) {
            $foundCorruption = true;
            break;
        }
        
        // If inside a route group closure, allow function-like lines
        $cleanLines[] = $line;
    }
    
    if (!$foundCorruption) {
        // Different kind of error - maybe unclosed paren
        // Let's try to detect the issue
        $msg = implode(' ', $output);
        
        if (str_contains($msg, "Unclosed '(' on line 34") || str_contains($msg, 'Unclosed')) {
            // Likely a middleware group that wasn't closed
            // Add closing brackets
            $content = rtrim($content);
            if (!str_ends_with($content, '});')) {
                // Count open/close braces and parens
                $openBraces = substr_count($content, '{');
                $closeBraces = substr_count($content, '}');
                $openParens = substr_count($content, '(');
                $closeParens = substr_count($content, ')');
                
                $needBraces = $openBraces - $closeBraces;
                $needParens = $openParens - $closeParens;
                
                $suffix = '';
                for ($j = 0; $j < $needBraces; $j++) {
                    $suffix .= '}';
                }
                for ($j = 0; $j < $needParens; $j++) {
                    $suffix .= ')';
                }
                if ($needBraces > 0 || $needParens > 0) {
                    // Build proper closing
                    $closing = "\n";
                    for ($j = 0; $j < max($needBraces, $needParens); $j++) {
                        $closing .= "});\n";
                    }
                    $content .= $closing;
                    if ($content !== $original) {
                        file_put_contents($routeFile, $content);
                        $stats['route_fixed']++;
                    }
                }
            }
        }
        continue;
    }
    
    // Rebuild clean route file
    // Use the clean lines we extracted, but make sure it's syntactically valid
    $cleanContent = implode("\n", $cleanLines);
    $cleanContent = rtrim($cleanContent);
    
    // Make sure we have proper opening
    if (!str_contains($cleanContent, '<?php')) {
        $cleanContent = "<?php\n\ndeclare(strict_types=1);\n\n" . $cleanContent;
    }
    
    // Count braces/parens to ensure balanced
    $openBraces = substr_count($cleanContent, '{');
    $closeBraces = substr_count($cleanContent, '}');
    $openParens = substr_count($cleanContent, '(');
    $closeParens = substr_count($cleanContent, ')');
    
    // Close any unclosed groups
    while ($openBraces > $closeBraces || $openParens > $closeParens) {
        if ($openBraces > $closeBraces && $openParens > $closeParens) {
            $cleanContent .= "\n});";
            $closeBraces++;
            $closeParens++;
        } elseif ($openBraces > $closeBraces) {
            $cleanContent .= "\n}";
            $closeBraces++;
        } elseif ($openParens > $closeParens) {
            $cleanContent .= "\n);";
            $closeParens++;
        }
    }
    
    $cleanContent .= "\n";
    
    if ($cleanContent !== $original) {
        file_put_contents($routeFile, $cleanContent);
        $stats['route_fixed']++;
    }
}
echo "  Fixed: {$stats['route_fixed']}\n\n";

// ========================================
// 3. FIX READONLY PROPERTIES WITH DEFAULTS
// ========================================
echo "=== PHASE 3: Fixing readonly properties with defaults ===\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir . '/app', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    
    $output = [];
    exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $ret);
    if ($ret === 0) continue;
    
    $msg = implode(' ', $output);
    if (!str_contains($msg, 'cannot have default value') && !str_contains($msg, 'must have type')) continue;
    
    $content = file_get_contents($path);
    $original = $content;
    
    // Problem: `final readonly class` but it has properties with defaults like:
    // protected static string $resource = CleaningOrderResource::class;
    // public int $tries = 3;
    // The issue is "readonly class" — ALL properties become readonly in a readonly class
    
    // Two strategies:
    // A) If class extends a framework class (Filament, Job, Listener) → remove "readonly" from class declaration
    // B) If it's a standalone readonly class → can't have defaults
    
    // Check if it's a framework class that shouldn't be readonly
    $isFilamentPage = str_contains($content, 'extends CreateRecord') || 
                      str_contains($content, 'extends EditRecord') ||
                      str_contains($content, 'extends ListRecords') ||
                      str_contains($content, 'extends ManageRecords');
    $isFilamentResource = str_contains($content, 'extends Resource');
    $isJob = str_contains($content, 'implements ShouldQueue') || str_contains($content, 'extends Job');
    $isListener = str_contains($content, 'class Log') && str_contains($content, 'Listener');
    $isCollection = str_contains($content, 'extends ResourceCollection') || str_contains($content, 'extends JsonResource');
    
    if ($isFilamentPage || $isFilamentResource || $isJob || $isListener || $isCollection) {
        // Remove readonly from class declaration
        $content = preg_replace('/final\s+readonly\s+class\b/', 'final class', $content);
    } else {
        // General approach — remove readonly from class
        $content = preg_replace('/final\s+readonly\s+class\b/', 'final class', $content);
    }
    
    if ($content !== $original) {
        file_put_contents($path, $content);
        $stats['readonly_default']++;
    }
}
echo "  Fixed: {$stats['readonly_default']}\n\n";

// ========================================
// 4. FIX DUPLICATE USE STATEMENTS
// ========================================
echo "=== PHASE 4: Fixing duplicate use statements ===\n";

$dupImportFiles = [
    $baseDir . '/app/Domains/Wallet/Services/WalletService.php',
    $baseDir . '/app/Services/AI/DemandForecastService.php',
    $baseDir . '/app/Services/AI/RecommendationEngine.php',
    $baseDir . '/app/Services/Automation/FraudDetectionService.php',
    $baseDir . '/app/Services/Inventory/InventoryManagementService.php',
    $baseDir . '/app/Services/Marketing/AdEngineService.php',
    $baseDir . '/app/Services/Marketing/MarketingCampaignService.php',
    $baseDir . '/app/Services/Marketing/PromoCampaignService.php',
];

foreach ($dupImportFiles as $path) {
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    $original = $content;
    
    $lines = explode("\n", $content);
    $seenUse = [];
    $newLines = [];
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (preg_match('/^use\s+(.+);$/', $trimmed, $m)) {
            $useStmt = $m[1];
            if (isset($seenUse[$useStmt])) {
                continue; // Skip duplicate
            }
            $seenUse[$useStmt] = true;
        }
        $newLines[] = $line;
    }
    
    $content = implode("\n", $newLines);
    if ($content !== $original) {
        file_put_contents($path, $content);
        $stats['duplicate_imports']++;
    }
}

// Also scan all files for duplicate use statements that we might have missed
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir . '/app', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    
    $output = [];
    exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $ret);
    if ($ret === 0) continue;
    
    $msg = implode(' ', $output);
    if (!str_contains($msg, 'name is already in use')) continue;
    
    $content = file_get_contents($path);
    $original = $content;
    
    $lines = explode("\n", $content);
    $seenUse = [];
    $newLines = [];
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (preg_match('/^use\s+(.+);$/', $trimmed, $m)) {
            $useStmt = $m[1];
            if (isset($seenUse[$useStmt])) {
                continue; // Skip duplicate
            }
            $seenUse[$useStmt] = true;
        }
        $newLines[] = $line;
    }
    
    $content = implode("\n", $newLines);
    if ($content !== $original) {
        file_put_contents($path, $content);
        $stats['duplicate_imports']++;
    }
}
echo "  Fixed: {$stats['duplicate_imports']}\n\n";

// ========================================
// 5. FIX EDGE CASES
// ========================================
echo "=== PHASE 5: Fixing edge cases ===\n";

// 5a. CalculateAgencyEarningsJob — duplicate WalletService import
$calcJob = $baseDir . '/app/Domains/Travel/Jobs/CalculateAgencyEarningsJob.php';
if (file_exists($calcJob)) {
    $content = file_get_contents($calcJob);
    $original = $content;
    
    // Remove duplicate use statement
    $lines = explode("\n", $content);
    $seenUse = [];
    $newLines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (preg_match('/^use\s+(.+);$/', $trimmed, $m)) {
            $useStmt = $m[1];
            if (isset($seenUse[$useStmt])) continue;
            $seenUse[$useStmt] = true;
        }
        $newLines[] = $line;
    }
    $content = implode("\n", $newLines);
    if ($content !== $original) {
        file_put_contents($calcJob, $content);
        $stats['edge_cases']++;
    }
}

// 5b. CleaningOrderController — missing semicolon
$cleaningCtrl = $baseDir . '/app/Domains/CleaningServices/Controllers/CleaningOrderController.php';
if (file_exists($cleaningCtrl)) {
    $output = [];
    exec("php -l " . escapeshellarg($cleaningCtrl) . " 2>&1", $output, $ret);
    if ($ret !== 0) {
        $content = file_get_contents($cleaningCtrl);
        // Try to find the line with missing semicolon
        // The error says line 83, unexpected identifier "JsonResponse", expecting ";"
        // This means a return type hint is on wrong line
        $content = preg_replace('/\n\s*JsonResponse\b/', ': JsonResponse', $content);
        file_put_contents($cleaningCtrl, $content);
        
        // Verify
        exec("php -l " . escapeshellarg($cleaningCtrl) . " 2>&1", $o2, $r2);
        if ($r2 === 0) {
            $stats['edge_cases']++;
        }
    }
}

echo "  Fixed: {$stats['edge_cases']}\n\n";

// ========================================
// FINAL VERIFICATION
// ========================================
echo "=== FINAL: Verifying all fixes ===\n";

$totalErrors = 0;
$errorFiles = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir . '/app', RecursiveDirectoryIterator::SKIP_DOTS)
);

$total = 0;
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $total++;
    $path = $file->getPathname();
    
    $output = [];
    exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $ret);
    if ($ret !== 0) {
        $totalErrors++;
        $relative = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path);
        $errorFiles[] = $relative . ' → ' . trim(implode(' ', $output));
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total files: $total\n";
echo "Remaining errors: $totalErrors\n";
echo "Pass rate: " . round((1 - $totalErrors / $total) * 100, 1) . "%\n\n";

echo "Fixes applied:\n";
foreach ($stats as $cat => $count) {
    echo "  $cat: $count\n";
}

if ($totalErrors > 0) {
    echo "\nRemaining errors (first 30):\n";
    foreach (array_slice($errorFiles, 0, 30) as $err) {
        echo "  $err\n";
    }
}

echo "\nTotal fixed: " . array_sum($stats) . "\n";
