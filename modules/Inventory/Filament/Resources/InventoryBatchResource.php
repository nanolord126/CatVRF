<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Domain\Enums\BatchStatus;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;

final class InventoryBatchResource extends Resource
{
    protected static ?string $model = InventoryBatchModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Партии';

    protected static ?string $modelLabel = 'Партия';

    protected static ?string $pluralModelLabel = 'Партии';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Инвентарь';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о партии')
                    ->schema([
                        Forms\Components\Select::make('inventory_item_id')
                            ->label('Товар')
                            ->relationship('inventoryItem', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Название')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\TextInput::make('batch_number')
                            ->label('Номер партии')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Уникальный номер партии от поставщика'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Срок годности')
                    ->schema([
                        Forms\Components\DatePicker::make('manufacture_date')
                            ->label('Дата производства')
                            ->required(),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Срок годности (СОГ)')
                            ->required()
                            ->helperText('Главное поле для FIFO - партии с ранним сроком списываются первыми'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Количество и цена')
                    ->schema([
                        Forms\Components\TextInput::make('initial_quantity')
                            ->label('Начальное количество')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\TextInput::make('current_quantity')
                            ->label('Текущее количество')
                            ->numeric()
                            ->required()
                            ->minValue(0),

                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Закупочная цена')
                            ->numeric()
                            ->prefix('₽')
                            ->step(0.01),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Хранение')
                    ->schema([
                        Forms\Components\TextInput::make('storage_location')
                            ->label('Место хранения')
                            ->helperText('Холодильник A, полка 3 и т.д.')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options(BatchStatus::class)
                            ->required()
                            ->default('active')
                            ->helperText('Статус автоматически обновляется на основе срока годности'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inventoryItem.name')
                    ->label('Товар')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Номер партии')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('manufacture_date')
                    ->label('Дата производства')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        ? ($record->getDaysUntilExpiry() < 0
                            ? 'Просрочен'
                            : ($record->getDaysUntilExpiry() === 0
                                ? 'Истекает сегодня'
                                : "Осталось {$record->getDaysUntilExpiry()} дн."))
                        : ''
                    ),

                Tables\Columns\TextColumn::make('current_quantity')
                    ->label('Текущее количество')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record): string => match (true) {
                        $record->current_quantity === 0 => 'gray',
                        $record->current_quantity < $record->initial_quantity * 0.2 => 'danger',
                        $record->current_quantity < $record->initial_quantity * 0.5 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('initial_quantity')
                    ->label('Начальное количество')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Закупочная цена')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expiring_soon' => 'warning',
                        'expired' => 'danger',
                        'quarantine' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('storage_location')
                    ->label('Место хранения')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(BatchStatus::class),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Истекает срок (14 дней)')
                    ->query(fn ($query) => $query->expiringSoon(14)),

                Tables\Filters\Filter::make('expired')
                    ->label('Просроченные')
                    ->query(fn ($query) => $query->expired()),

                Tables\Filters\Filter::make('has_stock')
                    ->label('В наличии')
                    ->query(fn ($query) => $query->where('current_quantity', '>', 0)),
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
            'index' => Pages\ListInventoryBatches::route('/'),
            'create' => Pages\CreateInventoryBatch::route('/create'),
            'view' => Pages\ViewInventoryBatch::route('/{record}'),
            'edit' => Pages\EditInventoryBatch::route('/{record}/edit'),
        ];
    }
}
