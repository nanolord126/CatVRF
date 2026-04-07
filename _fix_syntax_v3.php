<?php
/**
 * Phase 3 - Fix remaining 31 files individually
 */

$fixed = 0;

// ============================================================
// Group 1: Duplicate booted() in 13 model files
// ============================================================
$bootedFiles = [
    'app/Domains/Common/Chat/Models/Conversation.php',
    'app/Domains/Common/Chat/Models/Message.php',
    'app/Domains/ConstructionAndRepair/Construction/Models/ConstructionEstimate.php',
    'app/Domains/ConstructionAndRepair/Construction/Models/ConstructionMaterial.php',
    'app/Domains/ConstructionAndRepair/Construction/Models/ConstructionProject.php',
    'app/Domains/Consulting/HR/Models/JobApplication.php',
    'app/Domains/Consulting/HR/Models/JobVacancy.php',
    'app/Domains/Education/Social/Models/SocialMedia.php',
    'app/Domains/Education/Social/Models/SocialPost.php',
    'app/Domains/FarmDirect/Agro/Models/AgroCrop.php',
    'app/Domains/FarmDirect/Agro/Models/AgroFarm.php',
    'app/Domains/FarmDirect/Agro/Models/AgroProduct.php',
];

foreach ($bootedFiles as $f) {
    if (!file_exists($f)) { echo "SKIP: $f\n"; continue; }
    $c = file_get_contents($f);
    $count = substr_count($c, 'function booted()');
    if ($count < 2) { echo "OK already: $f (booted count=$count)\n"; continue; }
    
    // Strategy: find the SECOND booted() method and remove it entirely
    $pos1 = strpos($c, 'function booted()');
    $pos2 = strpos($c, 'function booted()', $pos1 + 20);
    
    if ($pos2 === false) continue;
    
    // Go back to find "protected static" or just newline before it
    $searchBack = substr($c, 0, $pos2);
    $methodStart = strrpos($searchBack, 'protected static function booted');
    if ($methodStart === false) $methodStart = strrpos($searchBack, 'static function booted');
    if ($methodStart === false) continue;
    
    // Find the method body end: count braces from the opening {
    $openBrace = strpos($c, '{', $pos2);
    if ($openBrace === false) continue;
    
    $depth = 0;
    $endPos = $openBrace;
    for ($i = $openBrace; $i < strlen($c); $i++) {
        if ($c[$i] === '{') $depth++;
        if ($c[$i] === '}') $depth--;
        if ($depth === 0) { $endPos = $i + 1; break; }
    }
    
    // Remove from methodStart to endPos
    $before = substr($c, 0, $methodStart);
    $after = substr($c, $endPos);
    $c = $before . $after;
    $c = preg_replace("/\n{3,}/", "\n\n", $c);
    file_put_contents($f, $c);
    $fixed++;
    echo "FIX booted: " . basename($f) . "\n";
}

// ============================================================
// Group 2: RevenueChart - remove the Illuminate\Support\Carbon line
// ============================================================
$rc = 'app/Domains/Finances/Presentation/Filament/Widgets/RevenueChart.php';
if (file_exists($rc)) {
    $c = file_get_contents($rc);
    // The line might not have \n at end, try both patterns
    $c = str_replace("use Illuminate\\Support\\Carbon;\r\n", '', $c);
    $c = str_replace("use Illuminate\\Support\\Carbon;\n", '', $c);
    $c = str_replace("use Illuminate\\Support\\Carbon;", '', $c);
    file_put_contents($rc, $c);
    echo "FIX: RevenueChart\n";
    $fixed++;
}

// ============================================================
// Group 3: Services with remaining broken wallet->credit() or enum garbage
// These need aggressive cleanup of any \App\Domains\Wallet\Enums line
// ============================================================
$serviceFiles = [
    'app/Domains/Medical/Services/MedicalAppointmentService.php',
    'app/Domains/OfficeCatering/Services/OfficeCateringService.php',
    'app/Domains/PartySupplies/Gifts/Services/GiftsService.php',
    'app/Domains/PartySupplies/Services/PartySuppliesService.php',
    'app/Domains/Pet/PetServices/Services/PetBoardingService.php',
    'app/Domains/Pharmacy/MedicalSupplies/Services/MedicalSuppliesService.php',
    'app/Domains/RealEstate/OfficeRentals/Services/OfficeRentalsService.php',
    'app/Domains/RealEstate/ShopRentals/Services/ShopRentalsService.php',
    'app/Domains/ShortTermRentals/Services/ShortTermRentalsService.php',
    'app/Domains/Taxi/Services/TaxiService.php',
    'app/Domains/Tickets/EntertainmentBooking/Services/EntertainmentBookingService.php',
    'app/Domains/ToysAndGames/Toys/Services/ToysService.php',
    'app/Domains/Travel/Services/TravelBookingService.php',
    'app/Domains/VeganProducts/Services/VeganProductService.php',
];

