<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\GroceryAndDelivery;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
    use Filament\Resources\Resource;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\IconColumn, Columns\BooleanColumn, Filters\Filter, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter};
    use Filament\Tables\Actions\{Action, EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;
    use Filament\Infolist\Infolist;
    use Filament\Infolist\Components\{TextEntry, BadgeEntry, Section as InfoSection};

    final class GroceryOrderResource extends Resource
    {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

        protected static ?string $model = GroceryOrder::class;
        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
        protected static ?string $navigationGroup = 'Grocery & Delivery';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Информация о заказе')
                    ->icon('heroicon-m-shopping-cart')
                    ->description('Основные данные заказа')
                    ->schema([
                        TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),

                        Select::make('store_id')
                            ->label('Магазин')
                            ->relationship('store', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),

                        Select::make('user_id')
                            ->label('Покупатель')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),

                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'На ожидании',
                                'confirmed' => 'Подтвержён',
                                'picked' => 'Комплектуется',
                                'in_transit' => 'В пути',
                                'delivered' => 'Доставлен',
                                'cancelled' => 'Отменён',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('order_number')
                            ->label('Номер заказа')
                            ->disabled()
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Адрес и доставка')
                    ->icon('heroicon-m-map-pin')
                    ->description('Информация о доставке')
                    ->schema([
                        TextInput::make('delivery_address')
                            ->label('Адрес доставки')
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('delivery_phone')
                            ->label('Телефон для доставки')
                            ->tel()
                            ->columnSpan(1),

                        TextInput::make('lat')
                            ->label('Широта')
                            ->numeric()
                            ->step(0.0001)
                            ->columnSpan(1),

                        TextInput::make('lon')
                            ->label('Долгота')
                            ->numeric()
                            ->step(0.0001)
                            ->columnSpan(1),

                        TextInput::make('delivery_time_from')
                            ->label('Доставка с (часы)')
                            ->numeric()
                            ->min(0)
                            ->max(23)
                            ->columnSpan(1),

                        TextInput::make('delivery_time_to')
                            ->label('Доставка до (часы)')
                            ->numeric()
                            ->min(0)
                            ->max(23)
                            ->columnSpan(1),

                        Select::make('delivery_partner_id')
                            ->label('Курьер')
                            ->relationship('deliveryPartner', 'id')
                            ->getOptionLabelFromRecordUsing(
                                fn($record) => "#{$record->id} (★ {$record->rating})"
                            )
                            ->searchable()
                            ->columnSpan(2),

                        DateTimePicker::make('estimated_delivery_at')
                            ->label('Ожидаемая доставка')
                            ->native(false)
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Финансы и комиссия')
                    ->icon('heroicon-m-banknote')
                    ->description('Суммы и комиссии')
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Сумма товаров')
                            ->numeric()
                            ->disabled()
                            ->suffix('₽')
                            ->columnSpan(1),

                        TextInput::make('delivery_price')
                            ->label('Стоимость доставки')
                            ->numeric()
                            ->disabled()
                            ->suffix('₽')
                            ->columnSpan(1),

                        TextInput::make('promo_discount')
                            ->label('Скидка по коду')
                            ->numeric()
                            ->disabled()
                            ->suffix('₽')
                            ->columnSpan(1),

                        TextInput::make('total_price')
                            ->label('Итоговая сумма')
                            ->numeric()
                            ->disabled()
                            ->suffix('₽')
                            ->columnSpan(1),

                        TextInput::make('commission_type')
                            ->label('Тип комиссии')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('commission_amount')
                            ->label('Комиссия платформы')
                            ->numeric()
                            ->disabled()
                            ->suffix('₽')
                            ->columnSpan(1),

                        TextInput::make('store_revenue')
                            ->label('Доход магазина')
                            ->numeric()
                            ->disabled()
                            ->suffix('₽')
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Товары в заказе')
                    ->icon('heroicon-m-package')
                    ->description('Список товаров')
                    ->schema([
                        Repeater::make('orderItems')
                            ->label('Товары')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Товар')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(2),

                                TextInput::make('quantity')
                                    ->label('Кол-во')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->columnSpan(1),

                                TextInput::make('price_per_unit')
                                    ->label('Цена/ед.')
                                    ->numeric()
                                    ->disabled()
                                    ->suffix('₽')
                                    ->columnSpan(1),

                                TextInput::make('total_price')
                                    ->label('Итого')
                                    ->numeric()
                                    ->disabled()
                                    ->suffix('₽')
                                    ->columnSpan(1),

                                TextInput::make('notes')
                                    ->label('Примечание')
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->columnSpan('full'),
                    ])->columnSpan('full'),

                Section::make('Оплата и статус')
                    ->icon('heroicon-m-credit-card')
                    ->description('Информация об оплате')
                    ->schema([
                        Select::make('payment_status')
                            ->label('Статус оплаты')
                            ->options([
                                'unpaid' => 'Не оплачено',
                                'paid' => 'Оплачено',
                                'partially_paid' => 'Частично оплачено',
                                'refunded' => 'Возврачено',
                            ])
                            ->columnSpan(2),

                        Select::make('payment_method')
                            ->label('Способ оплаты')
                            ->options([
                                'card' => 'Карта',
                                'wallet' => 'Кошелёк платформы',
                                'cash' => 'Наличные',
                                'online_transfer' => 'Банковский перевод',
                            ])
                            ->columnSpan(2),

                        DateTimePicker::make('paid_at')
                            ->label('Дата оплаты')
                            ->native(false)
                            ->columnSpan(2),

                        TextInput::make('transaction_id')
                            ->label('ID транзакции')
                            ->disabled()
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Примечания и история')
                    ->icon('heroicon-m-chat-bubble-bottom-center-text')
                    ->description('Дополнительная информация')
                    ->schema([
                        RichEditor::make('customer_notes')
                            ->label('Примечание от клиента')
                            ->columnSpan('full'),

                        RichEditor::make('store_notes')
                            ->label('Примечание от магазина')
                            ->columnSpan('full'),

                        RichEditor::make('delivery_notes')
                            ->label('Примечание от курьера')
                            ->columnSpan('full'),
                    ])->columns('full'),

                Section::make('Служебная информация')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Hidden::make('tenant_id')
                            ->default(fn () => tenant('id')),

                        Hidden::make('correlation_id')
                            ->default(fn () => Str::uuid()),

                        Hidden::make('business_group_id')
                            ->default(fn () => filament()->getTenant()?->active_business_group_id),

                        TextInput::make('created_at')
                            ->label('Создан')
                            ->disabled()
                            ->columnSpan(2),

                        TextInput::make('updated_at')
                            ->label('Обновлён')
                            ->disabled()
                            ->columnSpan(2),
                    ])->columns(4),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('order_number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-ticket')
                    ->limit(30),

                TextColumn::make('store.name')
                    ->label('Магазин')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Покупатель')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('delivery_address')
                    ->label('Адрес доставки')
                    ->searchable()
                    ->limit(40),

                BadgeColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'confirmed' => 'Подтвержён',
                        'picked' => 'Комплектуется',
                        'in_transit' => 'В пути',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменён',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'confirmed' => 'info',
                        'picked' => 'primary',
                        'in_transit' => 'cyan',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                BadgeColumn::make('payment_status')
                    ->label('Оплата')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'paid' => 'Оплачено',
                        'partially_paid' => 'Частично',
                        'refunded' => 'Возврачено',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'partially_paid' => 'warning',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('total_price')
                    ->label('Сумма')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),

                TextColumn::make('delivery_price')
                    ->label('Доставка')
                    ->money('RUB', divideBy: 100),

                TextColumn::make('commission_amount')
                    ->label('Комиссия')
                    ->money('RUB', divideBy: 100),

                TextColumn::make('deliveryPartner.rating')
                    ->label('Курьер ★')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) : '—')
                    ->sortable(),

                TextColumn::make('estimated_delivery_at')
                    ->label('Ожидаемая доставка')
                    ->dateTime('d.m H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'На ожидании',
                        'confirmed' => 'Подтвержён',
                        'picked' => 'Комплектуется',
                        'in_transit' => 'В пути',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменён',
                    ])
                    ->multiple(),

                SelectFilter::make('payment_status')
                    ->label('Статус оплаты')
                    ->options([
                        'unpaid' => 'Не оплачено',
                        'paid' => 'Оплачено',
                        'partially_paid' => 'Частично оплачено',
                        'refunded' => 'Возврачено',
                    ])
                    ->multiple(),

                SelectFilter::make('store_id')
                    ->label('Магазин')
                    ->relationship('store', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Filter::make('delivered_only')
                    ->label('Только доставленные')
                    ->query(fn (Builder $query) => $query->where('status', 'delivered')),

                Filter::make('high_value')
                    ->label('Заказы > 5000 ₽')
                    ->query(fn (Builder $query) => $query->where('total_price', '>', 500000)),

                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),

                    Action::make('confirm')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->label('Подтвердить')
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->action(function ($record) {
                            $record->update(['status' => 'confirmed']);
                            $this->logger->info('Grocery order confirmed', [
                                'order_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),

                    Action::make('mark_picked')
                        ->icon('heroicon-m-cube')
                        ->color('info')
                        ->label('Комплектуется')
                        ->visible(fn ($record) => $record->status === 'confirmed')
                        ->action(function ($record) {
                            $record->update(['status' => 'picked']);
                            $this->logger->info('Grocery order marked as picked', [
                                'order_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),

                    Action::make('mark_in_transit')
                        ->icon('heroicon-m-truck')
                        ->color('cyan')
                        ->label('В пути')
                        ->visible(fn ($record) => $record->status === 'picked')
                        ->action(function ($record) {
                            $record->update(['status' => 'in_transit']);
                            $this->logger->info('Grocery order marked as in transit', [
                                'order_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),

                    Action::make('mark_delivered')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->label('Доставлен')
                        ->visible(fn ($record) => $record->status === 'in_transit')
                        ->action(function ($record) {
                            $record->update(['status' => 'delivered']);
                            $this->logger->info('Grocery order marked as delivered', [
                                'order_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),

                    Action::make('cancel')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->label('Отменить')
                        ->visible(fn ($record) => !in_array($record->status, ['delivered', 'cancelled']))
                        ->action(function ($record) {
                            $record->update(['status' => 'cancelled']);
                            $this->logger->info('Grocery order cancelled', [
                                'order_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->successNotification(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $this->logger->info('Grocery order bulk deleted', [
                                    'order_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        }),

                    BulkAction::make('confirm_bulk')
                        ->label('Подтвердить (массово)')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'confirmed']);
                                    $this->logger->info('Grocery order bulk confirmed', [
                                        'order_id' => $record->id,
                                        'user_id' => $this->guard->id(),
                                        'correlation_id' => $record->correlation_id,
                                    ]);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),

                    BulkAction::make('mark_delivered_bulk')
                        ->label('Отметить как доставленные')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if (in_array($record->status, ['in_transit', 'picked'])) {
                                    $record->update(['status' => 'delivered']);
                                    $this->logger->info('Grocery order bulk marked delivered', [
                                        'order_id' => $record->id,
                                        'user_id' => $this->guard->id(),
                                        'correlation_id' => $record->correlation_id,
                                    ]);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),

                    BulkAction::make('cancel_bulk')
                        ->label('Отменить (массово)')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if (!in_array($record->status, ['delivered', 'cancelled'])) {
                                    $record->update(['status' => 'cancelled']);
                                    $this->logger->info('Grocery order bulk cancelled', [
                                        'order_id' => $record->id,
                                        'user_id' => $this->guard->id(),
                                        'correlation_id' => $record->correlation_id,
                                    ]);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
        }
}
