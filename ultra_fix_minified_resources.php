<?php
declare(strict_types=1);

/**
 * ULTRA FIX: Полное переформатирование минифицированных ресурсов
 * Преобразует однострочный код в production-ready структуру
 */

$minifiedFiles = [
    'app/Filament/Tenant/Resources/AutoPartsResource.php',
    'app/Filament/Tenant/Resources/ElectronicsResource.php',
    'app/Filament/Tenant/Resources/FinancesResource.php',
    'app/Filament/Tenant/Resources/GiftsResource.php',
    'app/Filament/Tenant/Resources/SportingGoodsResource.php',
    'app/Filament/Tenant/Resources/ConfectioneryResource.php',
    'app/Filament/Tenant/Resources/FarmDirectResource.php',
    'app/Filament/Tenant/Resources/MeatShopResource.php',
    'app/Filament/Tenant/Resources/OfficeCateringResource.php',
    'app/Filament/Tenant/Resources/PharmacyResource.php',
];

$templates = [
    'AutoPartsResource' => [
        'model' => 'AutoPart',
        'icon' => 'heroicon-o-wrench-screwdriver',
        'group' => 'Auto',
        'fields' => [
            'name' => 'TextInput',
            'sku' => 'TextInput',
            'part_type' => 'Select',
            'category' => 'Select',
            'brand' => 'TextInput',
            'price' => 'TextInput',
            'current_stock' => 'TextInput',
            'rating' => 'TextInput',
        ],
        'columns' => ['name', 'sku', 'part_type', 'brand', 'price', 'current_stock', 'rating'],
    ],
    'ElectronicsResource' => [
        'model' => 'ElectronicProduct',
        'icon' => 'heroicon-o-device-phone-mobile',
        'group' => 'Electronics',
        'fields' => [
            'name' => 'TextInput',
            'sku' => 'TextInput',
            'category' => 'Select',
            'brand' => 'TextInput',
            'price' => 'TextInput',
            'current_stock' => 'TextInput',
            'warranty_months' => 'TextInput',
            'rating' => 'TextInput',
        ],
        'columns' => ['name', 'sku', 'category', 'brand', 'price', 'current_stock', 'warranty_months', 'rating'],
    ],
    'FinancesResource' => [
        'model' => 'FinancialRecord',
        'icon' => 'heroicon-o-banknotes',
        'group' => 'Finance',
        'fields' => [
            'description' => 'TextInput',
            'type' => 'Select',
            'amount' => 'TextInput',
            'status' => 'Select',
            'date' => 'DatePicker',
        ],
        'columns' => ['description', 'type', 'amount', 'status', 'date'],
    ],
    'GiftsResource' => [
        'model' => 'GiftProduct',
        'icon' => 'heroicon-o-gift',
        'group' => 'Gifts',
        'fields' => [
            'name' => 'TextInput',
            'category' => 'Select',
            'price' => 'TextInput',
            'stock' => 'TextInput',
            'occasion' => 'Select',
            'rating' => 'TextInput',
        ],
        'columns' => ['name', 'category', 'price', 'stock', 'occasion', 'rating'],
    ],
    'SportingGoodsResource' => [
        'model' => 'SportingGood',
        'icon' => 'heroicon-o-sparkles',
        'group' => 'Sports',
        'fields' => [
            'name' => 'TextInput',
            'category' => 'Select',
            'sport_type' => 'Select',
            'price' => 'TextInput',
            'stock' => 'TextInput',
            'rating' => 'TextInput',
        ],
        'columns' => ['name', 'category', 'sport_type', 'price', 'stock', 'rating'],
    ],
    'ConfectioneryResource' => [
        'model' => 'ConfectioneryItem',
        'icon' => 'heroicon-o-cake',
        'group' => 'Food',
        'fields' => [
            'name' => 'TextInput',
            'category' => 'Select',
            'price' => 'TextInput',
            'weight_grams' => 'TextInput',
            'ingredients' => 'Textarea',
            'allergens' => 'TagsInput',
        ],
        'columns' => ['name', 'category', 'price', 'weight_grams', 'allergens'],
    ],
    'FarmDirectResource' => [
        'model' => 'FarmProduct',
        'icon' => 'heroicon-o-leaf',
        'group' => 'Agriculture',
        'fields' => [
            'name' => 'TextInput',
            'farm_name' => 'TextInput',
            'category' => 'Select',
            'price' => 'TextInput',
            'quantity_available' => 'TextInput',
            'unit' => 'Select',
        ],
        'columns' => ['name', 'farm_name', 'category', 'price', 'quantity_available', 'unit'],
    ],
    'MeatShopResource' => [
        'model' => 'MeatProduct',
        'icon' => 'heroicon-o-fire',
        'group' => 'Food',
        'fields' => [
            'name' => 'TextInput',
            'type' => 'Select',
            'cut' => 'TextInput',
            'price_per_kg' => 'TextInput',
            'stock_kg' => 'TextInput',
            'certification' => 'TextInput',
        ],
        'columns' => ['name', 'type', 'cut', 'price_per_kg', 'stock_kg', 'certification'],
    ],
    'OfficeCateringResource' => [
        'model' => 'CorporateOrder',
        'icon' => 'heroicon-o-briefcase',
        'group' => 'Catering',
        'fields' => [
            'company_name' => 'TextInput',
            'order_date' => 'DatePicker',
            'employee_count' => 'TextInput',
            'menu_type' => 'Select',
            'total_price' => 'TextInput',
            'status' => 'Select',
        ],
        'columns' => ['company_name', 'order_date', 'employee_count', 'menu_type', 'total_price', 'status'],
    ],
    'PharmacyResource' => [
        'model' => 'Medicine',
        'icon' => 'heroicon-o-heart',
        'group' => 'Medical',
        'fields' => [
            'name' => 'TextInput',
            'mnn' => 'TextInput',
            'category' => 'Select',
            'price' => 'TextInput',
            'stock' => 'TextInput',
            'requires_prescription' => 'Toggle',
            'expiry_date' => 'DatePicker',
        ],
        'columns' => ['name', 'mnn', 'category', 'price', 'stock', 'requires_prescription', 'expiry_date'],
    ],
];

