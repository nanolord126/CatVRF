<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domains\Supermarket\Models\Product;
use App\Filament\Resources\ProductResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Товары';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Supermarket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->required()
                            ->label('Тенант'),

                        Forms\Components\Select::make('sub_vertical')
                            ->options([
                                'meat_shops' => 'Мясные магазины',
                                'farm_direct' => 'Фермерские продукты',
                                'vegan_products' => 'Веганские продукты',
                                'confectionery' => 'Кондитерские изделия',
                                'grocery_and_delivery' => 'Бакалея и доставка',
                                'food' => 'Еда',
                                'office_catering' => 'Офисный кейтеринг',
                            ])
                            ->required()
                            ->live()
                            ->label('Под-вертикаль'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Название'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->label('Описание'),

                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->suffix('₽')
                            ->label('Цена'),

                        Forms\Components\TextInput::make('weight')
                            ->numeric()
                            ->suffix('кг')
                            ->label('Вес'),

                        Forms\Components\Toggle::make('requires_cold_chain')
                            ->label('Требует холодовой цепи'),

                        Forms\Components\TextInput::make('shelf_life_days')
                            ->numeric()
                            ->label('Срок годности (дней)'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активен'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Атрибуты по под-вертикали')
                    ->schema([
                        Forms\Components\TextInput::make('attributes.meat_type')
                            ->label('Тип мяса')
                            ->visible(fn (Forms\Get $get): bool => $get('sub_vertical') === 'meat_shops'),

                        Forms\Components\TextInput::make('attributes.cut_type')
                            ->label('Тип нарезки')
                            ->visible(fn (Forms\Get $get): bool => $get('sub_vertical') === 'meat_shops'),

                        Forms\Components\TextInput::make('attributes.farm_name')
                            ->label('Название фермы')
                            ->visible(fn (Forms\Get $get): bool => $get('sub_vertical') === 'farm_direct'),

                        Forms\Components\TextInput::make('attributes.certification')
                            ->label('Сертификация')
                            ->visible(fn (Forms\Get $get): bool => $get('sub_vertical') === 'farm_direct'),

                        Forms\Components\TextInput::make('attributes.is_vegan')
                            ->label('Веганский продукт')
                            ->visible(fn (Forms\Get $get): bool => $get('sub_vertical') === 'vegan_products'),

                        Forms\Components\TextInput::make('attributes.allergens')
                            ->label('Аллергены')
                            ->visible(fn (Forms\Get $get): bool => $get('sub_vertical') === 'vegan_products'),

                        Forms\Components\TextInput::make('attributes.sugar_content')
                            ->label('Содержание сахара')
                            ->visible(fn (Forms\Get $get): bool => $get('sub_vertical') === 'confectionery'),

                        Forms\Components\TextInput::make('attributes.filling_type')
                            ->label('Тип начинки')
                            ->visible(fn (Forms\Get $get): bool => $get('sub_vertical') === 'confectionery'),

                        Forms\Components\TextInput::make('attributes.storage_type')
                            ->label('Тип хранения')
                            ->visible(fn (Forms\Get $get): bool => in_array($get('sub_vertical'), ['grocery_and_delivery', 'food'])),

                        Forms\Components\TextInput::make('attributes.serving_size')
                            ->label('Размер порции')
                            ->visible(fn (Forms\Get $get): bool => $get('sub_vertical') === 'office_catering'),

                        Forms\Components\KeyValue::make('attributes')
                            ->label('Дополнительные атрибуты')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->visible(fn (Forms\Get $get): bool => !in_array($get('sub_vertical'), [
                                'meat_shops',
                                'farm_direct',
                                'vegan_products',
                                'confectionery',
                                'grocery_and_delivery',
                                'food',
                                'office_catering',
                            ])),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sub_vertical')
                    ->label('Под-вертикаль')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'meat_shops' => 'danger',
                        'farm_direct' => 'success',
                        'vegan_products' => 'warning',
                        'confectionery' => 'info',
                        'grocery_and_delivery' => 'primary',
                        'food' => 'secondary',
                        'office_catering' => 'purple',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\IconColumn::make('requires_cold_chain')
                    ->label('Холодовая цепь')
                    ->boolean()
                    ->trueIcon('heroicon-o-snowflake')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('sub_vertical')
                    ->options([
                        'meat_shops' => 'Мясные магазины',
                        'farm_direct' => 'Фермерские продукты',
                        'vegan_products' => 'Веганские продукты',
                        'confectionery' => 'Кондитерские изделия',
                        'grocery_and_delivery' => 'Бакалея и доставка',
                        'food' => 'Еда',
                        'office_catering' => 'Офисный кейтеринг',
                    ])
                    ->label('Под-вертикаль'),

                Tables\Filters\TernaryFilter::make('requires_cold_chain')
                    ->label('Холодовая цепь'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'tenant',
            'variants',
            'inventoryItems',
            'images',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
