<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\AppointmentResource\Pages;

use App\Domains\Beauty\Models\Appointment;
use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

/**
 * Страница просмотра одной записи в Tenant Panel.
 *
 * Показывает полную информацию о записи: салон, мастер, услуга,
 * время, статус, комментарии, цену и информацию об отмене.
 */
final class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    TextEntry::make('salon.name')
                        ->label('Салон'),

                    TextEntry::make('master.full_name')
                        ->label('Мастер'),

                    TextEntry::make('service.name')
                        ->label('Услуга'),

                    TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            Appointment::STATUS_PENDING     => 'warning',
                            Appointment::STATUS_CONFIRMED   => 'info',
                            Appointment::STATUS_IN_PROGRESS => 'primary',
                            Appointment::STATUS_COMPLETED   => 'success',
                            Appointment::STATUS_CANCELLED   => 'danger',
                            Appointment::STATUS_NO_SHOW     => 'gray',
                            default                         => 'secondary',
                        }),

                    TextEntry::make('starts_at')
                        ->label('Начало')
                        ->dateTime('d.m.Y H:i'),

                    TextEntry::make('ends_at')
                        ->label('Окончание')
                        ->dateTime('d.m.Y H:i'),

                    TextEntry::make('price_kopecks')
                        ->label('Цена')
                        ->formatStateUsing(fn ($state) => number_format((int) $state / 100, 2, '.', ' ') . ' ₽'),

                    TextEntry::make('final_price_kopecks')
                        ->label('Итоговая цена')
                        ->formatStateUsing(fn ($state) => $state
                            ? number_format((int) $state / 100, 2, '.', ' ') . ' ₽'
                            : '—'),
                ]),

            Section::make('Детали')
                ->columns(2)
                ->schema([
                    TextEntry::make('client_comment')
                        ->label('Комментарий клиента')
                        ->default('—')
                        ->columnSpan(2),

                    TextEntry::make('cancellation_reason')
                        ->label('Причина отмены')
                        ->visible(fn ($record) => $record->status === Appointment::STATUS_CANCELLED)
                        ->columnSpan(2),

                    TextEntry::make('cancellation_penalty_kopecks')
                        ->label('Штраф за отмену')
                        ->visible(fn ($record) => $record->status === Appointment::STATUS_CANCELLED && $record->cancellation_penalty_kopecks > 0)
                        ->formatStateUsing(fn ($state) => number_format((int) $state / 100, 2, '.', ' ') . ' ₽'),
                ]),

            Section::make('Системная информация')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('uuid')
                        ->label('UUID'),

                    TextEntry::make('correlation_id')
                        ->label('Correlation ID'),

                    TextEntry::make('created_at')
                        ->label('Создано')
                        ->dateTime('d.m.Y H:i:s'),

                    TextEntry::make('updated_at')
                        ->label('Обновлено')
                        ->dateTime('d.m.Y H:i:s'),
                ]),
        ]);
    }

    /**
     * Заголовок страницы просмотра записи.
     */
    public function getTitle(): string
    {
        /** @var Appointment $record */
        $record = $this->getRecord();

        return "Запись #{$record->id} — {$record->service?->name}";
    }

    public function getHeaderActions(): array
    {
        return [];
    }
}
