<?php
declare(strict_types=1);

$resources = [
    'Construction',
    'Repair',
    'GardenProduct',
    'Flower',
    'Restaurant',
    'TaxiService',
    'EducationCourse',
];

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';

foreach ($resources as $resourceName) {
    $filePath = "$baseDir/{$resourceName}Resource.php";
    
    if (!file_exists($filePath)) {
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Fix namespace
    $content = str_replace(
        'namespace App\Filament\Tenant\Resources;',
        'namespace App\Filament\Tenant\Resources\Marketplace;',
        $content
    );
    
    // Determine singular and plural names
    if (str_ends_with($resourceName, 'Product')) {
        $singular = str_replace('Product', '', $resourceName);
        $plural = $resourceName . 's';
    } elseif (str_ends_with($resourceName, 'Service')) {
        $singular = str_replace('Service', '', $resourceName);
        $plural = $resourceName . 's';
    } elseif (str_ends_with($resourceName, 'Course')) {
        $singular = str_replace('Course', '', $resourceName);
        $plural = $resourceName . 's';
    } else {
        $singular = $resourceName;
        $plural = $resourceName . 's';
    }
    
    // Fix getPages method
    $content = preg_replace_callback(
        '/public static function getPages\(\): array\s*\{.*?\}/s',
        function($matches) use ($resourceName, $singular, $plural) {
            return <<<EOT
public static function getPages(): array
    {
        return [
            'index' => \\App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource\\Pages\\List{$plural}::route('/'),
            'create' => \\App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource\\Pages\\Create{$singular}::route('/create'),
            'edit' => \\App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource\\Pages\\Edit{$singular}::route('/{record}/edit'),
            'view' => \\App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource\\Pages\\View{$singular}::route('/{record}'),
        ];
    }
EOT;
        },
        $content
    );
    
    file_put_contents($filePath, $content);
    echo "✅ Updated {$resourceName}Resource.php with Marketplace namespace\n";
}

echo "\n✅ All Resources updated with correct namespace!\n";
