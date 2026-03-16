<?php
declare(strict_types=1);

$resources = [
    'Construction' => ['App\\Models\\Construction', 'Строительный материал', 'Строительные материалы', 'heroicon-o-cube-transparent'],
    'Repair' => ['App\\Models\\Repair', 'Услуга ремонта', 'Услуги ремонта', 'heroicon-o-wrench-screwdriver'],
    'GardenProduct' => ['App\\Models\\GardenProduct', 'Садовый товар', 'Садовые товары', 'heroicon-o-leaf'],
    'Flower' => ['App\\Models\\Flower', 'Цветок', 'Цветы', 'heroicon-o-sparkles'],
    'Restaurant' => ['App\\Models\\Restaurant', 'Ресторан', 'Рестораны', 'heroicon-o-fork-knife'],
    'TaxiService' => ['App\\Models\\TaxiService', 'Услуга такси', 'Услуги такси', 'heroicon-o-square-3-stack-3d'],
    'EducationCourse' => ['App\\Models\\EducationCourse', 'Курс обучения', 'Курсы обучения', 'heroicon-o-academic-cap'],
];

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources';

foreach ($resources as $resourceName => $config) {
    list($modelFQN, $label, $pluralLabel, $icon) = $config;
    
    // Determine Page class names
    $singular = preg_replace('/(Product|Service|Course)$/', '', $resourceName);
    
    if ($resourceName === 'GardenProduct') {
        $plural = 'GardenProducts';
    } elseif ($resourceName === 'TaxiService') {
        $plural = 'TaxiServices';
    } elseif ($resourceName === 'EducationCourse') {
        $plural = 'EducationCourses';
    } else {
        $plural = $resourceName . 's';
    }
    
    $classModel = explode('\\', $modelFQN)[2];
    
    $code = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources;

use $modelFQN;
use Filament\\Forms;
use Filament\\Forms\\Form;
use Filament\\Resources\\Resource;
use Filament\\Tables;
use Filament\\Tables\\Table;

final class {$resourceName}Resource extends Resource
{
    protected static ?string \$model = {$classModel}::class;

    protected static ?string \$navigationIcon = '$icon';

    protected static ?string \$label = '$label';

    protected static ?string \$pluralLabel = '$pluralLabel';

    public static function form(Form \$form): Form
    {
        return \$form
            ->schema([
                Forms\\Components\\TextInput::make('name')
                    ->label('Наименование')
                    ->required()
                    ->maxLength(255),
                Forms\\Components\\Textarea::make('description')
                    ->label('Описание')
                    ->maxLength(1000),
                Forms\\Components\\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'active' => 'Активен',
                        'archived' => 'Архив',
                    ])
                    ->default('draft'),
                Forms\\Components\\Toggle::make('is_available')
                    ->label('Доступен'),
            ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
                Tables\\Columns\\TextColumn::make('name')
                    ->label('Наименование')
                    ->searchable()
                    ->sortable(),
                Tables\\Columns\\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'danger' => 'draft',
                        'success' => 'active',
                        'gray' => 'archived',
                    ]),
                Tables\\Columns\\IconColumn::make('is_available')
                    ->label('Доступен')
                    ->boolean(),
                Tables\\Columns\\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\\Filters\\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'active' => 'Активен',
                        'archived' => 'Архив',
                    ]),
                Tables\\Filters\\TernaryFilter::make('is_available')
                    ->label('Доступен'),
            ])
            ->actions([
                Tables\\Actions\\EditAction::make(),
                Tables\\Actions\\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\\Actions\\BulkActionGroup::make([
                    Tables\\Actions\\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \\App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages\\List{$plural}::route('/'),
            'create' => \\App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages\\Create{$singular}::route('/create'),
            'edit' => \\App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages\\Edit{$singular}::route('/{record}/edit'),
            'view' => \\App\\Filament\\Tenant\\Resources\\{$resourceName}Resource\\Pages\\View{$singular}::route('/{record}'),
        ];
    }
}
PHP;

    file_put_contents("$baseDir/{$resourceName}Resource.php", $code);
    echo "✅ Created {$resourceName}Resource.php\n";
}

echo "\n✅ All Resources created successfully!\n";
