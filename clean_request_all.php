<?php

$files = [
    'C:/opt/kotvrf/CatVRF/app/Domains/Fashion/Http/Controllers/FashionOrderController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Fashion/Http/Controllers/FashionProductController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Fashion/Http/Controllers/FashionReturnController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Fashion/Http/Controllers/FashionReviewController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Fashion/Http/Controllers/FashionStoreController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Fitness/Http/Controllers/FitnessClassController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Fitness/Http/Controllers/GymController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Fitness/Http/Controllers/MembershipController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Freelance/Http/Controllers/FreelanceContractController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Freelance/Http/Controllers/FreelanceJobController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Freelance/Http/Controllers/FreelanceProposalController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Freelance/Http/Controllers/FreelancerController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Freelance/Http/Controllers/FreelanceServiceController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Logistics/Http/Controllers/CourierController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Logistics/Http/Controllers/CourierTaskController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Logistics/Http/Controllers/DeliveryOrderController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Logistics/Http/Controllers/DeliveryZoneController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Logistics/Http/Controllers/StockMovementController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Logistics/Http/Controllers/WarehouseController.php',
];

$replacement = "except(['id', 'tenant_id', 'business_group_id', 'correlation_id'])";

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, '->all()') !== false) {
            $updatedContent = str_replace('->all()', '->' . $replacement, $content);
            file_put_contents($file, $updatedContent);
            echo "Updated: $file\n";
        }
    }
}
echo "Done.\n";
