<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FurnitureOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListFurnitureOrder extends ListRecords
{
    protected static string $resource = FurnitureOrderResource::class;

    public function getTitle(): string
    {
        return 'Заказы мебели';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Создать заказ')
                ->icon('heroicon-m-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('item_id')
                    ->label('Товар')
                    ->sortable(),
                TextColumn::make('client_id')
                    ->label('Клиент')
                    ->sortable(),
                TextColumn::make('client_address')
                    ->label('Адрес')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('total_price')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),
                TextColumn::make('delivery_date')
                    ->label('Доставка')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('assembly_date')
                    ->label('Сборка')
                    ->dateTime()
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'primary' => 'assembled',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),
                TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'processing' => 'В обработке',
                        'assembled' => 'Собран',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменён',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
