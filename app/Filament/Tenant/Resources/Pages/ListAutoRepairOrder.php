<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\AutoRepairOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListAutoRepairOrder extends ListRecords
{
    protected static string $resource = AutoRepairOrderResource::class;

    public function getTitle(): string
    {
        return 'Заказ-наряды СТО';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Создать заказ-наряд')
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
                TextColumn::make('auto_vehicle_id')
                    ->label('Авто')
                    ->sortable(),
                TextColumn::make('client_id')
                    ->label('Клиент')
                    ->sortable(),
                TextColumn::make('total_cost_kopecks')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),
                TextColumn::make('planned_at')
                    ->label('План')
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('started_at')
                    ->label('Старт')
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('finished_at')
                    ->label('Финиш')
                    ->dateTime()
                    ->toggleable(),
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
                    ->label('Создано')
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
