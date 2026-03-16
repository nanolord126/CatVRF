<?php

/**
 * Анализирует Resource файлы и создаёт Pages с правильными именами
 * на основе того, что требует каждый Resource в getPages()
 */

$basePath = __DIR__ . '/app/Filament/Tenant/Resources';

function findResourceFiles($dir, $prefix = '') {
    $files = [];
    $items = glob($dir . '/*');
    foreach ($items as $item) {
        if (is_file($item) && preg_match('/Resource\.php$/', $item)) {
            $files[] = $item;
        } elseif (is_dir($item) && !in_array(basename($item), ['Pages', 'RelationManagers', 'Widgets'])) {
            $files = array_merge($files, findResourceFiles($item));
        }
    }
    return $files;
}

$resourceFiles = findResourceFiles($basePath);
$createdCount = 0;
$updatedCount = 0;

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
    
    // Извлекаем getPages() блок
    preg_match('/public\s+static\s+function\s+getPages\(\).*?\{(.*?)\n\s*\}/s', $content, $pagesMatches);
    
    if (!isset($pagesMatches[1])) {
        continue;
    }
    
    $pagesContent = $pagesMatches[1];
    
    // Ищем все упоминания Pages классов
    preg_match_all('/Pages\\\\(\w+)::', $pagesContent, $pageMatches);
    $pageNames = $pageMatches[1] ?? [];
    
    if (empty($pageNames)) {
        continue;
    }
    
    // Определяем директорию для Pages
    $resourceDir = dirname($resourceFile);
    $pagesDir = $resourceDir . '/Pages';
    
    if (!is_dir($pagesDir)) {
        mkdir($pagesDir, 0755, true);
    }
    
    // Для каждой требуемой Page проверяем наличие и создаём если нужна
    foreach ($pageNames as $pageName) {
        $pageFile = $pagesDir . '/' . $pageName . '.php';
        
        if (file_exists($pageFile)) {
            continue;
        }
        
        // Определяем тип Page по имени
        $basePageName = preg_replace('/(List|Create|Edit|View)/', '', $pageName);
        
        if (strpos($pageName, 'List') === 0) {
            $content = <<<'PHP'
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
            \Filament\Actions\CreateAction::make()
                ->label('Создать')
                ->before(function () {
                    Log::channel('audit')->info('Opening create page', [
                        'user_id' => \Illuminate\Support\Facades\Auth::id(),
                        'tenant_id' => tenant('id'),
                    ]);
                }),
        ];
    }
}
PHP;
        } elseif (strpos($pageName, 'Create') === 0) {
            $content = <<<'PHP'
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
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);
        
        $data['tenant_id'] = tenant('id');
        return $data;
    }
}
PHP;
        } elseif (strpos($pageName, 'Edit') === 0) {
            $content = <<<'PHP'
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
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        Log::channel('audit')->info('Updating record', [
            'record_id' => $this->record->id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'tenant_id' => tenant('id'),
        ]);
        
        return $data;
    }
}
PHP;
        } elseif (strpos($pageName, 'View') === 0) {
            $content = <<<'PHP'
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
        
        $content = str_replace(
            ['{PAGE_NAME}', '{NAMESPACE}', '{RESOURCE_CLASS}'],
            [$pageName, $pageNamespace, $fullResourceClass],
            $content
        );
        
        file_put_contents($pageFile, $content);
        $createdCount++;
        echo "[✓] Created: " . str_replace($basePath, '', $pageFile) . "\n";
    }
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "SUMMARY:\n";
echo "  Created: $createdCount page files\n";
echo str_repeat('=', 70) . "\n";
