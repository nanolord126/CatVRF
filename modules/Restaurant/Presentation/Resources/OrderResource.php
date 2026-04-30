<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources;

use Modules\Restaurant\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * OrderResource — управление заказами ресторана в Filament.
 * CatCRM Standard Color Scheme 2026.
 */
final class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Заказы ресторана';

    protected static ?string $pluralModelLabel = 'Заказы ресторана';

    protected static ?string $modelLabel = 'Заказ ресторана';

    protected static ?string $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о заказе')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Номер заказа')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Select::make('restaurant_id')
                            ->relationship('restaurant', 'name')
                            ->label('Ресторан')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Select::make('table_id')
                            ->relationship('table', 'name')
                            ->label('Столик')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Select::make('type')
                            ->label('Тип заказа')
                            ->options([
                                'dine_in' => 'В зале',
                                'delivery' => 'Доставка',
                                'pickup' => 'Самовывоз',
                            ])
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Статус и оплата')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'pending_payment' => 'Ожидает оплаты',
                                'partially_paid' => 'Частично оплачен',
                                'paid' => 'Полностью оплачен',
                                'in_kitchen' => 'На кухне',
                                'ready' => 'Готов к выдаче',
                                'completed' => 'Завершён',
                                'cancelled' => 'Отменён',
                            ])
                            ->columnSpan(1),
                        Forms\Components\Select::make('payment_status')
                            ->label('Статус оплаты')
                            ->options([
                                'pending' => 'Ожидает',
                                'paid' => 'Оплачен',
                                'refunded' => 'Возвращён',
                            ])
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Финансы')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Сумма товаров')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Итого')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Ресторан')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('table.name')
                    ->label('Столик')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ViewColumn::make('status')
                    ->label('Статус')
                    ->view('components.status-badge')
                    ->viewData(fn ($record): array => [
                        'status' => $record->status,
                        'label' => match ($record->status) {
                            'draft' => 'Черновик',
                            'pending_payment' => 'Ожидает оплаты',
                            'partially_paid' => 'Частично оплачен',
                            'paid' => 'Оплачен',
                            'in_kitchen' => 'На кухне',
                            'ready' => 'Готов к выдаче',
                            'completed' => 'Завершён',
                            'cancelled' => 'Отменён',
                            default => ucfirst($record->status),
                        },
                        'color' => match ($record->status) {
                            'draft' => 'secondary',
                            'pending_payment' => 'warning',
                            'partially_paid' => 'amber',
                            'paid' => 'success',
                            'in_kitchen' => 'info',
                            'ready' => 'emerald',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'secondary',
                        },
                        'icon' => match ($record->status) {
                            'draft' => 'document',
                            'pending_payment' => 'clock',
                            'partially_paid' => 'credit-card',
                            'paid' => 'check-circle',
                            'in_kitchen' => 'fire',
                            'ready' => 'hand-raised',
                            'completed' => 'check-badge',
                            'cancelled' => 'x-circle',
                            default => null,
                        },
                    ]),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Оплата')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Итого')
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2) . ' ₽'),
                Tables\Columns\TextColumn::make('order_time')
                    ->label('Время заказа')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'pending_payment' => 'Ожидает оплаты',
                        'partially_paid' => 'Частично оплачен',
                        'paid' => 'Полностью оплачен',
                        'in_kitchen' => 'На кухне',
                        'ready' => 'Готов к выдаче',
                        'completed' => 'Завершён',
                        'cancelled' => 'Отменён',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Статус оплаты')
                    ->options([
                        'pending' => 'Ожидает',
                        'paid' => 'Оплачен',
                        'refunded' => 'Возвращён',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип заказа')
                    ->options([
                        'dine_in' => 'В зале',
                        'delivery' => 'Доставка',
                        'pickup' => 'Самовывоз',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order_time', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['restaurant', 'table', 'items']);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Restaurant\Presentation\Resources\OrderResource\Pages\ListOrders::route('/'),
            'view' => \Modules\Restaurant\Presentation\Resources\OrderResource\Pages\ViewOrder::route('/{record}'),
        ];
    }
}
