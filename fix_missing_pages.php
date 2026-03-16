<?php

/**
 * Анализирует getPages() в Resources и создаёт недостающие Pages файлы
 */

function parseResourceFile($filePath) {
    $content = file_get_contents($filePath);
    
    preg_match('/namespace\s+([^;]+);/', $content, $nsMatch);
    preg_match('/class\s+(\w+)\s+/', $content, $classMatch);
    
    // Ищем getPages()
    if (!preg_match('/public\s+static\s+function\s+getPages\(\)\s*:\s*array\s*\{(.*?)\n\s+\}/s', $content, $pagesMatch)) {
        return null;
    }
    
    $pagesBlock = $pagesMatch[1];
    
    // Ищем все требуемые Pages классы: ClassName::route(...)
    preg_match_all('/Pages\\\\(\w+)::route/', $pagesBlock, $matches);
    
    if (empty($matches[1])) {
        return null;
    }
    
    return [
        'namespace' => $nsMatch[1] ?? '',
        'class' => $classMatch[1] ?? '',
        'pages' => $matches[1],
    ];
}

$basePath = __DIR__ . '/app/Filament/Tenant/Resources';

// Рекурсивно найти все Resource.php файлы
function findResourceFiles($dir) {
    $files = [];
    $items = @glob($dir . '/*');
    if ($items === false) return $files;
    
    foreach ($items as $item) {
        if (is_file($item) && basename($item) !== '.' && basename($item) !== '..' && preg_match('/Resource\.php$/', $item)) {
            $files[] = $item;
        } elseif (is_dir($item) && !in_array(basename($item), ['Pages', 'RelationManagers', 'Widgets', 'Common'])) {
            $files = array_merge($files, findResourceFiles($item));
        }
    }
    return $files;
}

$resourceFiles = findResourceFiles($basePath);
$createdCount = 0;
$processedCount = 0;

echo "Found " . count($resourceFiles) . " resource files\n\n";

foreach ($resourceFiles as $resourceFile) {
    $parsed = parseResourceFile($resourceFile);
    
    if (!$parsed) {
        continue;
    }
    
    // Для Resources в вложенных папках
    $baseName = basename($resourceFile, '.php');  // e.g. "ProductResource"
    
    // Ищем папку с этим именем
    $resourceDir = dirname($resourceFile);  // e.g. "/app/Filament/Tenant/Resources"
    
    // Проверяем папку в самом Resource (e.g. "/app/Filament/Tenant/Resources/ProductResource/")
    $possibleResourceFolderPath = $resourceDir . '/' . $baseName;
    
    // Используем директорию если она есть
    if (is_dir($possibleResourceFolderPath)) {
        $pagesDir = $possibleResourceFolderPath . '/Pages';
    } else {
        // Иначе используем текущую директорию
        $pagesDir = $resourceDir . '/Pages';
    }
    
    if (!is_dir($pagesDir)) {
        @mkdir($pagesDir, 0755, true);
    }
    
    $processedCount++;
    
    foreach ($parsed['pages'] as $pageName) {
        $pageFile = $pagesDir . '/' . $pageName . '.php';
        
        if (file_exists($pageFile)) {
            continue;
        }
        
        // Определяем тип по имени
        if (strpos($pageName, 'List') === 0) {
            $baseClass = 'ListRecords';
            $useStatement = "use Filament\Resources\Pages\ListRecords;";
        } elseif (strpos($pageName, 'Create') === 0) {
            $baseClass = 'CreateRecord';
            $useStatement = "use Filament\Resources\Pages\CreateRecord;";
        } elseif (strpos($pageName, 'Edit') === 0) {
            $baseClass = 'EditRecord';
            $useStatement = "use Filament\Resources\Pages\EditRecord;";
        } elseif (strpos($pageName, 'View') === 0) {
            $baseClass = 'ViewRecord';
            $useStatement = "use Filament\Resources\Pages\ViewRecord;";
        } else {
            // Для других типов (Manage, Kanban и т.д.)
            $baseClass = 'Page';
            $useStatement = "use Filament\Resources\Pages\Page;";
        }
        
        $pageNamespace = $parsed['namespace'] . '\\Pages';
        $resourceClass = $parsed['namespace'] . '\\' . $parsed['class'];
        
        $content = <<<PHP
<?php

namespace $pageNamespace;

$useStatement

class $pageName extends $baseClass
{
    protected static string \$resource = $resourceClass::class;
}
PHP;
        
        file_put_contents($pageFile, $content);
        $createdCount++;
        echo "[✓] " . str_replace($basePath, '', $pageFile) . "\n";
    }
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "Processed: $processedCount resources\n";
echo "Created: $createdCount pages\n";
echo str_repeat('=', 70) . "\n";
