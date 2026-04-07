<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Finances;

use App\Models\BalanceTransaction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class FinancesResource extends Resource
{
    protected static ?string $model = BalanceTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Финансы';
    protected static ?string $navigationLabel = 'Транзакции';
    protected static ?string $modelLabel = 'Транзакция';
    protected static ?string $pluralModelLabel = 'Финансы';
    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Транзакция')
                ->columns(2)
                ->schema([
                    Placeholder::make('wallet_id')
                        ->label('Кошелёк ID')
                        ->content(fn (BalanceTransaction $record): string => (string) $record->wallet_id)
                        ->columnSpan(1),
                    Placeholder::make('type')
                        ->label('Тип')
                        ->content(fn (BalanceTransaction $record): string => $record->type)
                        ->columnSpan(1),
                    Placeholder::make('amount')
                        ->label('Сумма')
                        ->content(fn (BalanceTransaction $record): string => number_format($record->amount / 100, 2) . ' ₽')
                        ->columnSpan(1),
                    Placeholder::make('status')
                        ->label('Статус')
                        ->content(fn (BalanceTransaction $record): string => $record->status)
                        ->columnSpan(1),
                    Placeholder::make('reason')
                        ->label('Причина')
                        ->content(fn (BalanceTransaction $record): string => (string) ($record->reason ?? '—'))
                        ->columnSpan(1),
                    Placeholder::make('source_type')
                        ->label('Источник')
                        ->content(fn (BalanceTransaction $record): string => (string) ($record->source_type ?? '—'))
                        ->columnSpan(1),
                    Placeholder::make('balance_before')
                        ->label('Баланс до')
                        ->content(fn (BalanceTransaction $record): string => number_format(($record->balance_before ?? 0) / 100, 2) . ' ₽')
                        ->columnSpan(1),
                    Placeholder::make('balance_after')
                        ->label('Баланс после')
                        ->content(fn (BalanceTransaction $record): string => number_format(($record->balance_after ?? 0) / 100, 2) . ' ₽')
                        ->columnSpan(1),
                    Placeholder::make('correlation_id')
                        ->label('Correlation ID')
                        ->content(fn (BalanceTransaction $record): string => (string) ($record->correlation_id ?? '—'))
                        ->columnSpan(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'refund'     => 'success',
                        'bonus'      => 'info',
                        'withdrawal' => 'danger',
                        'payout'     => 'danger',
                        'commission' => 'warning',
                        'hold'       => 'gray',
                        default      => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label('Сумма')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2) . ' ₽')
                    ->color(fn (BalanceTransaction $record): string => in_array($record->type, ['deposit', 'refund', 'bonus'])
                        ? 'success'
                        : 'danger'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'failed'    => 'danger',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    }),
                TextColumn::make('reason')
                    ->label('Описание')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('balance_before')
                    ->label('Было')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? number_format($state / 100, 2) . ' ₽' : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('balance_after')
                    ->label('Стало')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? number_format($state / 100, 2) . ' ₽' : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->limit(16)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'deposit'    => 'Пополнение',
                        'withdrawal' => 'Вывод',
                        'commission' => 'Комиссия',
                        'bonus'      => 'Бонус',
                        'refund'     => 'Возврат',
                        'payout'     => 'Выплата',
                        'hold'       => 'Холд',
                        'release'    => 'Снятие холда',
                    ]),
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending'   => 'Ожидает',
                        'completed' => 'Завершена',
                        'failed'    => 'Ошибка',
                        'cancelled' => 'Отменена',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()?->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Finances\Pages\ListFinances::route('/'),
            'view'  => \App\Filament\Tenant\Resources\Finances\Pages\ViewFinances::route('/{record}'),
        ];
    }
}
