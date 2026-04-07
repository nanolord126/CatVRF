<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FurnitureCustomOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListFurnitureCustomOrder extends ListRecords
{
    protected static string $resource = FurnitureCustomOrderResource::class;

    public function getTitle(): string
    {
        return 'Индивидуальные заказы';
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
                TextColumn::make('user_id')
                    ->label('Пользователь')
                    ->sortable(),
                TextColumn::make('room_type_id')
                    ->label('Тип комнаты')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 100, 2, '.', ' ') . ' ₽' : '—')
                    ->sortable(),
                IconColumn::make('include_assembly')
                    ->label('Сборка')
                    ->boolean(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'in_progress',
                        'success' => 'completed',
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
                        'in_progress' => 'В работе',
                        'completed' => 'Завершён',
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
