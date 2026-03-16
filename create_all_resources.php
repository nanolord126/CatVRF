<?php
declare(strict_types=1);

$resources = [
    'Construction' => [
        'model' => 'App\Models\Construction',
        'label' => 'Строительный материал',
        'pluralLabel' => 'Строительные материалы',
        'icon' => 'heroicon-o-cube-transparent',
    ],
    'Repair' => [
        'model' => 'App\Models\Repair',
        'label' => 'Услуга ремонта',
        'pluralLabel' => 'Услуги ремонта',
        'icon' => 'heroicon-o-wrench-screwdriver',
    ],
    'GardenProduct' => [
        'model' => 'App\Models\GardenProduct',
        'label' => 'Садовый товар',
        'pluralLabel' => 'Садовые товары',
        'icon' => 'heroicon-o-leaf',
    ],
    'Flower' => [
        'model' => 'App\Models\Flower',
        'label' => 'Цветок',
        'pluralLabel' => 'Цветы',
        'icon' => 'heroicon-o-sparkles',
    ],
    'Restaurant' => [
        'model' => 'App\Models\Restaurant',
        'label' => 'Ресторан',
        'pluralLabel' => 'Рестораны',
        'icon' => 'heroicon-o-fork-knife',
    ],
    'TaxiService' => [
        'model' => 'App\Models\TaxiService',
        'label' => 'Услуга такси',
        'pluralLabel' => 'Услуги такси',
        'icon' => 'heroicon-o-square-3-stack-3d',
    ],
    'EducationCourse' => [
        'model' => 'App\Models\EducationCourse',
        'label' => 'Курс обучения',
        'pluralLabel' => 'Курсы обучения',
        'icon' => 'heroicon-o-academic-cap',
    ],
];

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources';

foreach ($resources as $resourceName => $config) {
    $filePath = "$baseDir/{$resourceName}Resource.php";
    
    $code = <<<'EOT'
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

EOT;

    $code .= "use {$config['model']};\n\n";
    $code .= "class {$resourceName}Resource extends Resource\n";
    $code .= "{\n";
    $code .= "    protected static ?string \$model = {$resourceName}::class;\n\n";
    $code .= "    protected static ?string \$navigationIcon = '{$config['icon']}';\n\n";
    $code .= "    protected static ?string \$label = '{$config['label']}';\n\n";
    $code .= "    protected static ?string \$pluralLabel = '{$config['pluralLabel']}';\n\n";
    
    // Add basic form and table methods
    $code .= <<<'EOT'
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Наименование')->required(),
            Forms\Components\Textarea::make('description')->label('Описание'),
            Forms\Components\Select::make('status')
                ->label('Статус')
                ->options(['draft' => 'Черновик', 'active' => 'Активен', 'archived' => 'Архив'])
                ->default('draft'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Наименование')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Статус')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('Создан')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(['draft' => 'Черновик', 'active' => 'Активен', 'archived' => 'Архив']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

EOT;

    $pluralName = strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $resourceName));
    $singularName = preg_replace('/(Product|Service|Course)$/', '', $pluralName);
    
    $code .= "    public static function getPages(): array\n";
    $code .= "    {\n";
    $code .= "        return [\n";
    $code .= "            'index' => \\App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages\\List" . ucfirst($pluralName) . "::route(),\n";
    $code .= "            'create' => \\App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages\\Create" . ucfirst(str_replace('_', '', $singularName)) . "::route(),\n";
    $code .= "            'edit' => \\App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages\\Edit" . ucfirst(str_replace('_', '', $singularName)) . "::route(),\n";
    $code .= "            'view' => \\App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages\\View" . ucfirst(str_replace('_', '', $singularName)) . "::route(),\n";
    $code .= "        ];\n";
    $code .= "    }\n";
    $code .= "}\n";

    file_put_contents($filePath, $code);
    echo "Created: {$filePath}\n";
}

echo "\n✅ All Resources created!\n";
