<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class NotificationLogResource extends Resource
{
    protected static ?string $model = \Illuminate\Notifications\DatabaseNotification::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Уведомления';
    protected static ?string $navigationLabel = 'История уведомлений';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('notifiable.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => match (true) {
                        str_contains($state, 'BuyerOrderCreated') => 'Заказ создан (покупатель)',
                        str_contains($state, 'SellerNewOrder') => 'Новый заказ (продавец)',
                        str_contains($state, 'OrderConfirmed') => 'Заказ подтверждён',
                        str_contains($state, 'OrderReadyForDelivery') => 'Готов к доставке',
                        str_contains($state, 'OrderInDelivery') => 'В доставке',
                        str_contains($state, 'OrderDelivered') => 'Доставлен',
                        str_contains($state, 'OrderCancelled') => 'Отменён',
                        default => $state,
                    }),

                BadgeColumn::make('data.vertical')
                    ->label('Вертикаль')
                    ->colors([
                        'success' => 'supermarket',
                        'warning' => 'restaurant',
                        'info' => 'taxi',
                        'primary' => 'beauty',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'supermarket' => 'Супермаркет',
                        'restaurant' => 'Ресторан',
                        'taxi' => 'Такси',
                        'beauty' => 'Красота',
                        'medical' => 'Медицина',
                        default => $state,
                    }),

                TextColumn::make('data.title')
                    ->label('Заголовок')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('data.type')
                    ->label('Категория')
                    ->badge()
                    ->colors([
                        'success' => 'order_created',
                        'warning' => 'order_confirmed',
                        'info' => 'order_ready',
                        'primary' => 'order_delivered',
                        'danger' => 'order_cancelled',
                    ]),

                TextColumn::make('read_at')
                    ->label('Прочитано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options([
                        'supermarket' => 'Супермаркет',
                        'restaurant' => 'Ресторан',
                        'taxi' => 'Такси',
                        'beauty' => 'Красота',
                        'medical' => 'Медицина',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value'])) {
                            return $query->whereJsonContains('data->vertical', $data['value']);
                        }
                        return $query;
                    }),

                Tables\Filters\TernaryFilter::make('read')
                    ->label('Статус прочтения')
                    ->placeholder('Все')
                    ->trueLabel('Прочитанные')
                    ->falseLabel('Непрочитанные')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('read_at'),
                        false: fn (Builder $query) => $query->whereNull('read_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => \App\Filament\Resources\NotificationLogResource\Pages\ListNotificationLogs::route('/'),
            'view' => \App\Filament\Resources\NotificationLogResource\Pages\ViewNotificationLog::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role?->isPlatformAdmin() ?? false;
    }
}
