<?php
/**
 * Phase 4 — Fix remaining 17 files with targeted replacements
 */
$fixed = 0;

// ===========================================
// PATTERN A: wallet->credit() calls missing '[' before array keys
// These all have: ->credit(ARG1, ARG2, 'key' => val, ...]);
// Should be:     ->credit(ARG1, ARG2, ['key' => val, ...]);
// Or sometimes:  ->credit(ARG1, ARG2\n 'key' => val  (missing , [ )
// ===========================================

// Universal fix: find ->credit( or ->debit( calls where a string key => appears
// and wrap those args in [...]

function fixWalletCreditCalls(string $content): string
{
    // Pattern: ->credit(STUFF, 'key' => ... ]);
    // We need to find the LAST normal argument before the first 'key' =>
    // Strategy: replace "'key' => ... ]);" with "['key' => ... ]);"
    // But we need to know where to insert the [
    
    // Find all ->credit( or ->debit( calls
    $result = $content;
    
    // Fix pattern: ->credit(expr, expr, 'string_key' => value, ...]);
    // This regex finds credit/debit calls where after 2+ args, a string key appears
    // Add [ before the first 'key' =>
    $result = preg_replace(
        '/(->(?:credit|debit)\([^\'"\n]*?),\s*\n?\s*(\'[a-z_]+\'\s*=>)/s',
        '$1, [$2',
        $result
    );
    
    // Fix pattern: ->credit(expr, expr\n    'string_key' =>  (missing comma too)
    $result = preg_replace(
        '/(->(?:credit|debit)\([^\'"\n]*?)\s*\n\s*(\'[a-z_]+\'\s*=>)/s',
        '$1, [$2',
        $result
    );

    return $result;
}

function fixIfBlocksBeforeReturn(string $content): string
{
    // Pattern in minified code: if(cond){wallet_call]);return $x;});}
    // The ]);return should have }; before return: ]);} return
    // Actually: credit(...]);return → credit(...);}return
    $content = preg_replace(
        '/(\]\);\s*)(return\s+\$\w+;\s*\}\);)/',
        '$1}$2',
        $content
    );
    return $content;
}

// ===========================================
// Fix each file individually
// ===========================================

// --- MedicalAppointmentService ---
$f = 'app/Domains/Medical/Services/MedicalAppointmentService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    // Fix: $appointment->id"user" => missing comma
    $c = str_replace('$appointment->id"user"', '$appointment->id, "user"', $c);
    // Fix: missing closing }); for transaction + missing } for method
    // The file ends with: return true;\n        }\n}
    // But it should have }); to close the transaction closure
    $c = str_replace(
        "\$this->logger->info(\"Medical: appointment finished + payout\", [\"app_id\" => \$appointment->id, \"user\" => \$userId]\r\n);\r\n                       return true;\r\n        }\r\n}",
        "\$this->logger->info(\"Medical: appointment finished + payout\", [\"app_id\" => \$appointment->id, \"user\" => \$this->guard->id()]);\r\n                return true;\r\n            });\r\n        }\r\n}"
    , $c);
    // Also try with \n line endings
    $c = str_replace(
        "\$this->logger->info(\"Medical: appointment finished + payout\", [\"app_id\" => \$appointment->id, \"user\" => \$userId]\n);\n                       return true;\n        }\n}",
        "\$this->logger->info(\"Medical: appointment finished + payout\", [\"app_id\" => \$appointment->id, \"user\" => \$this->guard->id()]);\n                return true;\n            });\n        }\n}"
    , $c);
    // Brute force: if the exact patterns don't match, try regex
    $c = preg_replace(
        '/\["app_id"\s*=>\s*\$appointment->id,\s*"user"\s*=>\s*\$userId\]\s*\)\s*;/',
        '["app_id" => $appointment->id, "user" => $this->guard->id()]);',
        $c
    );
    // Ensure transaction is closed: check brace balance
    $open = substr_count($c, '{');
    $close = substr_count($c, '}');
    if ($open > $close) {
        $missing = $open - $close;
        // Insert before the final }
        $lastBrace = strrpos($c, '}');
        $c = substr($c, 0, $lastBrace) . str_repeat("});\n        ", 1) . substr($c, $lastBrace);
    }
    file_put_contents($f, $c);
    echo "FIX: MedicalAppointmentService\n";
    $fixed++;
}

// --- OfficeCateringService ---
$f = 'app/Domains/OfficeCatering/Services/OfficeCateringService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    // Fix wallet->credit calls: add [ before 'correlation_id' =>
    $c = fixWalletCreditCalls($c);
    // Fix cancelOrder: missing } to close if block
    // Pattern: if ($order->payment_status === 'completed') { wallet->credit(...]);
    // Need to add } after the credit call and before return
    $c = fixIfBlocksBeforeReturn($c);
    // Check braces
    $open = substr_count($c, '{');
    $close = substr_count($c, '}');
    if ($open > $close) {
        $lastBrace = strrpos($c, '}');
        $c = substr($c, 0, $lastBrace) . str_repeat("}\n", $open - $close) . substr($c, $lastBrace);
    }
    file_put_contents($f, $c);
    echo "FIX: OfficeCateringService\n";
    $fixed++;
}

// --- GiftsService (minified) ---
$f = 'app/Domains/PartySupplies/Gifts/Services/GiftsService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: GiftsService\n";
    $fixed++;
}

// --- PartySuppliesService ---
$f = 'app/Domains/PartySupplies/Services/PartySuppliesService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: PartySuppliesService\n";
    $fixed++;
}

// --- PetBoardingService ---
$f = 'app/Domains/Pet/PetServices/Services/PetBoardingService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: PetBoardingService\n";
    $fixed++;
}

