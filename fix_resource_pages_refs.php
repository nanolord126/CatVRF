<?php

/**
 * Исправляет getPages() в Resources - добавляет полные пути и use statements
 */

function fixResourcePages($dir) {
    $items = @glob($dir . '/*');
    if ($items === false) return 0;
    
    $count = 0;
    
    foreach ($items as $item) {
        if (is_file($item) && preg_match('/Resource\.php$/', $item)) {
            // Это Resource файл
            $content = file_get_contents($item);
            $originalContent = $content;
            
            // Ищем getPages()
            if (preg_match('/public\s+static\s+function\s+getPages\(\)/', $content)) {
                // Извлекаем namespace и класс
                preg_match('/namespace\s+([^;]+);/', $content, $nsMatch);
                preg_match('/class\s+(\w+)/', $content, $classMatch);
                
                if (!isset($nsMatch[1]) || !isset($classMatch[1])) {
                    continue;
                }
                
                $namespace = $nsMatch[1];
                $className = $classMatch[1];
                
                // Определяем полный namespace для Pages
                // Например: App\Filament\Tenant\Resources\BeautyProductResource
                // должен иметь Pages в: App\Filament\Tenant\Resources\BeautyProductResource\Pages
                
                // Но если Resource находится в подпапке (e.g. HR\EmployeeResource), то Pages в HR\EmployeeResource\Pages
                
                $baseName = str_replace('Resource', '', $className);
                $pagesNamespace = $namespace . '\\' . $className . '\\Pages';
                
                // Заменяем ссылки на Pages
                // Ищем паттерны типа: Pages\ListBeautyProducts::route(...)
                $content = preg_replace_callback(
                    '/Pages\\\\(\w+)::route/',
                    function ($matches) use ($pagesNamespace, $className) {
                        $pageName = $matches[1];
                        return "$pagesNamespace\\$pageName::route";
                    },
                    $content
                );
                
                if ($content !== $originalContent) {
                    file_put_contents($item, $content);
                    $count++;
                    echo "[✓] Fixed: " . basename($item) . "\n";
                }
            }
        } elseif (is_dir($item) && !in_array(basename($item), ['Pages', 'RelationManagers', 'Widgets', 'Common'])) {
            $count += fixResourcePages($item);
        }
    }
    
    return $count;
}

$basePath = __DIR__ . '/app/Filament/Tenant/Resources';
$fixed = fixResourcePages($basePath);

echo "\n" . str_repeat('=', 70) . "\n";
echo "Fixed: $fixed resource files\n";
echo str_repeat('=', 70) . "\n";
