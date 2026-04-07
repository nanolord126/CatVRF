<?php

declare(strict_types=1);

namespace Modules\Payments\Presentation\Filament\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;
use Modules\Payments\Infrastructure\Models\PaymentModel;
use Modules\Payments\Presentation\Filament\Resources\PaymentResource\Pages\ListPayments;
use Modules\Payments\Presentation\Filament\Resources\PaymentResource\Pages\ViewPayment;

/**
 * Filament Resource: Payments.
 */
final class PaymentResource extends Resource
{
    protected static ?string $model           = PaymentModel::class;
    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Платежи';
    protected static ?string $navigationGroup = 'Финансы';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('id')
                ->label('ID платежа')
                ->disabled(),

            TextInput::make('amount')
                ->label('Сумма (копейки)')
                ->numeric()
                ->disabled(),

            Select::make('status')
                ->label('Статус')
                ->options(array_column(PaymentStatus::cases(), 'value', 'value'))
                ->disabled(),

            TextInput::make('provider_payment_id')
                ->label('ID провайдера')
                ->disabled(),

            TextInput::make('correlation_id')
                ->label('Correlation ID')
                ->disabled(),

            DateTimePicker::make('created_at')
                ->label('Создан')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->copyable()
                    ->searchable()
                    ->limit(18),

                TextColumn::make('user_id')
                    ->label('Пользователь')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2) . ' ₽')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'authorized',
                        'success' => 'captured',
                        'danger'  => fn ($state): bool => in_array($state, ['failed', 'expired']),
                        'gray'    => fn ($state): bool => in_array($state, ['refunded', 'cancelled']),
                    ])
                    ->formatStateUsing(
                        fn ($state): string => PaymentStatus::tryFrom($state)?->label() ?? $state
                    ),

                TextColumn::make('provider_payment_id')
                    ->label('ID провайдера')
                    ->copyable()
                    ->limit(20),

                TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->copyable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(
                        collect(PaymentStatus::cases())
                            ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                            ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()?->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'view'  => ViewPayment::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
