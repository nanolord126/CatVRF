<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\UserCrmResource\RelationManagers;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'balanceTransactions';

    protected static ?string $title = 'Транзакции кошелька';

    protected static ?string $label = 'Транзакция';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(Builder $q) => $q->where('tenant_id', Filament::getTenant()?->id)
                ->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'success' => 'deposit',
                        'danger'  => 'withdrawal',
                        'warning' => 'commission',
                        'primary' => 'bonus',
                        'info'    => 'refund',
                        'gray'    => 'payout',
                    ]),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(function ($state, $record) {
                        $sign = in_array($record->type, ['deposit', 'bonus', 'refund']) ? '+' : '−';
                        return $sign . number_format(abs($state) / 100, 2, '.', ' ') . ' ₽';
                    })
                    ->color(fn($record) => in_array($record->type ?? '', ['deposit', 'bonus', 'refund'])
                        ? 'success'
                        : 'danger'
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),

                Tables\Columns\TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'deposit'    => 'Пополнение',
                        'withdrawal' => 'Списание',
                        'commission' => 'Комиссия',
                        'bonus'      => 'Бонус',
                        'refund'     => 'Возврат',
                        'payout'     => 'Выплата',
                    ]),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([]);
    }
}
