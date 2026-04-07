<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FitnessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListFitness extends ListRecords
{
    protected static string $resource = FitnessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый B2B-заказ фитнес')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Номер')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Номер скопирован')
                    ->badge()
                    ->fontFamily('mono'),
                TextColumn::make('company_contact_person')
                    ->label('Контактное лицо')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('company_phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('commission_amount')
                    ->label('Комиссия')
                    ->money('RUB')
                    ->alignRight()
                    ->color('warning'),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning'  => 'pending',
                        'info'     => 'in_review',
                        'success'  => 'approved',
                        'danger'   => 'rejected',
                        'primary'  => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'in_review' => 'На проверке',
                        'approved'  => 'Одобрен',
                        'rejected'  => 'Отклонён',
                        'completed' => 'Выполнен',
                        default     => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending'   => 'Ожидает',
                        'in_review' => 'На проверке',
                        'approved'  => 'Одобрен',
                        'rejected'  => 'Отклонён',
                        'completed' => 'Выполнен',
                    ]),
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