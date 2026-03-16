<?php
declare(strict_types=1);

// Template for all Marketplace Resources
$resourcesConfig = [
    // Direct Models in App\Models
    ['class' => 'AnimalProductResource', 'model' => 'AnimalProduct', 'icon' => 'heroicon-o-cube', 'label' => 'Животные продукты', 'plural' => 'Животные продукты'],
    ['class' => 'AutoResource', 'model' => 'Automotive', 'icon' => 'heroicon-o-truck', 'label' => 'Автомобили', 'plural' => 'Автомобили'],
    ['class' => 'BeautyProductResource', 'model' => 'BeautyProduct', 'icon' => 'heroicon-o-sparkles', 'label' => 'Косметика', 'plural' => 'Косметика'],
    ['class' => 'ClothingResource', 'model' => 'Clothing', 'icon' => 'heroicon-o-swatch', 'label' => 'Одежда', 'plural' => 'Одежда'],
    ['class' => 'CosmeticsResource', 'model' => 'Cosmetics', 'icon' => 'heroicon-o-sparkles', 'label' => 'Косметика', 'plural' => 'Косметика'],
    ['class' => 'EducationCourseResource', 'model' => 'EducationCourse', 'icon' => 'heroicon-o-academic-cap', 'label' => 'Курсы', 'plural' => 'Курсы'],
    ['class' => 'ElectronicsResource', 'model' => 'Electronics', 'icon' => 'heroicon-o-computer-desktop', 'label' => 'Электроника', 'plural' => 'Электроника'],
    ['class' => 'FootwearResource', 'model' => 'Footwear', 'icon' => 'heroicon-o-shoe', 'label' => 'Обувь', 'plural' => 'Обувь'],
    ['class' => 'FurnitureResource', 'model' => 'Furniture', 'icon' => 'heroicon-o-home', 'label' => 'Мебель', 'plural' => 'Мебель'],
    ['class' => 'GardenProductResource', 'model' => 'GardenProduct', 'icon' => 'heroicon-o-leaf', 'label' => 'Садовые товары', 'plural' => 'Садовые товары'],
    ['class' => 'PerfumeryResource', 'model' => 'Perfumery', 'icon' => 'heroicon-o-spray-can', 'label' => 'Парфюмерия', 'plural' => 'Парфюмерия'],
    ['class' => 'PropertyResource', 'model' => 'Property', 'icon' => 'heroicon-o-home', 'label' => 'Недвижимость', 'plural' => 'Недвижимость'],
    ['class' => 'RepairResource', 'model' => 'Repair', 'icon' => 'heroicon-o-wrench-screwdriver', 'label' => 'Ремонт', 'plural' => 'Ремонт'],
    ['class' => 'SupermarketProductResource', 'model' => 'SupermarketProduct', 'icon' => 'heroicon-o-shopping-cart', 'label' => 'Продукты', 'plural' => 'Продукты'],
    
    // Marketplace Models
    ['class' => 'ConcertResource', 'model' => 'Marketplace\\Concert', 'icon' => 'heroicon-o-ticket', 'label' => 'Концерт', 'plural' => 'Концерты'],
    ['class' => 'ConstructionResource', 'model' => 'Marketplace\\Construction', 'icon' => 'heroicon-o-hammer', 'label' => 'Строительство', 'plural' => 'Строительство'],
    ['class' => 'DanceEventResource', 'model' => 'Marketplace\\DanceEvent', 'icon' => 'heroicon-o-play', 'label' => 'Танцевальный концерт', 'plural' => 'Танцевальные концерты'],
    ['class' => 'FlowerResource', 'model' => 'Marketplace\\Flower', 'icon' => 'heroicon-o-heart', 'label' => 'Цветы', 'plural' => 'Цветы'],
    ['class' => 'HotelResource', 'model' => 'Marketplace\\Hotel', 'icon' => 'heroicon-o-building-office', 'label' => 'Отель', 'plural' => 'Отели'],
    ['class' => 'RestaurantResource', 'model' => 'Marketplace\\Restaurant', 'icon' => 'heroicon-o-fork-knife', 'label' => 'Ресторан', 'plural' => 'Рестораны'],
    
    // Sub-resources
    ['class' => 'RestaurantDishResource', 'model' => 'Marketplace\\RestaurantDish', 'icon' => 'heroicon-o-cup', 'label' => 'Блюдо', 'plural' => 'Блюда'],
    ['class' => 'RestaurantMenuResource', 'model' => 'Marketplace\\RestaurantMenu', 'icon' => 'heroicon-o-book-open', 'label' => 'Меню', 'plural' => 'Меню'],
    ['class' => 'RestaurantOrderResource', 'model' => 'Marketplace\\RestaurantOrder', 'icon' => 'heroicon-o-clipboard-document-list', 'label' => 'Заказ', 'plural' => 'Заказы'],
    ['class' => 'RestaurantTableResource', 'model' => 'Marketplace\\RestaurantTable', 'icon' => 'heroicon-o-table-cells', 'label' => 'Столик', 'plural' => 'Столики'],
    ['class' => 'HotelBookingResource', 'model' => 'Marketplace\\HotelBooking', 'icon' => 'heroicon-o-calendar', 'label' => 'Бронь', 'plural' => 'Брони'],
];

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';

foreach ($resourcesConfig as $config) {
    $resourceFile = $baseDir . '/' . $config['class'] . '.php';
    
    // Skip if not needed
    if (filesize($resourceFile) > 1000) {
        echo "✅ Skipped: {$config['class']}.php (already has content)\n";
        continue;
    }
    
    $modelPath = "App\\Models\\" . $config['model'];
    if (strpos($config['model'], '\\') === false) {
        $modelPath = "App\\Models\\" . $config['model'];
    } else {
        $modelPath = "App\\Models\\" . $config['model'];
    }
    
    $pagesPath = str_replace('Resource.php', 'Resource\\\\Pages', $config['class']);
    
    $template = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Marketplace;

use {MODEL_PATH};
use App\Filament\Tenant\Resources\Marketplace\{CLASS_SHORT}\Pages;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class {CLASS} extends Resource
{
    protected static ?string $model = {MODEL_CLASS}::class;

    protected static ?string $navigationIcon = '{ICON}';

    protected static ?string $navigationLabel = '{LABEL}';

    protected static ?string $modelLabel = '{LABEL}';

    protected static ?string $pluralModelLabel = '{PLURAL}';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label(__('Название'))
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Название'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\List{CLASS_SHORT}::route('/'),
            'create' => Pages\Create{CLASS_SHORT}::route('/create'),
            'view' => Pages\View{CLASS_SHORT}::route('/{record}'),
            'edit' => Pages\Edit{CLASS_SHORT}::route('/{record}/edit'),
        ];
    }

    public static function getTitle(): string
    {
        return '{LABEL}';
    }

    public static function getIcon(): string
    {
        return '{ICON}';
    }
}
PHP;

    $classShort = str_replace('Resource', '', $config['class']);
    $content = str_replace([
        '{CLASS}',
        '{CLASS_SHORT}',
        '{MODEL_PATH}',
        '{MODEL_CLASS}',
        '{ICON}',
        '{LABEL}',
        '{PLURAL}',
    ], [
        $config['class'],
        $classShort,
        $modelPath,
        str_replace('\\', '\\\\', $modelPath),
        $config['icon'],
        $config['label'],
        $config['plural'],
    ], $template);

    file_put_contents($resourceFile, $content);
    echo "✅ Generated: {$config['class']}.php\n";
}

echo "\n✅ All resources generated!\n";
