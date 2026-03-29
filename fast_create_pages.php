<?php
declare(strict_types=1);

error_reporting(E_ERROR);
ini_set('display_errors', '0');

$base = 'c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources';
$resources = glob($base . '\*Resource.php');

$created = 0;
$total = count($resources) * 4;
$batch = ceil($total / 10);

echo "Creating all missing Pages...\n\n";

foreach ($resources as $idx => $resource) {
    $name = basename($resource, 'Resource.php');
    $dir = dirname($resource);
    $pages_dir = $dir . '\\' . $name . '\\Pages';
    
    if (!is_dir($pages_dir)) {
        @mkdir($pages_dir, 0755, true);
    }
    
    $templates = [
        'List' => '<?php declare(strict_types=1); namespace App\Filament\Tenant\Resources\\' . $name . '\Pages; use App\Filament\Tenant\Resources\\' . $name . 'Resource; use Filament\Resources\Pages\ListRecords; final class List' . $name . ' extends ListRecords { protected static string $resource = ' . $name . 'Resource::class; }',
        'Create' => '<?php declare(strict_types=1); namespace App\Filament\Tenant\Resources\\' . $name . '\Pages; use App\Filament\Tenant\Resources\\' . $name . 'Resource; use Filament\Resources\Pages\CreateRecord; final class Create' . $name . ' extends CreateRecord { protected static string $resource = ' . $name . 'Resource::class; }',
        'Edit' => '<?php declare(strict_types=1); namespace App\Filament\Tenant\Resources\\' . $name . '\Pages; use App\Filament\Tenant\Resources\\' . $name . 'Resource; use Filament\Resources\Pages\EditRecord; final class Edit' . $name . ' extends EditRecord { protected static string $resource = ' . $name . 'Resource::class; }',
        'View' => '<?php declare(strict_types=1); namespace App\Filament\Tenant\Resources\\' . $name . '\Pages; use App\Filament\Tenant\Resources\\' . $name . 'Resource; use Filament\Resources\Pages\ViewRecord; final class View' . $name . ' extends ViewRecord { protected static string $resource = ' . $name . 'Resource::class; }'
    ];
    
    foreach ($templates as $type => $content) {
        $file = $pages_dir . '\\' . $type . $name . '.php';
        if (!file_exists($file)) {
            @file_put_contents($file, $content);
            $created++;
            if ($created % $batch == 0) {
                echo "✅ Created $created / $total Pages...\n";
            }
        }
    }
}

echo "\n✅ DONE! Created: $created Pages\n";
echo "═══════════════════════════════════════\n";
