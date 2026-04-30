<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domains\Supermarket\Models\InventoryItem;
use App\Filament\Resources\InventoryResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class InventoryResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationLabel = 'Склад & Инвентарь';

    protected static ?string $modelLabel = 'Единица инвентаря';

    protected static ?string $pluralModelLabel = 'Инвентарь';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationGroup = 'Supermarket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->required()
                            ->label('Товар'),

                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->label('Количество'),

                        Forms\Components\TextInput::make('reserved')
                            ->numeric()
                            ->default(0)
                            ->label('Зарезервировано'),

                        Forms\Components\TextInput::make('batch_number')
                            ->label('Номер партии'),

                        Forms\Components\TextInput::make('location')
                            ->label('Местоположение'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Срок годности'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Placeholder::make('available_quantity')
                            ->label('Доступно')
                            ->content(fn (InventoryItem $record): string => (string) $record->available_quantity),

                        Forms\Components\Placeholder::make('is_low_stock')
                            ->label('Мало на складе')
                            ->content(fn (InventoryItem $record): string => $record->available_quantity <= 10 ? 'Да' : 'Нет')
                            ->color(fn (InventoryItem $record): string => $record->available_quantity <= 10 ? 'danger' : 'success'),

                        Forms\Components\Placeholder::make('is_expiring_soon')
                            ->label('Скоро истекает срок')
                            ->content(fn (InventoryItem $record): string => $record->expires_at && $record->expires_at <= now()->addDays(7) ? 'Да' : 'Нет')
                            ->color(fn (InventoryItem $record): string => $record->expires_at && $record->expires_at <= now()->addDays(7) ? 'warning' : 'success'),

                        Forms\Components\Placeholder::make('is_expired')
                            ->label('Просрочено')
                            ->content(fn (InventoryItem $record): string => $record->expires_at && $record->expires_at < now() ? 'Да' : 'Нет')
                            ->color(fn (InventoryItem $record): string => $record->expires_at && $record->expires_at < now() ? 'danger' : 'success'),
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

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Товар')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Всего')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reserved')
                    ->label('Зарезервировано')
                    ->sortable(),

                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Доступно')
                    ->sortable()
                    ->color(fn (InventoryItem $record): string => $record->available_quantity <= 10 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Партия')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Местоположение')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Срок годности')
                    ->dateTime()
                    ->sortable()
                    ->color(fn (InventoryItem $record): string => match (true) {
                        $record->expires_at && $record->expires_at < now() => 'danger',
                        $record->expires_at && $record->expires_at <= now()->addDays(7) => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('low_stock')
                    ->query(fn ($query) => $query->lowStock())
                    ->label('Мало на складе'),

                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn ($query) => $query->expiringSoon())
                    ->label('Скоро истекает'),

                Tables\Filters\Filter::make('expired')
                    ->query(fn ($query) => $query->expired())
                    ->label('Просрочено'),

                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->label('Товар'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('update_quantity')
                        ->label('Массовое обновление количества')
                        ->icon('heroicon-o-pencil')
                        ->form([
                            Forms\Components\TextInput::make('quantity_change')
                                ->label('Изменение количества')
                                ->numeric()
                                ->helperText('Положительное число для добавления, отрицательное для вычитания'),
                        ])
                        ->action(function ($records, array $data): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'quantity' => max(0, $record->quantity + $data['quantity_change']),
                                ]);
                            }
                            Notification::make()
                                ->title('Количество обновлено')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('reset_reservations')
                        ->label('Сбросить резервации')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->color('warning')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->update(['reserved' => 0]);
                            }
                            Notification::make()
                                ->title('Резервации сброшены')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'product',
            'reservations',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventory::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'view' => Pages\ViewInventory::route('/{record}'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
