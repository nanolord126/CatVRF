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

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources';

foreach ($resources as $resourceName) {
    $pagesDir = "$baseDir/{$resourceName}Resource/Pages";
    
    if (!is_dir($pagesDir)) {
        mkdir($pagesDir, 0755, true);
    }
    
    // Generate singular name for pages
    $singularName = preg_replace('/(Resource|Product|Service|Course)$/', '', $resourceName);
    $pluralName = $resourceName;
    if (str_ends_with($resourceName, 'Product')) {
        $pluralName = substr($resourceName, 0, -7) . 'Products';
    } elseif (str_ends_with($resourceName, 'Service')) {
        $pluralName = substr($resourceName, 0, -7) . 'Services';
    } elseif (str_ends_with($resourceName, 'Course')) {
        $pluralName = substr($resourceName, 0, -6) . 'Courses';
    } else {
        $pluralName = $resourceName . 's';
    }
    
    // List Page
    $listPageContent = <<<EOT
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages;

use App\\Filament\\Tenant\\Resources\\{$resourceName}Resource;
use Filament\\Actions;
use Filament\\Resources\\Pages\\ListRecords;
use Illuminate\\Contracts\\Auth\\Guard;

final class List{$pluralName} extends ListRecords
{
    protected static string \$resource = {$resourceName}Resource::class;

    public function __construct(
        private Guard \$guard,
    ) {
        parent::__construct();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\\CreateAction::make(),
        ];
    }
}
EOT;

    file_put_contents("$pagesDir/List{$pluralName}.php", $listPageContent);
    echo "✅ Created: List{$pluralName}.php\n";
    
    // Create Page
    $createPageContent = <<<EOT
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages;

use App\\Filament\\Tenant\\Resources\\{$resourceName}Resource;
use Filament\\Resources\\Pages\\CreateRecord;

final class Create{$singularName} extends CreateRecord
{
    protected static string \$resource = {$resourceName}Resource::class;
}
EOT;

    file_put_contents("$pagesDir/Create{$singularName}.php", $createPageContent);
    echo "✅ Created: Create{$singularName}.php\n";
    
    // Edit Page
    $editPageContent = <<<EOT
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages;

use App\\Filament\\Tenant\\Resources\\{$resourceName}Resource;
use Filament\\Actions;
use Filament\\Resources\\Pages\\EditRecord;

final class Edit{$singularName} extends EditRecord
{
    protected static string \$resource = {$resourceName}Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\\DeleteAction::make(),
        ];
    }
}
EOT;

    file_put_contents("$pagesDir/Edit{$singularName}.php", $editPageContent);
    echo "✅ Created: Edit{$singularName}.php\n";
    
    // View Page
    $viewPageContent = <<<EOT
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages;

use App\\Filament\\Tenant\\Resources\\{$resourceName}Resource;
use Filament\\Actions;
use Filament\\Resources\\Pages\\ViewRecord;

final class View{$singularName} extends ViewRecord
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
EOT;

    file_put_contents("$pagesDir/View{$singularName}.php", $viewPageContent);
    echo "✅ Created: View{$singularName}.php\n";
    
    echo "\n";
}

echo "✅ All Pages created!\n";
