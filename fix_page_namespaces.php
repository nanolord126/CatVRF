<?php

/**
 * Исправляет namespace для всех Page файлов
 */

function fixPageNamespaces($dir, $parentNamespace = '') {
    $items = @glob($dir . '/*');
    if ($items === false) return 0;
    
    $count = 0;
    
    foreach ($items as $item) {
        if (is_file($item) && preg_match('/\.php$/', $item) && preg_match('/^(List|Create|Edit|View|Manage|Kanban)/', basename($item))) {
            // Это Page файл
            $content = file_get_contents($item);
            
            // Определяем правильный namespace на основе пути
            $dir_parts = explode('\\', str_replace('/', '\\', $dir));
            $resource_idx = array_search('Resources', $dir_parts);
            
            if ($resource_idx !== false) {
                // Собираем namespace от Resources до Pages
                $ns_parts = ['App', 'Filament', 'Tenant', 'Resources'];
                
                // Добавляем часть между Resources и Pages
                for ($i = $resource_idx + 1; $i < count($dir_parts); $i++) {
                    $part = $dir_parts[$i];
                    if ($part !== 'Pages' && !empty($part)) {
                        $ns_parts[] = $part;
                    }
                }
                
                $ns_parts[] = 'Pages';
                $correct_namespace = implode('\\', $ns_parts);
                
                // Проверяем текущий namespace
                if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
                    $current_namespace = $matches[1];
                    
                    if ($current_namespace !== $correct_namespace) {
                        // Заменяем namespace
                        $content = preg_replace(
                            '/^namespace\s+[^;]+;/m',
                            'namespace ' . $correct_namespace . ';',
                            $content
                        );
                        
                        file_put_contents($item, $content);
                        echo "[✓] Fixed: " . basename($item) . " → " . $correct_namespace . "\n";
                        $count++;
                    }
                }
            }
        } elseif (is_dir($item) && !in_array(basename($item), ['RelationManagers', 'Widgets', 'Common'])) {
            $count += fixPageNamespaces($item, $parentNamespace);
        }
    }
    
    return $count;
}

$basePath = __DIR__ . '/app/Filament/Tenant/Resources';
$fixed = fixPageNamespaces($basePath);

echo "\n" . str_repeat('=', 70) . "\n";
echo "Fixed: $fixed page files\n";
echo str_repeat('=', 70) . "\n";
