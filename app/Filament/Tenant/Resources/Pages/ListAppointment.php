<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\AppointmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListAppointment extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string
    {
        return 'Записи';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Создать запись')
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
                TextColumn::make('salon_id')
                    ->label('Салон')
                    ->sortable(),
                TextColumn::make('master_id')
                    ->label('Мастер')
                    ->sortable(),
                TextColumn::make('service_id')
                    ->label('Услуга')
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label('Клиент')
                    ->sortable(),
                TextColumn::make('datetime_start')
                    ->label('Начало')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('datetime_end')
                    ->label('Окончание')
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),
                BadgeColumn::make('payment_status')
                    ->label('Оплата')
                    ->colors([
                        'warning' => 'unpaid',
                        'success' => 'paid',
                        'danger' => 'refunded',
                    ])
                    ->toggleable(),
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
                        'confirmed' => 'Подтверждена',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                    ]),
                SelectFilter::make('payment_status')
                    ->label('Оплата')
                    ->options([
                        'unpaid' => 'Не оплачено',
                        'paid' => 'Оплачено',
                        'refunded' => 'Возврат',
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
