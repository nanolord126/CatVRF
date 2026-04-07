<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FinancesResource;
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

final class ListFinances extends ListRecords
{
    protected static string $resource = FinancesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новая запись')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->copyable()
                    ->fontFamily('mono')
                    ->width('120px'),
                TextColumn::make('wallet_id')
                    ->label('Wallet')
                    ->sortable(),
                BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'success' => 'deposit',
                        'danger'  => 'withdrawal',
                        'warning' => 'commission',
                        'info'    => 'bonus',
                        'primary' => 'refund',
                    ]),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger'  => 'failed',
                    ]),
                TextColumn::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->copyable()
                    ->fontFamily('mono')
                    ->limit(16),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'deposit'    => 'Пополнение',
                        'withdrawal' => 'Списание',
                        'commission' => 'Комиссия',
                        'bonus'      => 'Бонус',
                        'refund'     => 'Возврат',
                    ]),
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending'   => 'Ожидает',
                        'completed' => 'Завершён',
                        'failed'    => 'Ошибка',
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