foreach ($serviceFiles as $f) {
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    $orig = $c;
    
    // Strategy: aggressively remove ANY remaining BalanceTransactionType enum references
    // that appear as function arguments (not as use imports)
    
    // Pattern A: , \App\Domains\Wallet\Enums\BalanceTransactionType::XXX, $var, null, null, [
    $c = preg_replace(
        '/,\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\$\w+,\s*null,\s*null,\s*\[/',
        ',',
        $c
    );
    
    // Pattern B: , \App\Domains\Wallet\Enums\BalanceTransactionType::XXX, $var, null, null, null)
    $c = preg_replace(
        '/,\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\$\w+,\s*null,\s*null,\s*null\)/',
        ')',
        $c
    );
    
    // Pattern C: \App\Domains\Wallet\Enums\BalanceTransactionType::XXX, NUMBER, null, null, null);
    $c = preg_replace(
        '/\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\d+,\s*null,\s*null,\s*null\);/',
        '',
        $c
    );
    
    // Pattern D (multiline): \App\Domains\Wallet\Enums\..., $correlationId, null, null, [\n
    $c = preg_replace(
        '/\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\$\w+,\s*null,\s*null,\s*\[\s*\n/',
        "\n",
        $c
    );
    
    // Pattern E: standalone line with just enum: \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, [
    $c = preg_replace(
        '/^\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+.*$/m',
        '',
        $c
    );
    
    // Pattern F: 'standard', \App\Domains\Wallet\... → 'standard'
    $c = preg_replace(
        "/,\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\/",
        "",
        $c
    );
    
    // Now fix minified services: ;return $x;});} where if block isn't closed
    // Detect: {$this->wallet->credit(...);return $x;});}
    // The problem: if(condition){wallet_call;return $x;});}
    // Should be: if(condition){wallet_call;}return $x;});}
    
    // For credit() followed immediately by return inside if:
    // credit(args);return $var;});} → credit(args);}return $var;});}
    $c = preg_replace(
        '/->credit\(([^)]+)\);\s*return\s+(\$\w+);\s*\}\);/',
        '->credit($1);}return $2;});',
        $c
    );
    
    // Same for refund/debit patterns too
    $c = preg_replace(
        '/->credit\(([^)]+)\);\s*return\s+(\$\w+);\s*\}\)\s*;/',
        '->credit($1);}return $2;});',
        $c
    );

    // Clean up double newlines
    $c = preg_replace("/\n{4,}/", "\n\n", $c);
    
    if ($c !== $orig) {
        file_put_contents($f, $c);
        $fixed++;
        echo "FIX service: " . basename($f) . "\n";
    }
}

// ============================================================
// Group 4: PartnerStoreAPIIntegration + RouteOptimizationService (unclosed braces)
// ============================================================
foreach ([
    'app/Domains/GroceryAndDelivery/Integrations/PartnerStoreAPIIntegration.php',
    'app/Domains/GroceryAndDelivery/Integrations/RouteOptimizationService.php',
] as $f) {
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    
    // Count braces
    $open = substr_count($c, '{');
    $close = substr_count($c, '}');
    $missing = $open - $close;
    
    if ($missing > 0) {
        // Add missing closing braces at end of file
        $c = rtrim($c) . "\n" . str_repeat("}\n", $missing);
        file_put_contents($f, $c);
        $fixed++;
        echo "FIX braces ($missing): " . basename($f) . "\n";
    }
}

// ============================================================
// Group 5: RateLimitBloggers - unclosed { on line 23
// ============================================================
$rlb = 'app/Domains/Education/Bloggers/Http/Middleware/RateLimitBloggers.php';
if (file_exists($rlb)) {
    $c = file_get_contents($rlb);
    $open = substr_count($c, '{');
    $close = substr_count($c, '}');
    $missing = $open - $close;
    if ($missing > 0) {
        $c = rtrim($c) . "\n" . str_repeat("}\n", $missing);
        file_put_contents($rlb, $c);
        $fixed++;
        echo "FIX braces ($missing): RateLimitBloggers\n";
    }
}

// ============================================================
// Group 6: Reservation model fix
// ============================================================
$res = 'app/Domains/Inventory/Models/Reservation.php';
if (file_exists($res)) {
    $c = file_get_contents($res);
    // Count braces
    $open = substr_count($c, '{');
    $close = substr_count($c, '}');
    
    if ($close > $open) {
        // Remove extra closing braces from the end
        $extra = $close - $open;
        for ($i = 0; $i < $extra; $i++) {
            $lastBrace = strrpos($c, '}');
            if ($lastBrace !== false) {
                $c = substr($c, 0, $lastBrace) . substr($c, $lastBrace + 1);
            }
        }
        file_put_contents($res, $c);
        $fixed++;
        echo "FIX extra }: Reservation\n";
    }
}

echo "\n=== Phase 3 fixed: $fixed ===\n";

// VERIFY ALL
echo "\n--- Final verification ---\n";
$broken = file('_broken_files.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$ok = 0; $err = 0;
foreach ($broken as $f) {
    $f = trim($f);
    if (!file_exists($f)) continue;
    $out = shell_exec("php -l " . escapeshellarg($f) . " 2>&1");
    if (strpos($out, 'Parse error') !== false || strpos($out, 'Fatal error') !== false) {
        $err++;
        $shortErr = preg_replace('/^.*?(Parse error|Fatal error)/', '$1', trim($out));
        $shortErr = preg_replace('/\s+/', ' ', $shortErr);
        echo "ERR: " . basename($f) . " → " . substr($shortErr, 0, 120) . "\n";
    } else {
        $ok++;
    }
}
echo "\n=== FINAL: OK=$ok BROKEN=$err ===\n";
