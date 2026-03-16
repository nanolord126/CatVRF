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
    $resourceDir = "$baseDir/{$resourceName}Resource";
    
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
    
    $pagesDir = "$resourceDir/Pages";
    
    // Create directory if needed
    if (!is_dir($pagesDir)) {
        mkdir($pagesDir, 0755, true);
        echo "✅ Created directory: {$resourceName}Resource/Pages\n";
    }
    
    // Create List Page
    $listPageCode = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource\\Pages;

use App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource;
use Filament\\Actions;
use Filament\\Resources\\Pages\\ListRecords;

final class List{$plural} extends ListRecords
{
    protected static string \$resource = {$resourceName}Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\\CreateAction::make(),
        ];
    }
}
PHP;

    file_put_contents("$pagesDir/List{$plural}.php", $listPageCode);
    echo "✅ Created List{$plural}.php\n";
    
    // Create Create Page
    $createPageCode = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource\\Pages;

use App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource;
use Filament\\Resources\\Pages\\CreateRecord;

final class Create{$singular} extends CreateRecord
{
    protected static string \$resource = {$resourceName}Resource::class;
}
PHP;

    file_put_contents("$pagesDir/Create{$singular}.php", $createPageCode);
    echo "✅ Created Create{$singular}.php\n";
    
    // Create Edit Page
    $editPageCode = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource\\Pages;

use App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource;
use Filament\\Actions;
use Filament\\Resources\\Pages\\EditRecord;

final class Edit{$singular} extends EditRecord
{
    protected static string \$resource = {$resourceName}Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\\DeleteAction::make(),
        ];
    }
}
PHP;

    file_put_contents("$pagesDir/Edit{$singular}.php", $editPageCode);
    echo "✅ Created Edit{$singular}.php\n";
    
    // Create View Page
    $viewPageCode = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource\\Pages;

use App\\Filament\\Tenant\\Resources\\Marketplace\\{$resourceName}Resource;
use Filament\\Actions;
use Filament\\Resources\\Pages\\ViewRecord;

final class View{$singular} extends ViewRecord
{
    protected static string \$resource = {$resourceName}Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\\EditAction::make(),
            Actions\\DeleteAction::make(),
        ];
    }
}
PHP;

    file_put_contents("$pagesDir/View{$singular}.php", $viewPageCode);
    echo "✅ Created View{$singular}.php\n\n";
}

echo "✅ All Pages created in Marketplace!\n";
