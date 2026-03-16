<?php
declare(strict_types=1);

$resources = [
    [
        'name' => 'Construction',
        'singular' => 'Construction',
        'plural' => 'Constructions',
    ],
    [
        'name' => 'Repair',
        'singular' => 'Repair',
        'plural' => 'Repairs',
    ],
    [
        'name' => 'GardenProduct',
        'singular' => 'GardenProduct',
        'plural' => 'GardenProducts',
    ],
    [
        'name' => 'Flower',
        'singular' => 'Flower',
        'plural' => 'Flowers',
    ],
    [
        'name' => 'Restaurant',
        'singular' => 'Restaurant',
        'plural' => 'Restaurants',
    ],
    [
        'name' => 'TaxiService',
        'singular' => 'TaxiService',
        'plural' => 'TaxiServices',
    ],
    [
        'name' => 'EducationCourse',
        'singular' => 'EducationCourse',
        'plural' => 'EducationCourses',
    ],
];

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources';

foreach ($resources as $resource) {
    $filePath = "$baseDir/{$resource['name']}Resource.php";
    
    if (!file_exists($filePath)) {
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Build the correct getPages method
    $getPages = <<<EOT
    public static function getPages(): array
    {
        return [
            'index' => \\App\\Filament\\Tenant\\Resources\\{$resource['name']}Resource\\Pages\\List{$resource['plural']}::route(),
            'create' => \\App\\Filament\\Tenant\\Resources\\{$resource['name']}Resource\\Pages\\Create{$resource['singular']}::route(),
            'edit' => \\App\\Filament\\Tenant\\Resources\\{$resource['name']}Resource\\Pages\\Edit{$resource['singular']}::route(),
            'view' => \\App\\Filament\\Tenant\\Resources\\{$resource['name']}Resource\\Pages\\View{$resource['singular']}::route(),
        ];
    }
}
EOT;

    // Replace the getPages method
    $content = preg_replace(
        '/public static function getPages\(\): array\s*\{[^}]*\}/s',
        $getPages,
        $content
    );
    
    file_put_contents($filePath, $content);
    echo "✅ Updated getPages for {$resource['name']}Resource\n";
}

echo "\n✅ All Resources getPages updated!\n";
