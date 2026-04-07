<?php
/**
 * Fix remaining 33 broken files - Phase 2
 */

$stillBroken = [];

// ============================================================
// FIX 1: Duplicate booted() - remove second one (15 models)
// ============================================================
$bootedFiles = glob('app/Domains/*/Models/*.php') + 
               glob('app/Domains/*/*/Models/*.php') +
               glob('app/Domains/*/*/*/Models/*.php');

$bootedFixed = 0;
foreach ($bootedFiles as $f) {
    $c = file_get_contents($f);
    // Count booted() occurrences
    if (substr_count($c, 'function booted()') >= 2) {
        // Find second booted() and remove it (keep the first one)
        $pos1 = strpos($c, 'function booted()');
        $pos2 = strpos($c, 'function booted()', $pos1 + 1);
        if ($pos2 !== false) {
            // Find the start of this method (look back for 'protected static')
            $methodStart = strrpos(substr($c, 0, $pos2), 'protected static');
            if ($methodStart === false) $methodStart = strrpos(substr($c, 0, $pos2), 'public static');
            if ($methodStart === false) continue;
            
            // Find the end of this method (matching closing brace)
            $braceCount = 0;
            $methodEnd = $pos2;
            $started = false;
            for ($i = $pos2; $i < strlen($c); $i++) {
                if ($c[$i] === '{') { $braceCount++; $started = true; }
                if ($c[$i] === '}') { $braceCount--; }
                if ($started && $braceCount === 0) { $methodEnd = $i + 1; break; }
            }
            
            // Remove the duplicate method
            $before = substr($c, 0, $methodStart);
            $after = substr($c, $methodEnd);
            // Clean up extra blank lines
            $c = $before . $after;
            $c = preg_replace("/\n{3,}/", "\n\n", $c);
            file_put_contents($f, $c);
            $bootedFixed++;
            echo "FIX booted: " . basename($f) . "\n";
        }
    }
}
echo "Duplicate booted() fixed: $bootedFixed\n\n";

// ============================================================
// FIX 2: Readonly default values (HeatmapExportService, ScreenshotService)
// Remove 'readonly' from class declaration for these
// ============================================================
$rdFiles = [
    'app/Domains/Consulting/Analytics/Services/HeatmapExportService.php',
    'app/Domains/Consulting/Analytics/Services/ScreenshotService.php',
    'app/Domains/Consulting/Analytics/Services/ComparisonHeatmapService.php',
    'app/Domains/Consulting/Analytics/Services/CustomMetricService.php',
];
foreach ($rdFiles as $f) {
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    $orig = $c;
    
    // Fix readonly class with default property values: remove readonly from class
    if (preg_match('/final\s+readonly\s+class/', $c) && preg_match('/private\s+(string|int|float|bool|array)\s+\$\w+\s*=/', $c)) {
        $c = preg_replace('/final\s+readonly\s+class/', 'final class', $c);
    }
    
    // Fix orphaned 'string $correlationId = '';' outside class
    $c = preg_replace('/^\s*string\s+\$correlationId\s*=\s*[\'"]{2}\s*;\s*$/m', '', $c);
    
    if ($c !== $orig) {
        file_put_contents($f, $c);
        echo "FIX readonly/orphan: " . basename($f) . "\n";
    }
}

// ============================================================
// FIX 3: RevenueChart duplicate Carbon
// ============================================================
$revChart = 'app/Domains/Finances/Presentation/Filament/Widgets/RevenueChart.php';
if (file_exists($revChart)) {
    $c = file_get_contents($revChart);
    if (strpos($c, "use Carbon\\Carbon;") !== false && strpos($c, "use Illuminate\\Support\\Carbon;") !== false) {
        $c = str_replace("use Illuminate\\Support\\Carbon;\n", '', $c);
        file_put_contents($revChart, $c);
        echo "FIX: RevenueChart Carbon\n";
    }
}

