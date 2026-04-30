<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\Inventory\Domain\Enums\InventoryCategory;
use Modules\Inventory\Domain\Enums\ItemStatus;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

final class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItemModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Склад';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Товары';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Инвентарь';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Уникальный артикул товара'),

                        Forms\Components\TextInput::make('barcode')
                            ->label('Штрихкод')
                            ->maxLength(255),

                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->options(InventoryCategory::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Components\Select $component, $state) {
                                if ($state && InventoryCategory::from($state)->isControlled()) {
                                    $component->getContainer()->getComponent('is_controlled')?->fill(true);
                                }
                            }),

                        Forms\Components\Toggle::make('is_controlled')
                            ->label('Контролируемый товар')
                            ->helperText('Требует обязательного указания срока годности')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Срок годности')
                    ->schema([
                        Forms\Components\TextInput::make('batch_number')
                            ->label('Номер партии')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('manufacture_date')
                            ->label('Дата производства'),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Срок годности (СОГ)')
                            ->required(fn (callable $get) => $get('is_controlled'))
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Components\DatePicker $component, $state, callable $get, callable $set) {
                                if ($state && $get('manufacture_date')) {
                                    $days = \Carbon\Carbon::parse($get('manufacture_date'))->diffInDays(\Carbon\Carbon::parse($state));
                                    $set('shelf_life_days', $days);
                                }
                            }),

                        Forms\Components\TextInput::make('shelf_life_days')
                            ->label('Срок годности (дней)')
                            ->numeric()
                            ->readOnly(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Количество и цена')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Количество')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\TextInput::make('reserved')
                            ->label('Зарезервировано')
                            ->numeric()
                            ->default(0)
                            ->readOnly(),

                        Forms\Components\TextInput::make('unit')
                            ->label('Единица измерения')
                            ->default('шт')
                            ->required(),

                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Закупочная цена')
                            ->numeric()
                            ->prefix('₽')
                            ->step(0.01),

                        Forms\Components\TextInput::make('selling_price')
                            ->label('Цена продажи')
                            ->numeric()
                            ->prefix('₽')
                            ->step(0.01),

                        Forms\Components\TextInput::make('min_stock_level')
                            ->label('Минимальный остаток')
                            ->numeric()
                            ->helperText('Уведомление при достижении этого уровня'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Хранение')
                    ->schema([
                        Forms\Components\TextInput::make('storage_conditions')
                            ->label('Условия хранения')
                            ->helperText('Холодильник, сухое место и т.д.')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('storage_location')
                            ->label('Место хранения')
                            ->helperText('Холодильник A, полка 3 и т.д.')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options(ItemStatus::class)
                            ->required()
                            ->default('active'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'medication' => 'danger',
                        'feed' => 'warning',
                        'grooming_product' => 'info',
                        'kitchen_product' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable()
                    ->suffix(' ' . fn ($record) => $record->unit),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Срок годности')
                    ->date()
                    ->sortable()
                    ->color(fn ($record): string => match (true) {
                        $record->isExpired() => 'danger',
                        $record->isExpiringSoon(14) => 'warning',
                        $record->isExpiringSoon(30) => 'warning',
                        default => 'success',
                    })
                    ->description(fn ($record): string => $record->getDaysUntilExpiry() !== null
                        ? ($record->getDaysUntilExpiry() < 0 ? 'Просрочен' : "Осталось {$record->getDaysUntilExpiry()} дн.")
                        : ''
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expiring_soon' => 'warning',
                        'expired' => 'danger',
                        'quarantine' => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_controlled')
                    ->label('Контроль')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Цена')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->options(InventoryCategory::class),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(ItemStatus::class),

                Tables\Filters\TernaryFilter::make('is_controlled')
                    ->label('Контролируемые'),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Истекает срок')
                    ->query(fn ($query) => $query->expiringSoon(30)),

                Tables\Filters\Filter::make('expired')
                    ->label('Просроченные')
                    ->query(fn ($query) => $query->expired()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('expiry_date', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryItems::route('/'),
            'create' => Pages\CreateInventoryItem::route('/create'),
            'view' => Pages\ViewInventoryItem::route('/{record}'),
            'edit' => Pages\EditInventoryItem::route('/{record}/edit'),
        ];
    }
}
