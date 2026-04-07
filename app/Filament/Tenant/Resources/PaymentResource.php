<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\PaymentResource\Pages;
use App\Models\PaymentTransaction;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

final class PaymentResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Финансы';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user_id')->label('Пользователь')->sortable(),
                Tables\Columns\TextColumn::make('payment_method')->label('Метод'),
                Tables\Columns\TextColumn::make('amount')->label('Сумма')->money('RUB', divideBy: 100),
                Tables\Columns\TextColumn::make('currency')->label('Валюта'),
                Tables\Columns\TextColumn::make('correlation_id')->label('Correlation')->searchable()->toggleable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'success' => PaymentTransaction::STATUS_CAPTURED,
                    'warning' => PaymentTransaction::STATUS_PENDING,
                    'danger' => PaymentTransaction::STATUS_FAILED,
                    'secondary' => PaymentTransaction::STATUS_CANCELLED,
                ]),
                Tables\Columns\TextColumn::make('provider_payment_id')->label('Provider ID')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Создан')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    PaymentTransaction::STATUS_PENDING => 'pending',
                    PaymentTransaction::STATUS_AUTHORIZED => 'authorized',
                    PaymentTransaction::STATUS_CAPTURED => 'captured',
                    PaymentTransaction::STATUS_REFUNDED => 'refunded',
                    PaymentTransaction::STATUS_FAILED => 'failed',
                    PaymentTransaction::STATUS_CANCELLED => 'cancelled',
                ]),
                Tables\Filters\SelectFilter::make('payment_method')->options([
                    'card' => 'card',
                    'wallet' => 'wallet',
                    'bank_transfer' => 'bank_transfer',
                    'cash' => 'cash',
                    'crypto' => 'crypto',
                    'invoice' => 'invoice',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Детали платежа')
                ->schema([
                    Forms\Components\TextInput::make('id')->label('ID')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('provider_payment_id')->label('Provider ID')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('payment_method')->label('Метод')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('status')->label('Статус')->disabled()->dehydrated(false),
                    Forms\Components\Placeholder::make('amount_display')
                        ->label('Сумма')
                        ->content(fn (?PaymentTransaction $record): string => $record === null
                            ? '—'
                            : number_format(((int) $record->amount) / 100, 2, '.', ' ') . ' ' . ($record->currency ?? 'RUB')),
                    Forms\Components\TextInput::make('currency')->label('Валюта')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('wallet_id')->label('Wallet ID')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('user_id')->label('User ID')->disabled()->dehydrated(false),
                ])->columns(2),
            Forms\Components\Section::make('Безопасность')
                ->schema([
                    Forms\Components\TextInput::make('correlation_id')->label('Correlation')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('fraud_score')->label('Fraud score')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('ip_address')->label('IP')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('device_fingerprint')->label('Device')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('idempotency_key')->label('Idempotency key')->disabled()->dehydrated(false),
                ])->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(function_exists('tenant') && tenant('id'), static fn (Builder $query) => $query->where('tenant_id', tenant('id')))
            ->latest('created_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