// ============================================================
// FIX 4: MeatService broken update() call
// ============================================================
$meatFile = 'app/Domains/MeatShops/Services/MeatService.php';
if (file_exists($meatFile)) {
    $c = file_get_contents($meatFile);
    // Fix: $order->update(["status" => "completed", \App\Domains\Wallet\Enums\... garbage
    $c = preg_replace(
        '/\$order->update\(\["status"\s*=>\s*"completed",\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+.*?\]\);/s',
        '$order->update(["status" => "completed"]);',
        $c
    );
    file_put_contents($meatFile, $c);
    echo "FIX: MeatService\n";
}

// ============================================================
// FIX 5: Reservation model - methods outside class
// ============================================================
$resFile = 'app/Domains/Inventory/Models/Reservation.php';
if (file_exists($resFile)) {
    $c = file_get_contents($resFile);
    // This file has scopeExpired and relationships OUTSIDE the class
    // We need to wrap them in a proper class
    
    // Check if class declaration exists
    if (strpos($c, 'final class Reservation') === false && strpos($c, 'class Reservation') === false) {
        // No class - need to add one
        $lines = explode("\n", $c);
        $lastUse = 0;
        foreach ($lines as $i => $l) {
            if (preg_match('/^use\s+/', trim($l))) $lastUse = $i;
        }
        
        // Insert class declaration after last use
        $classDecl = "\nfinal class Reservation extends Model\n{\n    use HasFactory;\n";
        array_splice($lines, $lastUse + 1, 0, [$classDecl]);
        
        // Check if file ends with }
        $last = trim(end($lines));
        if ($last !== '}') {
            $lines[] = '}';
        }
        
        $c = implode("\n", $lines);
        file_put_contents($resFile, $c);
        echo "FIX: Reservation (class wrapper)\n";
    }
}

// ============================================================
// FIX 6: Services with enum injected into wrong places
// Pattern: \App\Domains\Wallet\Enums\BalanceTransactionType::XXX, $correlationId, null, null, [
// appearing in non-wallet contexts (update, where, getAvailableRooms etc)
// ============================================================
$enumPattern = '/,\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,?\s*\$?\w*,?\s*null,?\s*null,?\s*\[?/';
$enumFiles = [
    'app/Domains/Pet/PetServices/Services/PetBoardingService.php',
    'app/Domains/Medical/Services/MedicalAppointmentService.php',
    'app/Domains/Travel/Services/TravelBookingService.php',
    'app/Domains/VeganProducts/Services/VeganProductService.php',
    'app/Domains/Taxi/Services/TaxiService.php',
    'app/Domains/RealEstate/OfficeRentals/Services/OfficeRentalsService.php',
    'app/Domains/RealEstate/ShopRentals/Services/ShopRentalsService.php',
];

foreach ($enumFiles as $f) {
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    $orig = $c;
    
    // Remove lines that are just enum garbage injected into wrong methods
    // Pattern: \App\Domains\Wallet\Enums\BalanceTransactionType::XXX, $variable, null, null, [
    $c = preg_replace(
        '/,?\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\$\w+,\s*null,\s*null,\s*null\)/',
        ')',
        $c
    );
    $c = preg_replace(
        '/,?\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\$\w+,\s*null,\s*null,\s*\[/',
        '',
        $c
    );
    // Also fix: 'standard', \App\Domains\Wallet\... → 'standard']
    $c = preg_replace(
        "/'standard',\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\\\$\w+,\s*null,\s*null,\s*\[/",
        "'standard'",
        $c
    );
    // Fix: 'status', \App\Domains\Wallet\Enums\... → 'status'
    $c = preg_replace(
        "/,\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\\\$\w+,\s*null,\s*null,\s*\[/",
        ",",
        $c
    );
    
    if ($c !== $orig) {
        file_put_contents($f, $c);
        echo "FIX enum garbage: " . basename($f) . "\n";
    }
}

// ============================================================
// FIX 7: Minified services - missing } before return in cancelOrder
// Pattern: ;return $VAR;});}  (inside if block)
// Should be: ;}return $VAR;});}
// ============================================================
$minifiedServices = glob('app/Domains/*/Services/*.php') +
                    glob('app/Domains/*/*/Services/*.php') +
                    glob('app/Domains/*/*/*/Services/*.php');

