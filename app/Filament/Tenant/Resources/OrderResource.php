<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;


use Psr\Log\LoggerInterface;
use App\Models\Order;
use App\Services\AuditService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * OrderResource — управление заказами в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Только для чтения (просмотр + изменение статусов).
 * Создание заказов — через API и корзину, не через Filament.
 */
final class OrderResource extends Resource
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static ?string $model = Order::class;

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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->copyable()
                    ->limit(12)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('vertical')
                    ->label('Вертикаль')
                    ->badge()
                    ->searchable(),
                TextColumn::make('user_id')
                    ->label('Покупатель ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed'  => 'info',
                        'processing' => 'warning',
                        'shipped'    => 'primary',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        'refunded'   => 'danger',
                        default      => 'gray',
                    }),
                TextColumn::make('total')
                    ->label('Итого')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2) . ' ₽'),
                TextColumn::make('payment_status')
                    ->label('Оплата')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'failed'    => 'danger',
                        'refunded'  => 'warning',
                        default     => 'gray',
                    }),
                TextColumn::make('payment_method')
                    ->label('Способ оплаты')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tracking_number')
                    ->label('Трек-номер')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('is_b2b')
                    ->label('B2B')
                    ->badge()
                    ->color(fn (string $state): string => $state ? 'primary' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state ? 'B2B' : 'B2C'),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус заказа')
                    ->options([
                        'pending'    => 'На рассмотрении',
                        'confirmed'  => 'Подтверждён',
                        'processing' => 'В обработке',
                        'shipped'    => 'Отправлен',
                        'delivered'  => 'Доставлен',
                        'cancelled'  => 'Отменён',
                        'refunded'   => 'Возвращён',
                    ]),
                SelectFilter::make('payment_status')
                    ->label('Статус оплаты')
                    ->options([
                        'pending'   => 'Ожидает',
                        'confirmed' => 'Подтверждён',
                        'failed'    => 'Ошибка',
                        'refunded'  => 'Возвращён',
                    ]),
                SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options([
                        'beauty'    => 'Красота',
                        'food'      => 'Еда',
                        'fashion'   => 'Мода',
                        'furniture' => 'Мебель',
                        'fitness'   => 'Фитнес',
                        'hotel'     => 'Отели',
                        'travel'    => 'Путешествия',
                    ]),
                SelectFilter::make('is_b2b')
                    ->label('Тип клиента')
                    ->options(['1' => 'B2B', '0' => 'B2C']),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('confirmOrder')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        $correlationId = $record->correlation_id ?? \Illuminate\Support\Str::uuid()->toString();
                        $record->update(['status' => 'confirmed']);
                        $this->logger->info('Order confirmed', [
                            'order_id'       => $record->id,
                            'correlation_id' => $correlationId,
                        ]);
                    }),
                Action::make('cancelOrder')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => in_array($record->status, ['pending', 'confirmed']))
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'cancelled']);
                    }),
            ])
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
            'index' => \App\Filament\Tenant\Resources\OrderResource\Pages\ListOrders::route('/'),
            'view'  => \App\Filament\Tenant\Resources\OrderResource\Pages\ViewOrder::route('/{record}'),
        ];
    }
}
