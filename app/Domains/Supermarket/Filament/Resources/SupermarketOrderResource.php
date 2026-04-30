<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Domains\Supermarket\Services\InventoryReservationService;
use App\Domains\Supermarket\Services\SubscriptionNotificationService;
use App\Services\Cashback\CashbackService;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Form;

final class SupermarketOrderResource extends Resource
{
    protected static ?string $model = SupermarketOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Заказы';
    protected static ?string $navigationLabel = 'Заказы супермаркета';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('№ Заказа')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('buyer.name')
                    ->label('Покупатель')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Продавец')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sub_vertical')
                    ->label('Подвертикаль')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'meat_shops' => 'Мясные лавки',
                        'vegan_products' => 'Веган',
                        'confectionery' => 'Кондитерка',
                        'farm_direct' => 'Фермерские',
                        'grocery_and_delivery' => 'Бакалея и доставка',
                        'food' => 'Готовая еда',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('delivery_cost')
                    ->label('Доставка')
                    ->money('RUB')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => ['pending', 'confirmed'],
                        'info' => 'ready',
                        'success' => 'delivered',
                        'danger'  => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждён',
                        'ready' => 'Готов к доставке',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменён',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('cold_chain_required')
                    ->label('Холодовая цепь')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждён',
                        'ready' => 'Готов к доставке',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменён',
                    ]),
                Tables\Filters\SelectFilter::make('sub_vertical')
                    ->label('Подвертикаль')
                    ->options([
                        'meat_shops' => 'Мясные лавки',
                        'vegan_products' => 'Веган',
                        'confectionery' => 'Кондитерка',
                        'farm_direct' => 'Фермерские',
                        'grocery_and_delivery' => 'Бакалея и доставка',
                        'food' => 'Готовая еда',
                    ]),
                Tables\Filters\TernaryFilter::make('cold_chain_required')
                    ->label('Холодовая цепь'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('accept')
                    ->label('Принять')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->confirmationMessage('Подтвердить принятие заказа?')
                    ->action(function (SupermarketOrder $record) {
                        $record->update(['status' => 'confirmed']);
                        // Запуск резервирования и уведомления
                        // TODO: Add notification and inventory reservation logic
                    })
                    ->visible(fn ($record) => $record->status === 'pending'),

                Tables\Actions\Action::make('ready_for_delivery')
                    ->label('Готов к доставке')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->requiresConfirmation()
                    ->confirmationMessage('Заказ готов к передаче в доставку?')
                    ->action(function (SupermarketOrder $record) {
                        $record->update(['status' => 'ready']);

                        // Логирование статуса трекинга (интеграция с RealtimeTrackingAdapter будет добавлена позже)
                        Log::info('Order ready for delivery', [
                            'order_id' => $record->id,
                            'status' => 'ready',
                        ]);

                        // TODO: Интеграция с RealtimeTrackingAdapter
                        // app(RealtimeTrackingAdapter::class)->startTracking($record, 'supermarket');
                    })
                    ->visible(fn ($record) => $record->status === 'confirmed'),

                Tables\Actions\Action::make('mark_delivered')
                    ->label('Доставлен')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->confirmationMessage('Подтвердить доставку заказа?')
                    ->action(function (SupermarketOrder $record) {
                        $record->update(['status' => 'delivered']);
                        // TODO: Add delivery completion logic and cashback calculation
                    })
                    ->visible(fn ($record) => $record->status === 'ready'),

                Tables\Actions\Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->confirmationMessage('Вы уверены, что хотите отменить заказ?')
                    ->action(function (SupermarketOrder $record) {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                        ]);
                        // TODO: Add cancellation logic and refund processing
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'confirmed'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('accept_bulk')
                        ->label('Принять выбранные')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (array $records) {
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'confirmed']);
                                }
                            }
                        }),

                    Tables\Actions\BulkAction::make('ready_bulk')
                        ->label('Готовы к доставке')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (array $records) {
                            foreach ($records as $record) {
                                if ($record->status === 'confirmed') {
                                    $record->update(['status' => 'ready']);
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Supermarket\Filament\Resources\SupermarketOrderResource\Pages\ListSupermarketOrders::route('/'),
            'view' => \App\Domains\Supermarket\Filament\Resources\SupermarketOrderResource\Pages\ViewSupermarketOrder::route('/{record}'),
        ];
    }
}
                                   $paymentAdapter->refundPayment($record->payment_id, $amountKopecks, 'order_cancel_' . $record->id);

                                    Log::info('Refund ed for cancelled order', [
                                        'order_id' => $record->d,
                                        'payment_id' => $record->payment_id,
                                        'amount' => $record->total_amount,
                                    ]);
                                }
                            } catch (\Exception $e) {
                                Log::error('Failed to process refud for cancelled order', [
                                    'order_id' => $record->id,
                                    'error' => $e->getMessae(),
                                ]);
                            }
                        }
                        // Запуск трекинга
                        // TODO: Add RealtimeTrackingAdapter integration
                        // app(RealtimeTrackingAdapter::class)->startTracking($record, 'supermarket');
                    })
                    ->visible(fn ($record) => $record->status === 'confirmed'),

                Tables\Actions\Action::make('mark_delivered')
                    ->label('Доставлен')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->confirmationMessage('Подтвердить доставку заказа?')
                    ->action(function (SupermarketOrder $record) {
                        $record->update(['status' => 'delivered']);
                        // TODO: Add delivery completion logic and cashback calculation
                    })
                    ->visible(fn ($record) => $record->status === 'ready'),

                Tables\Actions\Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->confirmationMessage('Вы уверены, что хотите отменить заказ?')
                    ->action(function (SupermarketOrder $record) {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                        ]);
                        // TODO: Add cancellation logic and refund processing
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'confirmed'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('accept_bulk')
                        ->label('Принять выбранные')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (array $records) {
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'confirmed']);
                                }
                            }
                        }),

                    Tables\Actions\BulkAction::make('ready_bulk')
                        ->label('Готовы к доставке')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (array $records) {
                            foreach ($records as $record) {
                                if ($record->status === 'confirmed') {
                                    $record->update(['status' => 'ready']);
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Supermarket\Filament\Resources\SupermarketOrderResource\Pages\ListSupermarketOrders::route('/'),
            'view' => \App\Domains\Supermarket\Filament\Resources\SupermarketOrderResource\Pages\ViewSupermarketOrder::route('/{record}'),
        ];
    }
}
