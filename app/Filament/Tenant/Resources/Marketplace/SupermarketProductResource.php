<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Models\Tenants\RestaurantMenu; // Модель Menu используется для товаров супермаркета
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupermarketProductResource extends Resource
{
    protected static ?string $model = RestaurantMenu::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = '🛒 Grocery & Supermarket';

    protected static ?string $modelLabel = 'Товар супермаркета';

    protected static ?string $pluralModelLabel = 'Каталог супермаркета';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о товаре')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Наименование')
                            ->required(),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU (Артикул)')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('barcode')
                            ->label('Штрих-код'),
                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->options([
                                'dairy' => 'Молочные продукты',
                                'fruit' => 'Фрукты и овощи',
                                'bakery' => 'Хлебобулочные',
                                'frozen' => 'Заморозка',
                            ])
                            ->required(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Складской учет (Inventory Integration)')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Цена (₽)')
                            ->numeric()
                            ->prefix('₽')
                            ->required(),
                        Forms\Components\TextInput::make('stock_quantity')
                            ->label('Остатки на полке')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_perishable')
                            ->label('Скоропортящийся')
                            ->helperText('Требует контроля срока годности'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Наименование')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Остаток')
                    ->badge()
                    ->color(fn ($state) => $state < 10 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_perishable')
                    ->label('Срок годн.')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_perishable')
                    ->label('Только скоропортящиеся'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => SupermarketProductResource\Pages\ListSupermarketProducts::route('/'),
            'create' => SupermarketProductResource\Pages\CreateSupermarketProduct::route('/create'),
            'edit' => SupermarketProductResource\Pages\EditSupermarketProduct::route('/{record}/edit'),
        ];
    }
}
