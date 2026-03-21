<?php

$files = [
    'C:/opt/kotvrf/CatVRF/app/Domains/Fitness/Http/Controllers/TrainerController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Freelance/Http/Controllers/ProposalController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/Logistics/Http/Controllers/CourierServiceController.php',
    'C:/opt/kotvrf/CatVRF/app/Domains/RealEstate/Http/Controllers/PropertyController.php',
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
echo "Done for remaining controllers.\n";
