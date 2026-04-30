<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources;

use App\Domains\Supermarket\Models\SubscriptionPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class SubscriptionPaymentResource extends Resource
{
    protected static ?string $model = SubscriptionPayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Финансы';
    protected static ?string $navigationLabel = 'Платежи подписок';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о платеже')
                    ->schema([
                        Forms\Components\Select::make('subscription_id')
                            ->relationship('subscription', 'id')
                            ->label('Подписка')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'id')
                            ->label('Заказ')
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма')
                            ->required()
                            ->numeric()
                            ->prefix('₽')
                            ->step(0.01),
                        
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает',
                                'success' => 'Успешно',
                                'failed' => 'Ошибка',
                                'refunded' => 'Возвращен',
                            ])
                            ->required()
                            ->default('pending'),
                        
                        Forms\Components\Select::make('payment_method')
                            ->label('Способ оплаты')
                            ->options([
                                'card' => 'Карта',
                                'sbp' => 'СБП',
                                'invoice' => 'Счёт (B2B)',
                            ]),
                        
                        Forms\Components\TextInput::make('gateway_transaction_id')
                            ->label('ID транзакции шлюза'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Повторные попытки')
                    ->schema([
                        Forms\Components\TextInput::make('attempts')
                            ->label('Количество попыток')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('last_attempt_at')
                            ->label('Последняя попытка')
                            ->seconds(false)
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('next_attempt_at')
                            ->label('Следующая попытка')
                            ->seconds(false)
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('error_message')
                            ->label('Сообщение об ошибке')
                            ->rows(3)
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('subscription.id')
                    ->label('Подписка')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Заказ')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'pending' => 'warning',
                        'success' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                    ]),
                
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Способ оплаты')
                    ->badge(),
                
                Tables\Columns\TextColumn::make('attempts')
                    ->label('Попыток')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'success' => 'Успешно',
                        'failed' => 'Ошибка',
                        'refunded' => 'Возвращен',
                    ]),
                
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Способ оплаты')
                    ->options([
                        'card' => 'Карта',
                        'sbp' => 'СБП',
                        'invoice' => 'Счёт (B2B)',
                    ]),
                
                Tables\Filters\Filter::make('needs_retry')
                    ->label('Требует повтора')
                    ->query(fn ($query) => $query->where('status', 'failed')->where('attempts', '<', 3)),
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

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Supermarket\Filament\Resources\SubscriptionPaymentResource\Pages\ListSubscriptionPayments::route('/'),
            'create' => \App\Domains\Supermarket\Filament\Resources\SubscriptionPaymentResource\Pages\CreateSubscriptionPayment::route('/create'),
            'view' => \App\Domains\Supermarket\Filament\Resources\SubscriptionPaymentResource\Pages\ViewSubscriptionPayment::route('/{record}'),
            'edit' => \App\Domains\Supermarket\Filament\Resources\SubscriptionPaymentResource\Pages\EditSubscriptionPayment::route('/{record}/edit'),
        ];
    }
}
