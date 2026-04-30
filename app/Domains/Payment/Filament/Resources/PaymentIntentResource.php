<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources;

use App\Domains\Payment\Filament\Resources\PaymentIntentResource\Pages;
use App\Domains\Payment\Models\PaymentIntent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentIntentResource extends Resource
{
    protected static ?string $model = PaymentIntent::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'succeeded' => 'Succeeded',
                                'failed' => 'Failed',
                                'canceled' => 'Canceled',
                                'requires_action' => 'Requires Action',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount_kopecks')
                            ->label('Amount (kopecks)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'RUB' => 'RUB',
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                            ])
                            ->default('RUB')
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'card' => 'Card',
                                'sbp' => 'SBP',
                                'sber_pay' => 'SberPay',
                                'installments' => 'Installments',
                            ])
                            ->required(),
                        Forms\Components\Select::make('provider')
                            ->options([
                                'tinkoff' => 'Tinkoff',
                                'tochka' => 'Tochka',
                                'sber' => 'Sber',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('provider_payment_intent_id')
                            ->label('Provider Payment ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('payment_url')
                            ->label('Payment URL')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('capture_method')
                            ->label('Automatic Capture')
                            ->default(true),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('payable_type')
                            ->label('Payable Type')
                            ->disabled(),
                        Forms\Components\TextInput::make('payable_id')
                            ->label('Payable ID')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Created At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('succeeded_at')
                            ->label('Succeeded At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('canceled_at')
                            ->label('Canceled At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'succeeded' => 'success',
                        'failed' => 'danger',
                        'canceled' => 'warning',
                        'pending' => 'gray',
                        'processing' => 'info',
                        'requires_action' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_kopecks')
                    ->label('Amount (kopecks)')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('currency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('payable_type')
                    ->label('Payable Type')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payable_id')
                    ->label('Payable ID')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('succeeded_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fraud_score')
                    ->label('Fraud Score')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'succeeded' => 'Succeeded',
                        'failed' => 'Failed',
                        'canceled' => 'Canceled',
                        'requires_action' => 'Requires Action',
                    ]),
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'tinkoff' => 'Tinkoff',
                        'tochka' => 'Tochka',
                        'sber' => 'Sber',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'card' => 'Card',
                        'sbp' => 'SBP',
                        'sber_pay' => 'SberPay',
                        'installments' => 'Installments',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'transactions',
            'escrowHolds',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentIntents::route('/'),
            'create' => Pages\CreatePaymentIntent::route('/create'),
            'view' => Pages\ViewPaymentIntent::route('/{record}'),
            'edit' => Pages\EditPaymentIntent::route('/{record}/edit'),
        ];
    }
}
