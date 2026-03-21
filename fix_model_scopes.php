<?php
$files = [
    'app/Models/BalanceTransaction.php',
    'app/Models/BusinessGroup.php',
    'app/Models/InventoryItem.php',
    'app/Models/PaymentTransaction.php',
    'app/Models/Wallet.php',
    'app/Models/Domains/Clinic/MedicalCard.php',
    'app/Models/Domains/Food/FoodOrder.php',
    'app/Models/Domains/Hotel/HotelBooking.php',
    'app/Models/Domains/Sports/SportsMembership.php',
    'app/Domains/AutoParts/Models/AutoPartItem.php',
    'app/Domains/AutoParts/Models/AutoPartOrder.php',
    'app/Domains/Books/Models/BookOrder.php',
    'app/Domains/Books/Models/BookReview.php',
    'app/Domains/ConstructionMaterials/Models/MaterialOrder.php',
    'app/Domains/Cosmetics/Models/CosmeticOrder.php',
    'app/Domains/Electronics/Models/ElectronicOrder.php',
    'app/Domains/Electronics/Models/ElectronicProduct.php',
    'app/Domains/Electronics/Models/WarrantyClaim.php',
    'app/Domains/FreshProduce/Models/FarmSupplier.php',
    'app/Domains/FreshProduce/Models/FreshProduct.php',
    'app/Domains/FreshProduce/Models/ProduceBox.php',
    'app/Domains/FreshProduce/Models/ProduceOrder.php',
    'app/Domains/FreshProduce/Models/ProduceSubscription.php',
    'app/Domains/Furniture/Models/FurnitureItem.php',
    'app/Domains/Furniture/Models/FurnitureOrder.php',
    'app/Domains/HealthyFood/Models/DietPlan.php',
    'app/Domains/HealthyFood/Models/HealthyMeal.php',
    'app/Domains/HealthyFood/Models/MealSubscription.php',
    'app/Domains/HomeServices/Models/HomeServiceJob.php',
    'app/Domains/Jewelry/Models/JewelryOrder.php',
    'app/Domains/Logistics/Models/Courier.php',
    'app/Domains/Logistics/Models/DeliveryOrder.php',
    'app/Domains/MeatShops/Models/MeatOrder.php',
    'app/Domains/MeatShops/Models/MeatProduct.php',
    'app/Domains/OfficeCatering/Models/CorporateClient.php',
    'app/Domains/OfficeCatering/Models/CorporateOrder.php',
    'app/Domains/OfficeCatering/Models/OfficeMenu.php',
    'app/Domains/Pharmacy/Models/Medicine.php',
    'app/Domains/Pharmacy/Models/PharmacyOrder.php',
    'app/Domains/Pharmacy/Models/Prescription.php',
    'app/Domains/ToysKids/Models/ToyOrder.php',
    'app/Domains/ToysKids/Models/ToyProduct.php'
];

$fixed = 0;
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    if (strpos($content, 'addGlobalScope') !== false) continue;
    if (strpos($content, 'Bootable') !== false || strpos($content, 'trait ') !== false) continue; // safety check

    $insertBoot = "\n    protected static function booted(): void\n    {\n        parent::booted();\n        static::addGlobalScope('tenant_id', function (\$query) {\n            if (function_exists('tenant') && tenant('id')) {\n                \$query->where('tenant_id', tenant('id'));\n            }\n        });\n    }\n";
    
    // Check if booted() already exists
    if (strpos($content, 'protected static function booted()') !== false) {
        // Need to inject inside existing booted()
        // It's safer to just let me know to manually fix it, but let's try regex:
        $content = preg_replace(
            '/(protected\s+static\s+function\s+booted\s*\(\)\s*(?::\s*void)?\s*\{)(?:\s*\n)?/i',
            "$1\n        static::addGlobalScope('tenant_id', function (\$query) {\n            if (function_exists('tenant') && tenant('id')) {\n                \$query->where('tenant_id', tenant('id'));\n            }\n        });\n",
            $content
        );
        file_put_contents($file, $content);
        $fixed++;
    } else {
        // Inject before the last closing brace
        $content = preg_replace('/}(?=\s*$)/', $insertBoot . "}", $content);
        file_put_contents($file, $content);
        $fixed++;
    }
}
echo "Booted Global Scope attached to Models: $fixed\n";
