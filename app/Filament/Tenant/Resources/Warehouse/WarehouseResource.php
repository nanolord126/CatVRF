<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Warehouse;

use App\Models\InventoryItem;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class WarehouseResource extends Resource
{
    protected static ?string $model = InventoryItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Склад';
    protected static ?string $navigationLabel = 'Остатки';
    protected static ?string $modelLabel = 'Позиция склада';
    protected static ?string $pluralModelLabel = 'Остатки склада';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('tenant_id')
                ->default(fn (): int|string => filament()->getTenant()?->id),
            Hidden::make('correlation_id')
                ->default(fn (): string => Str::uuid()->toString()),
            Hidden::make('uuid')
                ->default(fn (): string => Str::uuid()->toString()),

            Section::make('Товар')
                ->columns(2)
                ->schema([
                    TextInput::make('sku')
                        ->label('Артикул (SKU)')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(1),
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    TextInput::make('product_id')
                        ->label('ID товара')
                        ->numeric()
                        ->nullable()
                        ->columnSpan(1),
                    TagsInput::make('tags')
                        ->label('Теги')
                        ->nullable()
                        ->columnSpan(1),
                ]),

            Section::make('Остатки')
                ->columns(3)
                ->schema([
                    TextInput::make('current_stock')
                        ->label('Текущий остаток')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->columnSpan(1),
                    TextInput::make('hold_stock')
                        ->label('В резерве')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->disabled()
                        ->columnSpan(1),
                    TextInput::make('min_stock_threshold')
                        ->label('Минимальный порог')
                        ->helperText('Алерт при достижении')
                        ->numeric()
                        ->minValue(0)
                        ->default(5)
                        ->columnSpan(1),
                    TextInput::make('max_stock_threshold')
                        ->label('Максимальный порог')
                        ->numeric()
                        ->minValue(0)
                        ->nullable()
                        ->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('Артикул')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->limit(35),
                TextColumn::make('current_stock')
                    ->label('Остаток')
                    ->sortable()
                    ->color(fn (InventoryItem $record): string => match (true) {
                        $record->current_stock <= 0                        => 'danger',
                        $record->current_stock <= $record->min_stock_threshold => 'warning',
                        default                                             => 'success',
                    }),
                TextColumn::make('hold_stock')
                    ->label('Резерв')
                    ->sortable()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('available_stock')
                    ->label('Свободно')
                    ->getStateUsing(fn (InventoryItem $record): int => max(0, $record->current_stock - $record->hold_stock))
                    ->color(fn (InventoryItem $record): string => ($record->current_stock - $record->hold_stock) <= 0 ? 'danger' : 'success'),
                TextColumn::make('min_stock_threshold')
                    ->label('Мин. порог')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_checked_at')
                    ->label('Последняя инвентаризация')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Не проводилась')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Мало на складе')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('current_stock', '<=', 'min_stock_threshold')),
                Filter::make('out_of_stock')
                    ->label('Нет в наличии')
                    ->query(fn (Builder $query): Builder => $query->where('current_stock', '<=', 0)),
                Filter::make('has_reserve')
                    ->label('Есть резервы')
                    ->query(fn (Builder $query): Builder => $query->where('hold_stock', '>', 0)),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('adjustStock')
                    ->label('Скорректировать')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        TextInput::make('adjustment')
                            ->label('Корректировка (+ или -)')
                            ->helperText('Например: +10 или -5')
                            ->required()
                            ->numeric(),
                        TextInput::make('reason')
                            ->label('Причина')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (InventoryItem $record, array $data): void {
                        $delta = (int) $data['adjustment'];
                        $newStock = max(0, $record->current_stock + $delta);
                        $record->update([
                            'current_stock' => $newStock,
                            'last_checked_at' => now(),
                        ]);
                    }),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('name');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->where('tenant_id', filament()->getTenant()?->id);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Tenant\Resources\Warehouse\WarehouseResource\Pages\ListWarehouses::route('/'),
            'create' => \App\Filament\Tenant\Resources\Warehouse\WarehouseResource\Pages\CreateWarehouse::route('/create'),
            'view'   => \App\Filament\Tenant\Resources\Warehouse\WarehouseResource\Pages\ViewWarehouse::route('/{record}'),
            'edit'   => \App\Filament\Tenant\Resources\Warehouse\WarehouseResource\Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