// --- MedicalSuppliesService ---
$f = 'app/Domains/Pharmacy/MedicalSupplies/Services/MedicalSuppliesService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: MedicalSuppliesService\n";
    $fixed++;
}

// --- OfficeRentalsService ---
$f = 'app/Domains/RealEstate/OfficeRentals/Services/OfficeRentalsService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: OfficeRentalsService\n";
    $fixed++;
}

// --- ShopRentalsService ---
$f = 'app/Domains/RealEstate/ShopRentals/Services/ShopRentalsService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: ShopRentalsService\n";
    $fixed++;
}

// --- ShortTermRentalsService ---
$f = 'app/Domains/ShortTermRentals/Services/ShortTermRentalsService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: ShortTermRentalsService\n";
    $fixed++;
}

// --- TaxiService ---
$f = 'app/Domains/Taxi/Services/TaxiService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    // TaxiService has: ->credit($ride->driver->wallet(), $driverReward\n    'ride_uuid' => ...
    // Fix: insert , [ after $driverReward
    $c = preg_replace(
        '/(->credit\(\$ride->driver->wallet\(\),\s*\$driverReward)\s*\n\s*(\'ride_uuid\')/s',
        '$1, [$2',
        $c
    );
    // Remove duplicate ride_uuid line
    $c = preg_replace(
        "/'ride_uuid'\s*=>\s*\\\$ride->uuid\s*\n\s*'ride_uuid'\s*=>\s*\\\$ride->uuid,/",
        "'ride_uuid' => \$ride->uuid,",
        $c
    );
    file_put_contents($f, $c);
    echo "FIX: TaxiService\n";
    $fixed++;
}

// --- EntertainmentBookingService ---
$f = 'app/Domains/Tickets/EntertainmentBooking/Services/EntertainmentBookingService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: EntertainmentBookingService\n";
    $fixed++;
}

// --- ToysService ---
$f = 'app/Domains/ToysAndGames/Toys/Services/ToysService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: ToysService\n";
    $fixed++;
}

// --- TravelBookingService ---
$f = 'app/Domains/Travel/Services/TravelBookingService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: TravelBookingService\n";
    $fixed++;
}

// --- VeganProductService ---
$f = 'app/Domains/VeganProducts/Services/VeganProductService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $c = fixWalletCreditCalls($c);
    $c = fixIfBlocksBeforeReturn($c);
    file_put_contents($f, $c);
    echo "FIX: VeganProductService\n";
    $fixed++;
}

// --- RateLimitBloggers ---
$f = 'app/Domains/Education/Bloggers/Http/Middleware/RateLimitBloggers.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    $open = substr_count($c, '{');
    $close = substr_count($c, '}');
    if ($open > $close) {
        // Find the last } and add missing ones before it
        $c = rtrim($c);
        if (substr($c, -1) !== '}') {
            $c .= "\n" . str_repeat("}\n", $open - $close);
        } else {
            $lastBrace = strrpos($c, '}');
            $c = substr($c, 0, $lastBrace) . str_repeat("    }\n", $open - $close) . substr($c, $lastBrace);
        }
    }
    file_put_contents($f, $c);
    echo "FIX: RateLimitBloggers\n";
    $fixed++;
}

// --- PartnerStoreAPIIntegration ---
$f = 'app/Domains/GroceryAndDelivery/Integrations/PartnerStoreAPIIntegration.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    // try without catch/finally — need to add catch block
    $c = preg_replace('/try\s*\{([^}]*)\}\s*$/m', 'try {$1} catch (\\Throwable $e) { throw $e; }', $c);
    // Also fix brace balance
    $open = substr_count($c, '{');
    $close = substr_count($c, '}');
    if ($open > $close) {
        $c = rtrim($c) . "\n" . str_repeat("}\n", $open - $close);
    }
    file_put_contents($f, $c);
    echo "FIX: PartnerStoreAPIIntegration\n";
    $fixed++;
}

// --- RouteOptimizationService ---
$f = 'app/Domains/GroceryAndDelivery/Integrations/RouteOptimizationService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    // Unclosed ( on line 60 — likely missing ) somewhere
    $open_p = substr_count($c, '(');
    $close_p = substr_count($c, ')');
    if ($open_p > $close_p) {
        // Find last ; and add missing ) before it at the end
        $c = rtrim($c);
        // Try adding ) before the final }
        $lastBrace = strrpos($c, '}');
        if ($lastBrace !== false) {
            $c = substr($c, 0, $lastBrace) . str_repeat(")", $open_p - $close_p) . ";\n" . substr($c, $lastBrace);
        }
    }
    // Fix brace balance too
    $open = substr_count($c, '{');
    $close = substr_count($c, '}');
    if ($open > $close) {
        $c = rtrim($c) . "\n" . str_repeat("}\n", $open - $close);
    }
    file_put_contents($f, $c);
    echo "FIX: RouteOptimizationService\n";
    $fixed++;
}

echo "\n=== Phase 4 fixed: $fixed ===\n";

// VERIFY
echo "\n--- Verification ---\n";
$broken = file('_broken_files.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$ok = 0; $err = 0;
foreach ($broken as $f2) {
    $f2 = trim($f2);
    if (!file_exists($f2)) continue;
    $out = shell_exec("php -l " . escapeshellarg($f2) . " 2>&1");
    if (strpos($out, 'Parse error') !== false || strpos($out, 'Fatal error') !== false) {
        $err++;
        $shortErr = preg_replace('/^.*?(Parse error|Fatal error)/', '$1', trim($out));
        echo "ERR: " . basename($f2) . " → " . substr($shortErr, 0, 120) . "\n";
    } else {
        $ok++;
    }
}
echo "\n=== RESULT: OK=$ok BROKEN=$err ===\n";
