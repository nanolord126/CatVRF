<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\UserCrmResource\RelationManagers;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class OrderHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentTransactions';

    protected static ?string $title = 'История заказов';

    protected static ?string $label = 'Заказ';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(Builder $q) => $q->where('tenant_id', Filament::getTenant()?->id)
                ->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'authorized',
                        'success' => 'captured',
                        'danger'  => 'failed',
                        'gray'    => 'refunded',
                    ]),

                Tables\Columns\TextColumn::make('provider_code')
                    ->label('Провайдер')
                    ->placeholder('—'),

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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'authorized' => 'Авторизован',
                        'captured' => 'Оплачен',
                        'refunded' => 'Возврат',
                        'failed' => 'Ошибка',
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
