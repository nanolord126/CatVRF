<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\B2B\Resources;

use App\Domains\Supermarket\Models\Product;
use App\Domains\Supermarket\Models\B2BPriceRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class B2BProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Каталог товаров';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Товары';

    protected static ?string $navigationGroup = 'Каталог';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('₽')
                            ->label('Розничная цена'),
                        Forms\Components\Select::make('category')
                            ->options([
                                'food' => 'Продукты',
                                'grocery' => 'Бакалея',
                                'beverages' => 'Напитки',
                                'confectionery' => 'Кондитерские изделия',
                                'dairy' => 'Молочные продукты',
                                'meat' => 'Мясо и птица',
                                'fish' => 'Рыба и морепродукты',
                                'fruits_vegetables' => 'Фрукты и овощи',
                                'frozen' => 'Замороженные продукты',
                                'household' => 'Товары для дома',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('requires_cold_chain')
                            ->label('Требует холодовой цепи'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Оптовые цены')
                    ->schema([
                        Forms\Components\Repeater::make('b2b_price_rules')
                            ->relationship('priceRules')
                            ->schema([
                                Forms\Components\TextInput::make('min_quantity')
                                    ->required()
                                    ->numeric()
                                    ->label('Мин. количество'),
                                Forms\Components\TextInput::make('price_per_unit')
                                    ->numeric()
                                    ->prefix('₽')
                                    ->label('Оптовая цена (0 = использовать % скидки)'),
                                Forms\Components\TextInput::make('cashback_percent')
                                    ->numeric()
                                    ->suffix('%')
                                    ->label('Кэшбэк')
                                    ->default(0),
                                Forms\Components\DateTimePicker::make('valid_from')
                                    ->label('Действует с'),
                                Forms\Components\DateTimePicker::make('valid_until')
                                    ->label('Действует до'),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Активно')
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->defaultItems(0),
                    ])
                    ->columns(1),
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
                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Розничная цена')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wholesale_price')
                    ->label('Оптовая цена')
                    ->money('RUB')
                    ->getStateUsing(function ($record) {
                        $rule = B2BPriceRule::where('product_id', $record->id)
                            ->where('tenant_id', $record->tenant_id)
                            ->where('min_quantity', '<=', 1)
                            ->where('is_active', true)
                            ->first();
                        return $rule ? $rule->getEffectivePrice($record->price) : $record->price;
                    }),
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
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'food' => 'Продукты',
                        'grocery' => 'Бакалея',
                        'beverages' => 'Напитки',
                        'confectionery' => 'Кондитерские изделия',
                        'dairy' => 'Молочные продукты',
                        'meat' => 'Мясо и птица',
                        'fish' => 'Рыба и морепродукты',
                        'fruits_vegetables' => 'Фрукты и овощи',
                        'frozen' => 'Замороженные продукты',
                        'household' => 'Товары для дома',
                    ]),
                Tables\Filters\TernaryFilter::make('requires_cold_chain')
                    ->label('Требует холодовой цепи'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()?->tenant_id ?? null);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Filament\Resources\Pages\ListRecords::route('/'),
            'create' => \Filament\Resources\Pages\CreateRecord::route('/create'),
            'view' => \Filament\Resources\Pages\ViewRecord::route('/{record}'),
            'edit' => \Filament\Resources\Pages\EditRecord::route('/{record}/edit'),
        ];
    }
}
