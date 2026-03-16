<?php

/**
 * Создаёт Pages (List, Create, Edit, View) для всех Filament Resources
 * с правильной структурой и наследованием от базовых классов
 */

$basePath = __DIR__ . '/app/Filament/Tenant/Resources';

// Исключаем файлы в подпапках - берём только корневые Resources
$resourceFiles = glob($basePath . '/*.php');
$resourceNames = [];

foreach ($resourceFiles as $file) {
    $basename = basename($file, '.php');
    // Пропускаем служебные файлы
    if (in_array($basename, ['RelationManagers', 'Common', 'Finance', 'HR', 'CRM', 'Communication', 'B2B', 'RealEstate', 'Marketplace'])) {
        continue;
    }
    $resourceNames[$basename] = $file;
}

$createdCount = 0;
$skippedCount = 0;

foreach ($resourceNames as $resourceName => $resourceFile) {
    $resourceDir = $basePath . '/' . $resourceName;
    $pagesDir = $resourceDir . '/Pages';
    
    // Проверяем существование папки Pages
    if (!is_dir($pagesDir)) {
        mkdir($pagesDir, 0755, true);
    }
    
    // Читаем Resource файл чтобы получить имя модели
    $content = file_get_contents($resourceFile);
    
    // Извлекаем namespace из Resource
    preg_match('/namespace\s+([^;]+);/', $content, $nsMatches);
    $baseNamespace = $nsMatches[1] ?? 'App\\Filament\\Tenant\\Resources';
    
    // Извлекаем имя модели
    preg_match('/protected\s+static\s+\?string\s+\$model\s+=\s+([^;]+)::class;/', $content, $modelMatches);
    $modelClass = $modelMatches[1] ?? null;
    
    // Извлекаем display name (для label'ов)
    $displayName = preg_replace('/Resource$/', '', $resourceName);
    // Преобразуем CamelCase в human readable
    $displayNameFormatted = preg_replace('/([A-Z])/', ' $1', $displayName);
    $displayNameFormatted = trim($displayNameFormatted);
    
    // Создаём List Page
    $listPageContent = <<<'PHP'
<?php

namespace {NAMESPACE}\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class List{RESOURCE_NAME} extends ListRecords
{
    protected static string $resource = {RESOURCE_CLASS}::class;
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Создать')
                ->before(function () {
                    Log::channel('audit')->info('Opening create page for {DISPLAY_NAME}', [
                        'user_id' => \Illuminate\Support\Facades\Auth::id(),
                        'tenant_id' => tenant('id'),
                    ]);
                }),
        ];
    }
    
    protected function getHeaderHeading(): string
    {
        return '{DISPLAY_NAME}';
    }
}
PHP;
    
    $listPageContent = str_replace(
        ['{NAMESPACE}', '{RESOURCE_NAME}', '{RESOURCE_CLASS}', '{DISPLAY_NAME}'],
        [$baseNamespace . '\\Pages', str_replace('Resource', '', $resourceName), $baseNamespace . '\\' . $resourceName, $displayNameFormatted],
        $listPageContent
    );
    
    $listPageFile = $pagesDir . '/List' . str_replace('Resource', '', $resourceName) . '.php';
    
    if (!file_exists($listPageFile)) {
        file_put_contents($listPageFile, $listPageContent);
        $createdCount++;
        echo "[✓] Created: " . basename($listPageFile) . "\n";
    } else {
        $skippedCount++;
    }
    
    // Создаём Create Page
    $createPageContent = <<<'PHP'
<?php

namespace {NAMESPACE}\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class Create{RESOURCE_NAME} extends CreateRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return '{DISPLAY_NAME} успешно создан(а)';
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::channel('audit')->info('Creating {DISPLAY_NAME}', [
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'tenant_id' => tenant('id'),
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);
        
        $data['tenant_id'] = tenant('id');
        return $data;
    }
}
PHP;
    
    $createPageContent = str_replace(
        ['{NAMESPACE}', '{RESOURCE_NAME}', '{RESOURCE_CLASS}', '{DISPLAY_NAME}'],
        [$baseNamespace . '\\Pages', str_replace('Resource', '', $resourceName), $baseNamespace . '\\' . $resourceName, $displayNameFormatted],
        $createPageContent
    );
    
    $createPageFile = $pagesDir . '/Create' . str_replace('Resource', '', $resourceName) . '.php';
    
    if (!file_exists($createPageFile)) {
        file_put_contents($createPageFile, $createPageContent);
        $createdCount++;
        echo "[✓] Created: " . basename($createPageFile) . "\n";
    } else {
        $skippedCount++;
    }
    
    // Создаём Edit Page
    $editPageContent = <<<'PHP'
<?php

namespace {NAMESPACE}\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class Edit{RESOURCE_NAME} extends EditRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make()
                ->label('Просмотр'),
            \Filament\Actions\DeleteAction::make()
                ->label('Удалить')
                ->requiresConfirmation(),
        ];
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return '{DISPLAY_NAME} успешно обновлен(а)';
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        Log::channel('audit')->info('Updating {DISPLAY_NAME}', [
            'record_id' => $this->record->id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'tenant_id' => tenant('id'),
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);
        
        return $data;
    }
}
PHP;
    
    $editPageContent = str_replace(
        ['{NAMESPACE}', '{RESOURCE_NAME}', '{RESOURCE_CLASS}', '{DISPLAY_NAME}'],
        [$baseNamespace . '\\Pages', str_replace('Resource', '', $resourceName), $baseNamespace . '\\' . $resourceName, $displayNameFormatted],
        $editPageContent
    );
    
    $editPageFile = $pagesDir . '/Edit' . str_replace('Resource', '', $resourceName) . '.php';
    
    if (!file_exists($editPageFile)) {
        file_put_contents($editPageFile, $editPageContent);
        $createdCount++;
        echo "[✓] Created: " . basename($editPageFile) . "\n";
    } else {
        $skippedCount++;
    }
    
    // Создаём View Page
    $viewPageContent = <<<'PHP'
<?php

namespace {NAMESPACE}\Pages;

use Filament\Resources\Pages\ViewRecord;

class View{RESOURCE_NAME} extends ViewRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->label('Редактировать'),
        ];
    }
}
PHP;
    
    $viewPageContent = str_replace(
        ['{NAMESPACE}', '{RESOURCE_NAME}', '{RESOURCE_CLASS}'],
        [$baseNamespace . '\\Pages', str_replace('Resource', '', $resourceName), $baseNamespace . '\\' . $resourceName],
        $viewPageContent
    );
    
    $viewPageFile = $pagesDir . '/View' . str_replace('Resource', '', $resourceName) . '.php';
    
    if (!file_exists($viewPageFile)) {
        file_put_contents($viewPageFile, $viewPageContent);
        $createdCount++;
        echo "[✓] Created: " . basename($viewPageFile) . "\n";
    } else {
        $skippedCount++;
    }
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "SUMMARY:\n";
echo "  Created: $createdCount page files\n";
echo "  Skipped: $skippedCount page files (already exist)\n";
echo str_repeat('=', 70) . "\n";
