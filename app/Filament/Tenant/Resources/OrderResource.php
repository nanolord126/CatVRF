<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListOrder::route('/'),
                'create' => Pages\\CreateOrder::route('/create'),
                'edit' => Pages\\EditOrder::route('/{record}/edit'),
                'view' => Pages\\ViewOrder::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListOrder::route('/'),
                'create' => Pages\\CreateOrder::route('/create'),
                'edit' => Pages\\EditOrder::route('/{record}/edit'),
                'view' => Pages\\ViewOrder::route('/{record}'),
            ];
        }
}
