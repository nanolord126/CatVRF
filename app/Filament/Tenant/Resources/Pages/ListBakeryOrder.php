<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BakeryOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListBakeryOrder extends ListRecords
{
    protected static string $resource = BakeryOrderResource::class;

    public function getTitle(): string
    {
        return 'Заказы пекарни';
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
                TextColumn::make('client_id')
                    ->label('Клиент')
                    ->sortable(),
                TextColumn::make('product_id')
                    ->label('Товар')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Кол-во')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ' ') . ' ₽')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),
                TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'processing' => 'В обработке',
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
