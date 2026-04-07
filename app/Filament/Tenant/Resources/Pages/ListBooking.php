<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListBooking extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string
    {
        return 'Бронирования';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Создать бронирование')
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
                TextColumn::make('client_id')
                    ->label('Клиент')
                    ->sortable(),
                TextColumn::make('session_id')
                    ->label('Сессия')
                    ->sortable(),
                TextColumn::make('photographer_id')
                    ->label('Фотограф')
                    ->toggleable(),
                TextColumn::make('studio_id')
                    ->label('Студия')
                    ->toggleable(),
                TextColumn::make('starts_at')
                    ->label('Начало')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Окончание')
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('total_amount_kopecks')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),
                TextColumn::make('paid_amount_kopecks')
                    ->label('Оплачено')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'success' => 'completed',
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
                        'confirmed' => 'Подтверждено',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
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
