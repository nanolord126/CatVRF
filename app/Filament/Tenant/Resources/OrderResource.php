<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Bloggers\Models\StreamOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;

final class OrderResource extends Resource
{
    protected static ?string $model = StreamOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Заказы';
    protected static ?string $pluralModelLabel = 'Заказы';
    protected static ?string $modelLabel = 'Заказ';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Информация о заказе')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Номер заказа')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('buyer.name')
                            ->label('Покупатель')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('stream.title')
                            ->label('Трансляция')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('stream.blogger.display_name')
                            ->label('Блогер')
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Товары')
                    ->schema([
                        Repeater::make('items')
                            ->label('Товары в заказе')
                            ->disabled()
                            ->schema([
                                TextInput::make('product_name')
                                    ->label('Название товара')
                                    ->disabled(),
                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->disabled(),
                                TextInput::make('unit_price')
                                    ->label('Цена за единицу (копейки)')
                                    ->numeric()
                                    ->disabled(),
                            ])
                            ->columns(3)
                            ->columnSpan('full'),
                    ]),

                Section::make('Финансы')
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Сумма товаров (копейки)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('shipping_cost')
                            ->label('Доставка (копейки)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('discount_amount')
                            ->label('Скидка (копейки)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('total')
                            ->label('Итого (копейки)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('platform_commission')
                            ->label('Комиссия платформы (копейки)')
                            ->numeric()
                            ->disabled()
                            ->hint('14% от итоговой суммы')
                            ->columnSpan(1),

                        TextInput::make('blogger_earnings')
                            ->label('Заработок блогера (копейки)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Статус платежа')
                    ->schema([
                        Select::make('payment_status')
                            ->label('Статус платежа')
                            ->options([
                                'pending' => 'На рассмотрении',
                                'confirmed' => 'Подтвержден',
                                'failed' => 'Ошибка',
                                'refunded' => 'Возвращен',
                            ])
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('payment_id')
                            ->label('ID платежа')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('paid_at')
                            ->label('Оплачено')
                            ->type('datetime-local')
                            ->disabled()
                            ->columnSpan(1),

                        Select::make('payment_method')
                            ->label('Способ оплаты')
                            ->options([
                                'sbp' => 'СБП',
                                'card' => 'Карта',
                                'wallet' => 'Кошелёк',
                                'crypto' => 'Крипто',
                            ])
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Статус заказа')
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'На рассмотрении',
                                'confirmed' => 'Подтвержден',
                                'processing' => 'В обработке',
                                'shipped' => 'Отправлен',
                                'delivered' => 'Доставлен',
                                'cancelled' => 'Отменен',
                                'refunded' => 'Возвращен',
                            ])
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('tracking_number')
                            ->label('Номер отслеживания')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('created_at')
                            ->label('Дата создания')
                            ->type('datetime-local')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('paid_at')
                            ->label('Дата оплаты')
                            ->type('datetime-local')
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Возврат')
                    ->schema([
                        Select::make('refund_status')
                            ->label('Статус возврата')
                            ->options([
                                'none' => 'Нет возврата',
                                'requested' => 'Запрошен',
                                'processing' => 'В обработке',
                                'completed' => 'Завершен',
                            ])
                            ->columnSpan(1),

                        TextInput::make('refund_amount')
                            ->label('Сумма возврата (копейки)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('refunded_at')
                            ->label('Дата возврата')
                            ->type('datetime-local')
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('buyer.name')
                    ->label('Покупатель')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stream.blogger.display_name')
                    ->label('Блогер')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state) => '₽' . ($state / 100))
                    ->sortable(),

                BadgeColumn::make('payment_status')
                    ->label('Платёж')
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'failed',
                        'info' => 'refunded',
                    ])
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'pending' => 'На рассмотрении',
                        'confirmed' => 'Подтвержден',
                        'failed' => 'Ошибка',
                        'refunded' => 'Возвращен',
                    })
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'confirmed',
                        'warning' => 'processing',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),

                TextColumn::make('blogger_earnings')
                    ->label('Заработок блогера')
                    ->formatStateUsing(fn (int $state) => '₽' . ($state / 100))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Статус платежа')
                    ->options([
                        'pending' => 'На рассмотрении',
                        'confirmed' => 'Подтвержден',
                        'failed' => 'Ошибка',
                        'refunded' => 'Возвращен',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус заказа')
                    ->options([
                        'pending' => 'На рассмотрении',
                        'confirmed' => 'Подтвержден',
                        'processing' => 'В обработке',
                        'shipped' => 'Отправлен',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменен',
                        'refunded' => 'Возвращен',
                    ]),

                Tables\Filters\SelectFilter::make('refund_status')
                    ->label('Возврат')
                    ->options([
                        'none' => 'Нет возврата',
                        'requested' => 'Запрошен',
                        'processing' => 'В обработке',
                        'completed' => 'Завершен',
                    ]),

                Tables\Filters\Filter::make('high_value')
                    ->label('Дорогие заказы (>5k)')
                    ->query(fn ($query) => $query->where('total', '>', 500000)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('refund')
                    ->label('Вернуть')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (StreamOrder $record) {
                        $record->update([
                            'refund_status' => 'processing',
                            'refund_amount' => $record->total,
                        ]);
                    })
                    ->visible(fn (StreamOrder $record) => 
                        $record->payment_status === 'confirmed' && $record->refund_status === 'none'
                    ),
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
            'index' => \App\Filament\Tenant\Resources\OrderResource\Pages\ListOrders::route('/'),
            'view' => \App\Filament\Tenant\Resources\OrderResource\Pages\ViewOrder::route('/{record}'),
        ];
    }
}
