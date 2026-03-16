<?php
declare(strict_types=1);

$resources = [
    'Construction' => 'App\\Models\\Construction',
    'Repair' => 'App\\Models\\Repair',
    'GardenProduct' => 'App\\Models\\GardenProduct',
    'Flower' => 'App\\Models\\Flower',
    'Restaurant' => 'App\\Models\\Restaurant',
    'TaxiService' => 'App\\Models\\TaxiService',
];

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';

foreach ($resources as $resourceName => $modelFQN) {
    // Determine singular/plural names
    if (str_ends_with($resourceName, 'Product')) {
        $singular = str_replace('Product', '', $resourceName);
        $plural = $resourceName . 's';
    } elseif (str_ends_with($resourceName, 'Service')) {
        $singular = str_replace('Service', '', $resourceName);
        $plural = $resourceName . 's';
    } else {
        $singular = $resourceName;
        $plural = $resourceName . 's';
    }
    
    $modelClass = explode('\\', $modelFQN)[2];
    
    $code = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Marketplace;

use [MODEL_FQN];
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class [RESOURCE_NAME]Resource extends Resource
{
    protected static ?string $model = [MODEL_CLASS]::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $label = '[LABEL]';

    protected static ?string $pluralLabel = '[PLURAL_LABEL]';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Наименование')->required(),
            Forms\Components\Textarea::make('description')->label('Описание'),
            Forms\Components\Select::make('status')
                ->label('Статус')
                ->options(['draft' => 'Черновик', 'active' => 'Активен', 'archived' => 'Архив'])
                ->default('draft'),
            Forms\Components\Toggle::make('is_available')->label('Доступен'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Наименование')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label('Статус')->colors(['danger' => 'draft', 'success' => 'active', 'gray' => 'archived']),
                Tables\Columns\IconColumn::make('is_available')->label('Доступен')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Создан')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Статус')->options(['draft' => 'Черновик', 'active' => 'Активен', 'archived' => 'Архив']),
                Tables\Filters\TernaryFilter::make('is_available')->label('Доступен'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Marketplace\[RESOURCE_NAME]Resource\Pages\List[PLURAL]::route('/'),
            'create' => \App\Filament\Tenant\Resources\Marketplace\[RESOURCE_NAME]Resource\Pages\Create[SINGULAR]::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\Marketplace\[RESOURCE_NAME]Resource\Pages\Edit[SINGULAR]::route('/{record}/edit'),
            'view' => \App\Filament\Tenant\Resources\Marketplace\[RESOURCE_NAME]Resource\Pages\View[SINGULAR]::route('/{record}'),
        ];
    }
}
PHP;

    $code = str_replace('[MODEL_FQN]', $modelFQN, $code);
    $code = str_replace('[RESOURCE_NAME]', $resourceName, $code);
    $code = str_replace('[MODEL_CLASS]', $modelClass, $code);
    $code = str_replace('[SINGULAR]', $singular, $code);
    $code = str_replace('[PLURAL]', $plural, $code);
    $code = str_replace('[LABEL]', $resourceName, $code);
    $code = str_replace('[PLURAL_LABEL]', $plural, $code);
    
    $filePath = "$baseDir/{$resourceName}Resource.php";
    file_put_contents($filePath, $code);
    echo "✅ Created {$resourceName}Resource.php\n";
}

echo "\n✅ All Marketplace Resources created!\n";
