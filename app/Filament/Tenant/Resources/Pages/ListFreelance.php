<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FreelanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ListFreelance extends ListRecords
{
    protected static string $resource = FreelanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый заказ')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('budget_kopecks')
                    ->label('Бюджет')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('commission_kopecks')
                    ->label('Комиссия')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->color('warning')
                    ->alignRight(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'in_progress',
                        'success' => 'completed',
                        'danger'  => 'cancelled',
                        'primary' => 'disputed',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'in_progress' => 'В работе',
                        'completed'   => 'Завершён',
                        'cancelled'   => 'Отменён',
                        'disputed'    => 'Спор',
                        default       => $state,
                    }),
                IconColumn::make('is_b2b')
                    ->label('B2B')
                    ->boolean(),
                TextColumn::make('deadline_at')
                    ->label('Дедлайн')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending'     => 'Ожидает',
                        'in_progress' => 'В работе',
                        'completed'   => 'Завершён',
                        'cancelled'   => 'Отменён',
                        'disputed'    => 'Спор',
                    ]),
                TernaryFilter::make('is_b2b')->label('B2B'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->bulkActions([DeleteBulkAction::make()])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
