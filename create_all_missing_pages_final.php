<?php
declare(strict_types=1);

$resourcesDir = 'app/Filament/Tenant/Resources';
$resourceFiles = glob("$resourcesDir/*Resource.php") ?: [];

echo "🔧 Creating all missing Page files for 100% coverage...\n\n";

$created = 0;
$skipped = 0;
$errors = [];

// Template для каждого Page типа
$listTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace %NAMESPACE%;

use %RESOURCE_CLASS%;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Filament\Tables\Actions\{EditAction, DeleteAction};
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class %CLASS% extends ListRecords
{
    protected static string $resource = %RESOURCE%::class;

    public function getTitle(): string
    {
        return 'List %VERTICAL%';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }
}
PHP;

$createTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace %NAMESPACE%;

use %RESOURCE_CLASS%;
use Filament\Resources\Pages\CreateRecord;

final class %CLASS% extends CreateRecord
{
    protected static string $resource = %RESOURCE%::class;

    public function getTitle(): string
    {
        return 'Create %VERTICAL%';
    }
}
PHP;

$editTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace %NAMESPACE%;

use %RESOURCE_CLASS%;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class %CLASS% extends EditRecord
{
    protected static string $resource = %RESOURCE%::class;

    public function getTitle(): string
    {
        return 'Edit %VERTICAL%';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
PHP;

$viewTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace %NAMESPACE%;

use %RESOURCE_CLASS%;
use Filament\Resources\Pages\ViewRecord;

final class %CLASS% extends ViewRecord
{
    protected static string $resource = %RESOURCE%::class;

    public function getTitle(): string
    {
        return 'View %VERTICAL%';
    }
}
PHP;

foreach ($resourceFiles as $resourceFile) {
    $resourceClass = basename($resourceFile, '.php');
    $vertical = str_replace('Resource', '', $resourceClass);
    
    // Определяем директорию Pages
    $resourceDir = dirname($resourceFile);
    $pagesDir = "$resourceDir/Pages";
    
    // Создаем директорию если ее нет
    if (!is_dir($pagesDir)) {
        mkdir($pagesDir, 0755, true);
    }
    
    $namespace = "App\\Filament\\Tenant\\Resources\\$vertical\\Pages";
    $resourceClassFull = "App\\Filament\\Tenant\\Resources\\$resourceClass";
    
    // 4 Page типа
    $pageTypes = [
        ['file' => "List$vertical.php", 'class' => "List$vertical", 'template' => $listTemplate],
        ['file' => "Create$vertical.php", 'class' => "Create$vertical", 'template' => $createTemplate],
        ['file' => "Edit$vertical.php", 'class' => "Edit$vertical", 'template' => $editTemplate],
        ['file' => "View$vertical.php", 'class' => "View$vertical", 'template' => $viewTemplate],
    ];
    
    foreach ($pageTypes as $type) {
        $filePath = "$pagesDir/{$type['file']}";
        
        if (file_exists($filePath)) {
            $skipped++;
            continue;
        }
        
        $content = str_replace(
            ['%NAMESPACE%', '%CLASS%', '%RESOURCE%', '%RESOURCE_CLASS%', '%VERTICAL%'],
            [$namespace, $type['class'], $resourceClass, "use $resourceClassFull;", $vertical],
            $type['template']
        );
        
        if (file_put_contents($filePath, $content) === false) {
            $errors[] = "Failed to create: $filePath";
            continue;
        }
        
        $created++;
        if ($created % 50 === 0) {
            echo "✅ Created $created pages...\n";
        }
    }
}

echo "\n" . str_repeat("═", 60) . "\n";
echo "✅ Created: $created new pages\n";
echo "⏭️  Skipped (already exist): $skipped\n";
if (!empty($errors)) {
    echo "❌ Errors: " . count($errors) . "\n";
    foreach (array_slice($errors, 0, 5) as $err) {
        echo "   • $err\n";
    }
}
echo str_repeat("═", 60) . "\n";
