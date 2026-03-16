<?php
/**
 * Fix duplicate __construct methods
 * Keeps the SECOND one (more complete), removes the FIRST one
 */

$files = [
    'app/Filament/Tenant/Resources/Marketplace/ConstructionResource/Pages/CreateConstruction.php',
    'app/Filament/Tenant/Resources/Marketplace/ConstructionResource/Pages/EditConstruction.php',
    'app/Filament/Tenant/Resources/Marketplace/CosmeticsResource/Pages/CreateCosmetics.php',
    'app/Filament/Tenant/Resources/Marketplace/CosmeticsResource/Pages/EditCosmetics.php',
    'app/Filament/Tenant/Resources/Marketplace/CosmeticsResource/Pages/ListCosmetics.php',
    'app/Filament/Tenant/Resources/Marketplace/GymResource/Pages/CreateGym.php',
    'app/Filament/Tenant/Resources/Marketplace/GymResource/Pages/EditGym.php',
    'app/Filament/Tenant/Resources/Marketplace/HotelBookingResource/Pages/CreateHotelBooking.php',
    'app/Filament/Tenant/Resources/Marketplace/HotelBookingResource/Pages/EditHotelBooking.php',
    'app/Filament/Tenant/Resources/Marketplace/HotelBookingResource/Pages/ListHotelBookings.php',
    'app/Filament/Tenant/Resources/Marketplace/MedicalCardResource/Pages/CreateMedicalCard.php',
    'app/Filament/Tenant/Resources/Marketplace/MedicalCardResource/Pages/EditMedicalCard.php',
    'app/Filament/Tenant/Resources/Marketplace/MedicalCardResource/Pages/ListMedicalCards.php',
    'app/Filament/Tenant/Resources/Marketplace/PerfumeryResource/Pages/CreatePerfumery.php',
    'app/Filament/Tenant/Resources/Marketplace/PerfumeryResource/Pages/EditPerfumery.php',
    'app/Filament/Tenant/Resources/Marketplace/PerfumeryResource/Pages/ListPerfumeries.php',
    'app/Filament/Tenant/Resources/Marketplace/PropertyResource/Pages/CreateProperty.php',
    'app/Filament/Tenant/Resources/Marketplace/PropertyResource/Pages/EditProperty.php',
];

$fixed = 0;
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Find and remove FIRST __construct only if there are TWO
    if (substr_count($content, 'public function __construct(') === 2) {
        // Remove first __construct (keep second)
        $content = preg_replace(
            '/\n\s+public function __construct\s*\(\s*protected[^)]*\)\s*\{\}\s+/m',
            "\n",
            $content,
            1  // Only replace first occurrence
        );
        
        if ($original !== $content) {
            file_put_contents($file, $content);
            $fixed++;
            echo "✓ " . str_replace('app/', '', $file) . "\n";
        }
    }
}

echo "\n=== Duplicate Constructor Fix ===\n";
echo "Files fixed: $fixed\n";
?>
