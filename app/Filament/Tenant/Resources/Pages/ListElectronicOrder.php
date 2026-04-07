<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\ElectronicOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListElectronicOrder extends ListRecords
{
    protected static string $resource = ElectronicOrderResource::class;

    public function getTitle(): string
    {
        return 'Электронные заказы';
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
                TextColumn::make('product_id')
                    ->label('Товар')
                    ->sortable(),
                TextColumn::make('client_id')
                    ->label('Клиент')
                    ->sortable(),
                TextColumn::make('serial_num')
                    ->label('Серийный №')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('imei_num')
                    ->label('IMEI')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('total_price')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),
                TextColumn::make('delivery_date')
                    ->label('Дата доставки')
                    ->dateTime()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'paid',
                        'primary' => 'shipped',
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
                        'paid' => 'Оплачен',
                        'shipped' => 'Отгружен',
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