$cancelFixed = 0;
foreach ($minifiedServices as $f) {
    $c = file_get_contents($f);
    $orig = $c;
    
    // Fix: if($x->payment_status==='completed'){$this->wallet->credit(...);return $var;});}
    // The ;return $var is inside the if{} block but });} closes the transaction
    // Need to close the if block first
    // Pattern: credit(...);return $VAR;});}
    $c = preg_replace(
        "/->credit\(([^;]+)\);(return\s+\\\$\w+;)\}\);/",
        "->credit($1);}$2});",
        $c
    );
    
    if ($c !== $orig) {
        file_put_contents($f, $c);
        $cancelFixed++;
        echo "FIX cancel}: " . basename($f) . "\n";
    }
}
echo "Cancel brace fixed: $cancelFixed\n\n";

// ============================================================
// FIX 8: CalculateAgencyEarningsJob broken credit()
// ============================================================
$agencyJob = 'app/Domains/Travel/Jobs/CalculateAgencyEarningsJob.php';
if (file_exists($agencyJob)) {
    $c = file_get_contents($agencyJob);
    // Fix the broken walletService->credit() call
    // Replace the broken credit call with a clean one
    $c = preg_replace(
        "/\\$walletService->credit\(\s*\\\$agency->wallet->id,\s*\(int\)\s*\(\\\$earnings\s*\*\s*100\),\s*'agency_id'\s*=>\s*\\\$this->agencyId,\s*'agency_id'\s*=>\s*\\\$this->agencyId,\s*'error'\s*=>\s*\\\$e->getMessage\(\),\s*'correlation_id'\s*=>\s*\\\$correlationId,\s*\]\);/s",
        "\$walletService->credit(\n                        \$agency->wallet->id,\n                        (int) (\$earnings * 100),\n                        'agency_payout',\n                        \$correlationId\n                    );",
        $c
    );
    file_put_contents($agencyJob, $c);
    echo "FIX: CalculateAgencyEarningsJob\n";
}

// ============================================================
// FIX 9: OfficeRentals/ShopRentals wallet->credit with named params + enum garbage
// ============================================================
$rentalServices = [
    'app/Domains/RealEstate/OfficeRentals/Services/OfficeRentalsService.php',
    'app/Domains/RealEstate/ShopRentals/Services/ShopRentalsService.php',
];
foreach ($rentalServices as $f) {
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    $orig = $c;
    
    // Fix: meta: ['correlation_id' => $correlationId, \App\Domains\..., $correlationId, null, null, [
    $c = preg_replace(
        "/meta:\s*\['correlation_id'\s*=>\s*\\\$correlationId,\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\\\$correlationId,\s*null,\s*null,\s*\[/",
        "meta: ['correlation_id' => \$correlationId,",
        $c
    );
    
    // Fix standalone: \App\Domains\Wallet\Enums\BalanceTransactionType::XXX, 400, null, null, null);
    $c = preg_replace(
        "/\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\d+,\s*null,\s*null,\s*null\);/",
        "",
        $c
    );
    
    if ($c !== $orig) {
        file_put_contents($f, $c);
        echo "FIX rental wallet: " . basename($f) . "\n";
    }
}

// ============================================================
// VERIFY
// ============================================================
echo "\n--- Verifying remaining ---\n";
$broken = file('_broken_files.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$ok = 0;
$err = 0;
foreach ($broken as $f) {
    $f = trim($f);
    if (!file_exists($f)) continue;
    $out = shell_exec("php -l " . escapeshellarg($f) . " 2>&1");
    if (strpos($out, 'Parse error') !== false || strpos($out, 'Fatal error') !== false) {
        $err++;
        echo "BROKEN: " . basename($f) . " → " . trim(preg_replace('/\s+/', ' ', $out)) . "\n";
    } else {
        $ok++;
    }
}
echo "\n=== PHASE 2 RESULT: OK=$ok BROKEN=$err ===\n";
