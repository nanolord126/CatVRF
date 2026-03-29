<?php
declare(strict_types=1);

$resourcesDir = 'app/Filament/Tenant/Resources';
$resourceFiles = glob("$resourcesDir/*Resource.php", GLOB_NOESCAPE) ?: [];

$created = 0;
$skipped = 0;
$errors = [];

$pageTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\%VERTICAL%\Pages;

use App\Filament\Tenant\Resources\%VERTICAL%\%VERTICAL%Resource;
use Filament\Resources\Pages\%FILAMENT_CLASS%;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class %CLASS_NAME% extends %FILAMENT_CLASS%
{
    protected static string $resource = %VERTICAL%Resource::class;

    public function getTitle(): string
    {
        return '%TITLE%';
    }
}
PHP;

echo "🔧 Creating missing Page files...\n\n";

foreach ($resourceFiles as $resourceFile) {
    $className = basename($resourceFile, '.php');
    $vertical = str_replace('Resource', '', $className);
    $pagesDir = dirname($resourceFile) . '/Pages';
    
    // Проверка, существует ли Pages директория
    if (!is_dir($pagesDir)) {
        mkdir($pagesDir, 0755, true);
    }
    
    // Определение Page типов
    $pageTypes = [
        ['file' => "List{$vertical}.php", 'class' => "List{$vertical}", 'filament' => 'ListRecords', 'title' => "List {$vertical}"],
        ['file' => "Create{$vertical}.php", 'class' => "Create{$vertical}", 'filament' => 'CreateRecord', 'title' => "Create {$vertical}"],
        ['file' => "Edit{$vertical}.php", 'class' => "Edit{$vertical}", 'filament' => 'EditRecord', 'title' => "Edit {$vertical}"],
        ['file' => "View{$vertical}.php", 'class' => "View{$vertical}", 'filament' => 'ViewRecord', 'title' => "View {$vertical}"],
    ];
    
    foreach ($pageTypes as $pageType) {
        $pagePath = $pagesDir . '/' . $pageType['file'];
        
        if (file_exists($pagePath)) {
            $skipped++;
            continue;
        }
        
        $content = str_replace(
            ['%VERTICAL%', '%CLASS_NAME%', '%FILAMENT_CLASS%', '%TITLE%'],
            [$vertical, $pageType['class'], $pageType['filament'], $pageType['title']],
            $pageTemplate
        );
        
        if (file_put_contents($pagePath, $content) === false) {
            $errors[] = "Could not create: $pagePath";
            continue;
        }
        
        $created++;
        echo "✅ Created: $vertical / {$pageType['file']}\n";
    }
}

echo "\n" . str_repeat("═", 60) . "\n";
echo "✅ Created: $created\n";
echo "⏭️  Skipped (already exist): $skipped\n";
if (!empty($errors)) {
    echo "❌ Errors: " . count($errors) . "\n";
    foreach (array_slice($errors, 0, 10) as $err) {
        echo "   • $err\n";
    }
}
echo str_repeat("═", 60) . "\n";
