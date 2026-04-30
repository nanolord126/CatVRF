<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\ReturnResource\Pages;

use App\Domains\Supermarket\Enums\ReturnCondition;
use App\Domains\Supermarket\Enums\ReturnReason;
use App\Domains\Supermarket\Enums\ReturnStatus;
use App\Domains\Supermarket\Filament\Resources\ReturnResource;
use App\Domains\Supermarket\Models\Return as ReturnModel;
use Filament\Actions;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextEntry;
use Filament\Forms\Components\KeyValueEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry as InfolistTextEntry;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;

/**
 * ViewReturn - Страница просмотра возврата.
 */
class ViewReturn extends ViewRecord
{
    protected static string $resource = ReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('approve')
                ->label('Одобрить')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (ReturnModel $record): bool => $record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Одобрить возврат')
                ->modalDescription('Вы уверены, что хотите одобрить этот возврат? Деньги будут возвращены на баланс покупателя.')
                ->action(function (ReturnModel $record) {
                    $service = app(\App\Domains\Supermarket\Services\ReturnService::class);
                    $service->approve($record);
                }),
            Actions\Action::make('reject')
                ->label('Отклонить')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (ReturnModel $record): bool => $record->status === 'pending')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Причина отказа')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (ReturnModel $record, array $data) {
                    $service = app(\App\Domains\Supermarket\Services\ReturnService::class);
                    $service->reject($record, $data['reason']);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Основная информация')
                    ->schema([
                        InfolistTextEntry::make('order_id')
                            ->label('ID заказа')
                            ->url(fn (ReturnModel $record): string => route('filament.admin.resources.supermarket-orders.view', $record->order_id)),

                        InfolistTextEntry::make('buyer.name')
                            ->label('Покупатель'),

                        InfolistTextEntry::make('seller.name')
                            ->label('Продавец')
                            ->default('-'),

                        InfolistTextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'completed' => 'primary',
                                'refunded' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'В ожидании',
                                'approved' => 'Одобрен',
                                'rejected' => 'Отклонен',
                                'completed' => 'Завершен',
                                'refunded' => 'Деньги возвращены',
                                default => $state,
                            }),

                        InfolistTextEntry::make('reason_type')
                            ->label('Причина возврата')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'spoiled' => 'danger',
                                'wrong_item' => 'info',
                                'changed_mind' => 'gray',
                                'damaged' => 'warning',
                                'expired' => 'danger',
                                'other' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'spoiled' => 'Испорчено',
                                'wrong_item' => 'Не тот товар',
                                'changed_mind' => 'Передумал',
                                'damaged' => 'Повреждено',
                                'expired' => 'Просрочено',
                                'other' => 'Другое',
                                default => $state,
                            }),

                        InfolistTextEntry::make('reason_comment')
                            ->label('Комментарий')
                            ->default('-'),
                    ])
                    ->columns(3),

                InfolistSection::make('Финансовая информация')
                    ->schema([
                        InfolistTextEntry::make('total_amount')
                            ->label('Сумма возврата')
                            ->money('RUB'),

                        InfolistTextEntry::make('refund_amount')
                            ->label('Фактическая сумма возврата')
                            ->money('RUB'),

                        IconEntry::make('is_cold_chain')
                            ->label('Холодная цепь')
                            ->boolean()
                            ->trueIcon('heroicon-o-snowflake')
                            ->falseIcon('heroicon-o-x-mark'),

                        InfolistTextEntry::make('return_method')
                            ->label('Способ возврата')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pickup' => 'Самовывоз',
                                'courier' => 'Курьером',
                                'self_delivery' => 'Самостоятельно',
                                default => $state,
                            }),

                        InfolistTextEntry::make('priority')
                            ->label('Приоритет')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'normal' => 'gray',
                                'high' => 'warning',
                                'urgent' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'normal' => 'Обычный',
                                'high' => 'Высокий',
                                'urgent' => 'Срочный',
                                default => $state,
                            }),

                        InfolistTextEntry::make('sub_vertical')
                            ->label('Под-вертикаль')
                            ->default('-'),
                    ])
                    ->columns(3),

                InfolistSection::make('Фото доказательства')
                    ->schema([
                        KeyValueEntry::make('images')
                            ->label('Ссылки на фото')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                InfolistSection::make('Информация об обработке')
                    ->schema([
                        InfolistTextEntry::make('reject_reason')
                            ->label('Причина отказа')
                            ->default('-'),

                        InfolistTextEntry::make('approved_at')
                            ->label('Дата одобрения')
                            ->dateTime()
                            ->default('-'),

                        InfolistTextEntry::make('completed_at')
                            ->label('Дата завершения')
                            ->dateTime()
                            ->default('-'),

                        InfolistTextEntry::make('refunded_at')
                            ->label('Дата возврата денег')
                            ->dateTime()
                            ->default('-'),

                        InfolistTextEntry::make('created_at')
                            ->label('Дата создания')
                            ->dateTime(),

                        InfolistTextEntry::make('updated_at')
                            ->label('Дата обновления')
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                InfolistSection::make('Товары в возврате')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                InfolistTextEntry::make('product.name')
                                    ->label('Товар')
                                    ->default('-'),

                                InfolistTextEntry::make('quantity')
                                    ->label('Количество'),

                                InfolistTextEntry::make('price_per_unit')
                                    ->label('Цена за единицу')
                                    ->money('RUB'),

                                InfolistTextEntry::make('refund_amount')
                                    ->label('Сумма возврата')
                                    ->money('RUB'),

                                InfolistTextEntry::make('condition')
                                    ->label('Состояние')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'good' => 'success',
                                        'spoiled' => 'danger',
                                        'damaged' => 'warning',
                                        'opened' => 'info',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'good' => 'Хорошее',
                                        'spoiled' => 'Испорчено',
                                        'damaged' => 'Повреждено',
                                        'opened' => 'Вскрыто',
                                        default => $state,
                                    }),

                                InfolistTextEntry::make('comment')
                                    ->label('Комментарий')
                                    ->default('-'),
                            ])
                            ->columns(6),
                    ])
                    ->collapsible(),
            ]);
    }
}
