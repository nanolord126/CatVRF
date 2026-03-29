<?php
declare(strict_types=1);

$base = 'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources';
$resources = glob($base . '/*Resource.php');

$created = 0;
$errors = 0;

foreach ($resources as $resource) {
    $name = basename($resource, 'Resource.php');
    $dir = dirname($resource);
    $pages_dir = $dir . '/' . $name . '/Pages';
    
    // Ensure Pages directory exists
    if (!is_dir($pages_dir)) {
        @mkdir($pages_dir, 0755, true);
    }
    
    // Templates for each page type
    $templates = [
        'List' => <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\%VERTICAL%\Pages;

use App\Filament\Tenant\Resources\%VERTICAL%Resource;
use Filament\Resources\Pages\ListRecords;

final class List%VERTICAL% extends ListRecords
{
    protected static string $resource = %VERTICAL%Resource::class;
}
PHP,
        'Create' => <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\%VERTICAL%\Pages;

use App\Filament\Tenant\Resources\%VERTICAL%Resource;
use Filament\Resources\Pages\CreateRecord;

final class Create%VERTICAL% extends CreateRecord
{
    protected static string $resource = %VERTICAL%Resource::class;
}
PHP,
        'Edit' => <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\%VERTICAL%\Pages;

use App\Filament\Tenant\Resources\%VERTICAL%Resource;
use Filament\Resources\Pages\EditRecord;

final class Edit%VERTICAL% extends EditRecord
{
    protected static string $resource = %VERTICAL%Resource::class;
}
PHP,
        'View' => <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\%VERTICAL%\Pages;

use App\Filament\Tenant\Resources\%VERTICAL%Resource;
use Filament\Resources\Pages\ViewRecord;

final class View%VERTICAL% extends ViewRecord
{
    protected static string $resource = %VERTICAL%Resource::class;
}
PHP
    ];
    
    foreach ($templates as $type => $template) {
        $file = $pages_dir . '/' . $type . $name . '.php';
        
        if (!file_exists($file)) {
            $content = str_replace('%VERTICAL%', $name, $template);
            if (file_put_contents($file, $content) !== false) {
                $created++;
            } else {
                $errors++;
                echo "❌ Failed to create: $file\n";
            }
        }
    }
}

echo "\n✅ Created Pages: $created\n";
echo "❌ Errors: $errors\n";
echo "═══════════════════════════════════════\n";
echo "✅ PAGES GENERATION COMPLETE!\n";
