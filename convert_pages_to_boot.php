<?php

declare(strict_types=1);

// Скрипт для конвертирования всех Filament Pages с __construct на boot() pattern

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';
$pagesToFix = [
    'AutoResource/Pages/CreateAuto.php',
    'AutoResource/Pages/EditAuto.php',
    'ConstructionResource/Pages/CreateConstruction.php',
    'ConstructionResource/Pages/EditConstruction.php',
    'CosmeticsResource/Pages/CreateCosmetics.php',
    'CosmeticsResource/Pages/EditCosmetics.php',
    'EducationCourseResource/Pages/CreateEducationCourse.php',
    'EducationCourseResource/Pages/EditEducationCourse.php',
    'FlowersItemResource/Pages/CreateFlowersItem.php',
    'FlowersOrderResource/Pages/CreateFlowersOrder.php',
    'FlowersOrderResource/Pages/EditFlowersOrder.php',
    'FlowersOrderResource/Pages/ListFlowersOrders.php',
    'FurnitureResource/Pages/CreateFurniture.php',
    'FurnitureResource/Pages/EditFurniture.php',
    'GymResource/Pages/CreateGym.php',
    'GymResource/Pages/EditGym.php',
    'HotelBookingResource/Pages/CreateHotelBooking.php',
    'HotelBookingResource/Pages/EditHotelBooking.php',
    'HotelBookingResource/Pages/ListHotelBookings.php',
    'HotelBookingResource/Pages/ShowHotelBooking.php',
    'MedicalCardResource/Pages/CreateMedicalCard.php',
    'MedicalCardResource/Pages/EditMedicalCard.php',
    'PerfumeryResource/Pages/CreatePerfumery.php',
    'PerfumeryResource/Pages/EditPerfumery.php',
    'PropertyResource/Pages/CreateProperty.php',
    'PropertyResource/Pages/EditProperty.php',
    'RestaurantDishResource/Pages/CreateRestaurantDish.php',
    'RestaurantMenuResource/Pages/CreateRestaurantMenu.php',
    'RestaurantOrderResource/Pages/CreateRestaurantOrder.php',
    'RestaurantOrderResource/Pages/EditRestaurantOrder.php',
    'RestaurantOrderResource/Pages/ListRestaurantOrders.php',
    'RestaurantTableResource/Pages/CreateRestaurantTable.php',
    'VetClinicResource/Pages/CreateVetClinic.php',
    'VetClinicResource/Pages/EditVetClinic.php',
];

$converted = 0;
$errors = [];

foreach ($pagesToFix as $page) {
    $filePath = $baseDir . '/' . $page;
    
    if (!file_exists($filePath)) {
        $errors[] = "File not found: $filePath";
        continue;
    }
    
    $content = file_get_contents($filePath);
    $original = $content;
    
    // Remove __construct method entirely
    $content = preg_replace(
        '/\n\s+public function __construct\([^)]*\) \{[^}]*\n\s+\}/s',
        '',
        $content
    );
    
    // Remove old mount() method
    $content = preg_replace(
        '/\n\s+public function mount[^:]*: void \{[^}]*\n\s+\}/s',
        '',
        $content
    );
    
    // Add boot() after protected static $resource line
    if (strpos($content, 'public function boot(') === false) {
        $bootMethod = "\n\n    public function boot(\n        Gate \$gate,\n        DatabaseManager \$database,\n        Request \$request,\n    ): void {\n        \$this->authorizeAccess();\n    }\n\n    protected function authorizeAccess(): void\n    {\n        // Override in subclasses\n    }";
        
        $content = preg_replace(
            '/(\n\s+protected static string \$resource = [\w\\\\]+::class;)/m',
            '$1' . $bootMethod,
            $content
        );
    }
    
    // Update imports if needed
    if (strpos($content, 'use Illuminate\\Support\\Facades\\Gate;') === false && strpos($content, 'use Gate;') === false) {
        $content = preg_replace(
            '/use Illuminate\\\\Http\\\\Request;/',
            "use Gate;\nuse Illuminate\\Http\\Request;",
            $content
        );
    }
    
    if (file_put_contents($filePath, $content)) {
        $converted++;
        echo "✅ Converted: $page\n";
    } else {
        $errors[] = "Failed to write: $filePath";
    }
}

echo "\n\n=== SUMMARY ===\n";
echo "✅ Converted: $converted files\n";
if ($errors) {
    echo "❌ Errors: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
}
