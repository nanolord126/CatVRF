<?php

/**
 * Создаёт Pages с ТОЧНЫМИ именами на основе getPages() в Resources
 */

function findResourceFiles($dir) {
    $files = [];
    $items = glob($dir . '/*');
    foreach ($items as $item) {
        if (is_file($item) && preg_match('/Resource\.php$/', $item)) {
            $files[] = $item;
        } elseif (is_dir($item) && !in_array(basename($item), ['Pages', 'RelationManagers', 'Widgets', 'Common'])) {
            $files = array_merge($files, findResourceFiles($item));
        }
    }
    return $files;
}

$basePath = __DIR__ . '/app/Filament/Tenant/Resources';
$resourceFiles = findResourceFiles($basePath);

$createdCount = 0;
$replacedCount = 0;

echo "Processing " . count($resourceFiles) . " resource files...\n\n";

foreach ($resourceFiles as $resourceFile) {
    $content = file_get_contents($resourceFile);
    
    // Пропускаем если нет getPages()
    if (strpos($content, 'getPages()') === false) {
        continue;
    }
    
    // Извлекаем namespace и имя класса
    preg_match('/namespace\s+([^;]+);/', $content, $nsMatches);
    preg_match('/class\s+(\w+)\s+/', $content, $classMatches);
    
    $namespace = $nsMatches[1] ?? '';
    $className = $classMatches[1] ?? '';
    
    if (!$namespace || !$className) {
        continue;
    }
    
    // Извлекаем getPages() блок
    preg_match('/public\s+static\s+function\s+getPages\(\)\s*:\s*array\s*\{(.*?)\n\s*\}/s', $content, $pagesMatches);
    
    if (!isset($pagesMatches[1])) {
        continue;
    }
    
    $pagesContent = $pagesMatches[1];
    
    // Ищем все требуемые Pages: 'key' => ClassName::route(...)
    preg_match_all("/'(\w+)'\s*=>\s*(\w+\\\\)?(\w+)::route/", $pagesContent, $matches);
    
    if (empty($matches[3])) {
        continue;
    }
    
    $requiredPages = array_combine($matches[1], $matches[3]);
    
    // Определяем директорию для Pages
    $resourceDir = dirname($resourceFile);
    $pagesDir = $resourceDir . '/Pages';
    
    if (!is_dir($pagesDir)) {
        mkdir($pagesDir, 0755, true);
    }
    
    // Для каждой требуемой Page создаём файл
    foreach ($requiredPages as $routeKey => $pageName) {
        $pageFile = $pagesDir . '/' . $pageName . '.php';
        
        if (file_exists($pageFile)) {
            // Проверяем что файл не пустой и содержит класс
            $fileContent = file_get_contents($pageFile);
            if (strpos($fileContent, "class $pageName") !== false) {
                continue;
            }
            // Если пуст или ошибочный - пересоздаём
            $replacedCount++;
        } else {
            $createdCount++;
        }
        
        // Определяем тип Page по имени или по routeKey
        if ($routeKey === 'index' || strpos($pageName, 'List') === 0) {
            $template = <<<'PHP'
<?php

namespace {NAMESPACE}\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class {PAGE_NAME} extends ListRecords
{
    protected static string $resource = {RESOURCE_CLASS}::class;
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
PHP;
        } elseif ($routeKey === 'create' || strpos($pageName, 'Create') === 0) {
            $template = <<<'PHP'
<?php

namespace {NAMESPACE}\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class {PAGE_NAME} extends CreateRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::channel('audit')->info('Creating record', [
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'tenant_id' => tenant('id'),
        ]);
        
        $data['tenant_id'] = tenant('id');
        return $data;
    }
}
PHP;
        } elseif ($routeKey === 'edit' || strpos($pageName, 'Edit') === 0) {
            $template = <<<'PHP'
<?php

namespace {NAMESPACE}\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class {PAGE_NAME} extends EditRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
PHP;
        } elseif (strpos($pageName, 'View') === 0) {
            $template = <<<'PHP'
<?php

namespace {NAMESPACE}\Pages;

use Filament\Resources\Pages\ViewRecord;

class {PAGE_NAME} extends ViewRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}
PHP;
        } else {
            continue;
        }
        
        // Заменяем placeholders
        $fullResourceClass = $namespace . '\\' . $className;
        $pageNamespace = $namespace . '\\Pages';
        
        $template = str_replace(
            ['{PAGE_NAME}', '{NAMESPACE}', '{RESOURCE_CLASS}'],
            [$pageName, $pageNamespace, $fullResourceClass],
            $template
        );
        
        file_put_contents($pageFile, $template);
        echo "[✓] " . basename($pageFile) . "\n";
    }
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "SUMMARY:\n";
echo "  Created: $createdCount\n";
echo "  Replaced: $replacedCount\n";
echo str_repeat('=', 70) . "\n";
