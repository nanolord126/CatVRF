<?php

$domains = [
    'AutoParts',
    'Beauty',
    'Confectionery',
    'Events',
    'FashionRetail',
    'Finances',
    'Flowers',
    'FreshProduce',
    'HealthyFood',
    'MeatShops',
    'MedicalHealthcare',
    'MedicalSupplies',
    'OfficeCatering',
    'PetServices',
    'Pharmacy',
    'Photography',
    'Sports',
    'TravelTourism'
];

foreach ($domains as $domain) {
    echo "Processing $domain...\n";
    $serviceDir = __DIR__ . "/app/Domains/$domain/Services";
    if (!is_dir($serviceDir)) continue;

    $files = glob("$serviceDir/*.php");
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        if (strpos($content, 'FraudControlService::check') !== false) {
            continue; // Already patched
        }

        // Add uses
        $useStatements = "use Illuminate\\Support\\Facades\\Log;\nuse App\\Services\\Security\\FraudControlService;\nuse Illuminate\\Support\\Str;\n";
        $content = preg_replace('/namespace App\\\\Domains\\\\([A-Za-z0-9_]+)\\\\Services;/', "namespace App\\Domains\\\\$1\\Services;\n\n" . $useStatements, $content);

        // Replace public function to include fraud check
        $pattern = '/(public function\s+[a-zA-Z0-9_]+\s*\([^)]*\)\s*(?::\s*[a-zA-Z0-9_\\\?]+)?\s*\{)(?!\s*\\$correlationId\s*=)/m';
        
        $replacement = "$1\n        \$correlationId = Str::uuid()->toString();\n        Log::channel('audit')->info('Service method called in $domain', ['correlation_id' => \$correlationId]);\n        FraudControlService::check('service_operation', ['correlation_id' => \$correlationId]);\n";
        
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent !== $content && $newContent !== null) {
            file_put_contents($file, $newContent);
            echo "Patched: " . basename($file) . "\n";
        }
    }
}
