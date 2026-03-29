<?php
declare(strict_types=1);

$resourcesDir = 'app/Filament/Tenant/Resources';
$resources = glob("$resourcesDir/*Resource.php", GLOB_NOESCAPE) ?: [];

$fixed = 0;
$skipped = 0;
$errors = [];

echo "🔧 Adding getPages() methods to Resources...\n";

foreach ($resources as $resourceFile) {
    $content = file_get_contents($resourceFile);
    $className = basename($resourceFile, '.php');
    $vertical = str_replace('Resource', '', $className);
    
    // Проверка наличия getPages()
    if (strpos($content, 'public function getPages()') !== false) {
        $skipped++;
        echo "⏭️  $className: Already has getPages()\n";
        continue;
    }
    
    // Поиск последнего метода перед закрывающей скобкой класса
    if (!preg_match('/^(.*?)(    \})\s*$/ms', $content, $matches)) {
        $errors[] = "$className: Could not find class closing bracket";
        continue;
    }
    
    $beforeEnd = $matches[1];
    
    $getPagesMeth = "\n    public static function getPages(): array\n    {\n"
        . "        return [\n"
        . "            'index' => Pages\\\List{$vertical}::route('/'),\n"
        . "            'create' => Pages\\\Create{$vertical}::route('/create'),\n"
        . "            'edit' => Pages\\\Edit{$vertical}::route('/{record}/edit'),\n"
        . "            'view' => Pages\\\View{$vertical}::route('/{record}'),\n"
        . "        ];\n"
        . "    }\n";
    
    $newContent = $beforeEnd . $getPagesMeth . "}\n";
    
    if (file_put_contents($resourceFile, $newContent) === false) {
        $errors[] = "$className: Could not write file";
        continue;
    }
    
    $fixed++;
    echo "✅ $className: Added getPages() method\n";
}

echo "\n" . str_repeat("═", 60) . "\n";
echo "✅ Fixed: $fixed\n";
echo "⏭️  Skipped: $skipped\n";
if (!empty($errors)) {
    echo "❌ Errors: " . count($errors) . "\n";
    foreach ($errors as $err) {
        echo "   • $err\n";
    }
}
echo str_repeat("═", 60) . "\n";
