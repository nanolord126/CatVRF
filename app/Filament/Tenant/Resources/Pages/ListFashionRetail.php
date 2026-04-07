<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FashionRetailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListFashionRetail extends ListRecords
{
    protected static string $resource = FashionRetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый B2B-ритейл заказ')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),
                TextColumn::make('order_number')
                    ->label('Номер заказа')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Номер скопирован')
                    ->fontFamily('mono')
                    ->badge(),
                TextColumn::make('buyer_inn')
                    ->label('ИНН')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('company_contact_person')
                    ->label('Контактное лицо')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('commission_amount')
                    ->label('Комиссия')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
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
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}