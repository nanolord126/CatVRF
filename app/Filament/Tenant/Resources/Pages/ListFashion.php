<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FashionResource;
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
use Illuminate\Support\Str;

final class ListFashion extends ListRecords
{
    protected static string $resource = FashionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый B2B-заказ')
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
                TextColumn::make('buyer_inn')
                    ->label('ИНН покупателя')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('ИНН скопирован')
                    ->fontFamily('mono'),
                TextColumn::make('fashionStore.name')
                    ->label('Магазин')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable()
                    ->alignRight(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning'  => 'pending',
                        'success'  => 'approved',
                        'danger'   => 'rejected',
                        'primary'  => 'completed',
                    ])
                    ->icons([
                        'heroicon-o-clock'       => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle'    => 'rejected',
                        'heroicon-o-archive-box' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'approved'  => 'Одобрен',
                        'rejected'  => 'Отклонён',
                        'completed' => 'Выполнен',
                        default     => $state,
                    }),
                TextColumn::make('items_json')
                    ->label('Позиций')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' поз.' : '—')
                    ->alignCenter(),
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