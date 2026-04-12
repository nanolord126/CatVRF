<?php
/**
 * PHP syntax check on a sample of important files.
 * Run `php -l` on critical services and recently modified files.
 */

$filesToCheck = [
    // Core services
    'app/Services/FraudControlService.php',
    'app/Services/AuditService.php',
    'app/Services/AI/AIConstructorService.php',
    'app/Services/MeshService.php',
    'app/Services/RecommendationService.php',
    // Wallet/Payment
    'app/Domains/Finances/Domain/Services/WalletService.php',
    'app/Domains/Finances/Domain/Services/BonusService.php',
    'app/Domains/Payment/Services/PaymentCoordinatorService.php',
    'app/Services/Payment/PaymentGatewayService.php',
    'app/Services/HR/PayrollService.php',
    // Delivery
    'app/Domains/Delivery/Services/GeotrackingService.php',
    'app/Domains/Delivery/Services/MapService.php',
    'app/Domains/Delivery/Services/RouteOptimizationService.php',
    // Domains
    'app/Domains/Beauty/Services/BeautyService.php',
    'app/Domains/Food/Services/FoodService.php',
    'app/Domains/Fashion/Services/FashionService.php',
    'app/Domains/Taxi/Services/SurgeService.php',
    'app/Domains/Education/Services/AICourseGeneratorService.php',
    'app/Domains/Education/Services/VideoCallService.php',
    // Middleware
    'app/Http/Middleware/CorrelationIdMiddleware.php',
    // Livewire
    'app/Livewire/User/Dashboard.php',
    'app/Livewire/User/DeliveryTrack.php',
    'app/Livewire/User/AIConstructor.php',
    // Request
    'app/Http/Requests/AI/RunConstructorRequest.php',
    'app/Http/Requests/Luxury/LuxuryBookingRequest.php',
    // Exceptions
    'app/Exceptions/InsufficientStockException.php',
];

$errors = 0;
$ok = 0;
$missing = 0;

foreach ($filesToCheck as $relPath) {
    $fullPath = __DIR__ . '/' . $relPath;
    if (!file_exists($fullPath)) {
        $missing++;
        continue;
    }
    
    $output = [];
    exec("php -l \"{$fullPath}\" 2>&1", $output, $exitCode);
    
    if ($exitCode !== 0) {
        echo "❌ SYNTAX ERROR: {$relPath}\n";
        foreach ($output as $line) echo "   {$line}\n";
        $errors++;
    } else {
        $ok++;
    }
}

echo "\n=== SYNTAX CHECK ===\n";
echo "✅ OK: {$ok}\n";
echo "❌ Errors: {$errors}\n";
echo "⚠️ Missing: {$missing}\n";

if ($errors === 0) {
    echo "\n🎉 ALL FILES PASS SYNTAX CHECK!\n";
}