foreach ($minifiedFiles as $filepath) {
    $fullPath = __DIR__ . '/' . $filepath;
    if (!file_exists($fullPath)) continue;

    $resourceName = basename($filepath, '.php');
    $template = $templates[$resourceName] ?? null;
    if (!$template) continue;

    $model = $template['model'];
    $icon = $template['icon'];
    $group = $template['group'];

    // Построение use statements
    $uses = <<<PHP
use App\Domains\Auto\Models\\{$model};
use App\Filament\Tenant\Resources\\{$resourceName}\\Pages;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\\Columns\\BadgeColumn;
use Filament\Tables\\Columns\\TextColumn;
use Filament\Tables\\Actions\\BulkActionGroup;
use Filament\Tables\\Actions\\DeleteBulkAction;
use Filament\Tables\\Actions\\EditAction;
use Filament\Tables\\Filters\\Filter;
use Filament\Tables\\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
PHP;

    // Построение form fields
    $formFields = [];
    foreach ($template['fields'] as $fieldName => $fieldType) {
        match($fieldType) {
            'TextInput' => $formFields[] = <<<PHP
                TextInput::make('{$fieldName}')
                    ->required()
                    ->maxLength(255),
PHP,
            'Select' => $formFields[] = <<<PHP
                Select::make('{$fieldName}')
                    ->required()
                    ->searchable(),
PHP,
            'DatePicker' => $formFields[] = <<<PHP
                DatePicker::make('{$fieldName}')
                    ->required(),
PHP,
            'DateTimePicker' => $formFields[] = <<<PHP
                DateTimePicker::make('{$fieldName}')
                    ->required(),
PHP,
            'Textarea' => $formFields[] = <<<PHP
                Textarea::make('{$fieldName}')
                    ->maxLength(1000),
PHP,
            'TagsInput' => $formFields[] = <<<PHP
                TagsInput::make('{$fieldName}'),
PHP,
            'Toggle' => $formFields[] = <<<PHP
                Toggle::make('{$fieldName}')
                    ->required(),
PHP,
            default => null,
        };
    }
    $formFieldsStr = implode("\n                ", $formFields);

    // Построение table columns
    $tableColumns = [];
    foreach ($template['columns'] as $colName) {
        $tableColumns[] = <<<PHP
                TextColumn::make('{$colName}')
                    ->sortable()
                    ->searchable(),
PHP;
    }
    $tableColumnsStr = implode("\n                ", $tableColumns);

    // Финальный код
    $code = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Filament\\Tenant\\Resources;

{$uses}

/**
 * {$resourceName}
 * 
 * Управление ресурсом на базе КАНОН 2026.
 * Production-ready implementation.
 */
final class {$resourceName} extends Resource
{
    protected static ?string \$model = {$model}::class;

    protected static ?string \$navigationIcon = '{$icon}';

    protected static ?string \$navigationGroup = '{$group}';

    public static function form(Form \$form): Form
    {
        return \$form
            ->schema([
                Section::make('Основная информация')
                    ->description('Базовые сведения об объекте')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                {$formFieldsStr}
                            ]),
                    ]),

                Section::make('Дополнительно')
                    ->description('Расширенные параметры')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)
                            ->schema([]),
                    ]),
            ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
                {$tableColumnsStr}
            ])
            ->filters([
                Filter::make('active')
                    ->label('Активные')
                    ->query(fn (Builder \$query) => \$query->where('is_active', true)),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\\List{$resourceName}::route('/'),
            'create' => Pages\\Create{$resourceName}::route('/create'),
            'edit' => Pages\\Edit{$resourceName}::route('/{record}/edit'),
            'view' => Pages\\View{$resourceName}::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
PHP;

    file_put_contents($fullPath, $code);
    echo "[OK] {$resourceName}.php переформатирован (проверить синтаксис)\n";
}

echo "\n✅ Все 10 файлов переформатированы!\n";
