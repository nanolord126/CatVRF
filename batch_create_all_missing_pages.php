<?php
declare(strict_types=1);

$resourcesDir = 'app/Filament/Tenant/Resources';
$resourceFiles = glob("$resourcesDir/*Resource.php", GLOB_NOESCAPE) ?: [];

echo "🔧 Creating missing Page files to reach 100% coverage...\n\n";

$created = 0;
$total = 0;

foreach ($resourceFiles as $resourceFile) {
    $resourceName = basename($resourceFile, '.php');
    $vertical = str_replace('Resource', '', $resourceName);
    
    // Определяем директорию для Pages
    // Может быть: app/Filament/Tenant/Resources/{vertical}/Pages или {vertical}Resource/Pages
    $dir1 = "$resourcesDir/$vertical/Pages";
    $dir2 = "$resourcesDir/{$resourceName}/Pages";
    
    $pagesDir = is_dir($dir1) ? $dir1 : (is_dir($dir2) ? $dir2 : $dir1);
    
    if (!is_dir($pagesDir)) {
        mkdir($pagesDir, 0755, true);
    }
    
    // Создать 4 Page типа
    $pageTypes = [
        "List{$vertical}.php" => "ListRecords",
        "Create{$vertical}.php" => "CreateRecord",
        "Edit{$vertical}.php" => "EditRecord",
        "View{$vertical}.php" => "ViewRecord",
    ];
    
    foreach ($pageTypes as $fileName => $filamentClass) {
        $total++;
        $pagePath = "$pagesDir/$fileName";
        
        if (file_exists($pagePath)) {
            continue; // Пропустить существующие
        }
        
        // Вычислить правильный namespace
        if (is_dir($dir1)) {
            $namespace = "App\\Filament\\Tenant\\Resources\\$vertical\\Pages";
            $resourceRef = "{$vertical}Resource";
        } else {
            $namespace = "App\\Filament\\Tenant\\Resources\\{$resourceName}\\Pages";
            $resourceRef = $resourceName;
        }
        
        $classNameBase = str_replace('.php', '', $fileName);
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace $namespace;

use App\\Filament\\Tenant\\Resources\\{$vertical}\\$resourceRef;
use Filament\\Resources\\Pages\\$filamentClass;

final class $classNameBase extends $filamentClass
{
    protected static string \\$resource = $resourceRef::class;
}
PHP;
        
        file_put_contents($pagePath, $content);
        $created++;
        
        if ($created % 50 === 0) {
            echo "✅ Created $created pages...\n";
        }
    }
}

echo "\n✅ Total pages created: $created out of $total\n";
echo "📊 Coverage: " . round(($created / ($total ?: 1)) * 100, 1) . "%\n";
