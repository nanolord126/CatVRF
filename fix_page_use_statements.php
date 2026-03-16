<?php

/**
 * Добавляет use statements для всех Page файлов
 */

function addUseStatementsToPages($dir) {
    $items = @glob($dir . '/*');
    if ($items === false) return;
    
    foreach ($items as $item) {
        if (is_file($item) && preg_match('/\.php$/', $item)) {
            $content = file_get_contents($item);
            
            // Пропускаем файлы которые уже имеют use statement
            if (preg_match('/^use /m', $content)) {
                continue;
            }
            
            // Определяем какой use нужен на основе extends
            if (preg_match('/extends\s+(\w+)/', $content, $matches)) {
                $className = $matches[1];
                
                // Определяем корректный use statement
                switch ($className) {
                    case 'ListRecords':
                        $use = "use Filament\Resources\Pages\ListRecords;\n\n";
                        break;
                    case 'CreateRecord':
                        $use = "use Filament\Resources\Pages\CreateRecord;\n\n";
                        break;
                    case 'EditRecord':
                        $use = "use Filament\Resources\Pages\EditRecord;\n\n";
                        break;
                    case 'ViewRecord':
                        $use = "use Filament\Resources\Pages\ViewRecord;\n\n";
                        break;
                    case 'Page':
                        $use = "use Filament\Resources\Pages\Page;\n\n";
                        break;
                    default:
                        $use = "use Filament\Resources\Pages\Page;\n\n";
                }
                
                // Добавляем use после namespace
                $content = preg_replace(
                    '/^(namespace [^;]+;)\n\n/m',
                    "$1\n\n" . $use,
                    $content
                );
                
                // Заменяем "extends Filament\..." на "extends ClassName"
                $content = str_replace(
                    'extends Filament\\Resources\\Pages\\' . $className,
                    'extends ' . $className,
                    $content
                );
                
                file_put_contents($item, $content);
                echo "[✓] " . basename($item) . "\n";
            }
        } elseif (is_dir($item) && !in_array(basename($item), ['RelationManagers', 'Widgets'])) {
            addUseStatementsToPages($item);
        }
    }
}

$basePath = __DIR__ . '/app/Filament/Tenant/Resources';
addUseStatementsToPages($basePath);
echo "\nDone\n";
