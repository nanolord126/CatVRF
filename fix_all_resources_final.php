<?php

/**
 * Исправляет все Resource файлы:
 * 1. Добавляет use Pages statement
 * 2. Исправляет ссылки на Pages в getPages()
 */

function fixResourcesAndPages($dir) {
    $items = @glob($dir . '/*');
    if ($items === false) return 0;
    
    $count = 0;
    
    foreach ($items as $item) {
        if (is_file($item) && preg_match('/Resource\.php$/', $item)) {
            // Это Resource файл
            $content = file_get_contents($item);
            $originalContent = $content;
            
            // Ищем getPages()
            if (!preg_match('/public\s+static\s+function\s+getPages\(\)/', $content)) {
                continue;
            }
            
            // Извлекаем namespace и класс
            preg_match('/namespace\s+([^;]+);/', $content, $nsMatch);
            preg_match('/class\s+(\w+)/', $content, $classMatch);
            
            if (!isset($nsMatch[1]) || !isset($classMatch[1])) {
                continue;
            }
            
            $namespace = $nsMatch[1];
            $className = $classMatch[1];
            
            // Добавляем use statement если его нет
            $pagesNamespace = $namespace . '\\' . $className . '\\Pages';
            $useStatement = "use $pagesNamespace;";
            
            if (strpos($content, $useStatement) === false && strpos($content, $pagesNamespace) === false) {
                // Ищем место для вставки use statement
                // Вставляем после namespace строки
                $content = preg_replace(
                    '/(namespace [^;]+;)\n/',
                    "$1\n\n$useStatement\n",
                    $content,
                    1
                );
            }
            
            // Теперь исправляем getPages()
            // Заменяем все ошибочные пути на простые Pages\...
            $content = preg_replace(
                '/' . preg_quote($className . '\\' . $pagesNamespace, '/') . '\\/',
                'Pages\\',
                $content
            );
            
            // Также проверяем на двойной пути с App\Filament...
            $content = preg_replace(
                '/' . preg_quote($className . '\\App\\Filament', '/') . '/',
                'Pages\\',
                $content
            );
            
            if ($content !== $originalContent) {
                file_put_contents($item, $content);
                $count++;
                echo "[✓] Fixed: " . basename($item) . "\n";
            }
        } elseif (is_dir($item) && !in_array(basename($item), ['Pages', 'RelationManagers', 'Widgets', 'Common'])) {
            $count += fixResourcesAndPages($item);
        }
    }
    
    return $count;
}

$basePath = __DIR__ . '/app/Filament/Tenant/Resources';
$fixed = fixResourcesAndPages($basePath);

echo "\n" . str_repeat('=', 70) . "\n";
echo "Fixed: $fixed resource files\n";
echo str_repeat('=', 70) . "\n";
