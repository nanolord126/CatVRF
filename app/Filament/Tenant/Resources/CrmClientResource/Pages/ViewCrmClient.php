<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmClientResource\Pages;

use App\Filament\Tenant\Resources\CrmClientResource;
use App\Filament\Tenant\Resources\CrmClientResource\Widgets\CrmActivityTimelineWidget;
use App\Filament\Tenant\Resources\CrmClientResource\Widgets\CrmVerticalProfileWidget;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

/**
 * ViewCrmClient — детальный просмотр CRM-клиента в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class ViewCrmClient extends ViewRecord
{
    protected static string $resource = CrmClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->color('danger'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            CrmVerticalProfileWidget::class,
            CrmActivityTimelineWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Tabs::make('client-tabs')->tabs([
                // ── Основная информация ──
                Infolists\Components\Tabs\Tab::make('Основное')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('first_name')
                                ->label('Имя'),
                            Infolists\Components\TextEntry::make('last_name')
                                ->label('Фамилия'),
                            Infolists\Components\TextEntry::make('middle_name')
                                ->label('Отчество'),
                        ]),
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('email')
                                ->label('Email')
                                ->icon('heroicon-o-envelope'),
                            Infolists\Components\TextEntry::make('phone')
                                ->label('Телефон')
                                ->icon('heroicon-o-phone'),
                            Infolists\Components\TextEntry::make('birthday')
                                ->label('День рождения')
                                ->date('d.m.Y'),
                        ]),
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('status')
                                ->label('Статус')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'active' => 'success',
                                    'vip' => 'primary',
                                    'inactive' => 'warning',
                                    'blacklist' => 'danger',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('segment')
                                ->label('Сегмент')
                                ->badge(),
                            Infolists\Components\TextEntry::make('vertical')
                                ->label('Вертикаль')
                                ->badge(),
                            Infolists\Components\TextEntry::make('source')
                                ->label('Источник'),
                        ]),
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('company_name')
                                ->label('Компания'),
                            Infolists\Components\TextEntry::make('company_inn')
                                ->label('ИНН'),
                        ]),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Заметки')
                            ->columnSpanFull(),
                    ]),

                // ── Финансовая сводка ──
                Infolists\Components\Tabs\Tab::make('Финансы')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('total_spent')
                                ->label('Общая сумма')
                                ->money('RUB'),
                            Infolists\Components\TextEntry::make('total_orders')
                                ->label('Кол-во заказов'),
                            Infolists\Components\TextEntry::make('average_order_value')
                                ->label('Средний чек')
                                ->money('RUB'),
                            Infolists\Components\TextEntry::make('loyalty_tier')
                                ->label('Уровень лояльности')
                                ->badge()
                                ->color(fn (?string $state): string => match ($state) {
                                    'vip' => 'primary',
                                    'gold' => 'warning',
                                    'silver' => 'gray',
                                    default => 'info',
                                }),
                        ]),
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('last_order_at')
                                ->label('Последний заказ')
                                ->dateTime('d.m.Y H:i'),
                            Infolists\Components\TextEntry::make('last_interaction_at')
                                ->label('Последнее взаимодействие')
                                ->dateTime('d.m.Y H:i'),
                        ]),
                    ]),

                // ── История взаимодействий ──
                Infolists\Components\Tabs\Tab::make('История')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('interactions')
                            ->label('Взаимодействия')
                            ->schema([
                                Infolists\Components\Grid::make(4)->schema([
                                    Infolists\Components\TextEntry::make('type')
                                        ->label('Тип')
                                        ->badge(),
                                    Infolists\Components\TextEntry::make('channel')
                                        ->label('Канал'),
                                    Infolists\Components\TextEntry::make('direction')
                                        ->label('Направление'),
                                    Infolists\Components\TextEntry::make('interacted_at')
                                        ->label('Дата')
                                        ->dateTime('d.m.Y H:i'),
                                ]),
                                Infolists\Components\TextEntry::make('content')
                                    ->label('Содержание'),
                            ])
                            ->columnSpanFull(),
                    ]),

                // ── Данные вертикали ──
                Infolists\Components\Tabs\Tab::make('Данные вертикали')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('vertical_data')
                            ->label('Данные вертикали')
                            ->columnSpanFull(),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    /**
     * Строковое представление для отладки.
     */
    public function __toString(): string
    {
        return 'ViewCrmClient';
    }
}
