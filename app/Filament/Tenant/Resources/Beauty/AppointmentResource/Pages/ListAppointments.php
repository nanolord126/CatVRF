<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\AppointmentResource\Pages;

use App\Domains\Beauty\Models\Appointment;
use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Страница списка записей в Tenant Panel.
 *
 * Записи создаются только через B2C API, поэтому кнопка «Create» отсутствует.
 * Показываем только записи салонов текущего tenant'а.
 */
final class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    /**
     * Заголовок страницы со счётчиком сегодняшних записей.
     */
    public function getTitle(): string
    {
        $todayCount = Appointment::query()
            ->whereDate('starts_at', today())
            ->whereIn('status', [
                Appointment::STATUS_PENDING,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_IN_PROGRESS,
            ])
            ->count();

        return "Записи (сегодня: {$todayCount})";
    }

    /**
     * Хлебные крошки для навигации.
     */
    public function getBreadcrumbs(): array
    {
        return [
            'Beauty'  => null,
            'Записи'  => route('filament.tenant.resources.beauty.appointments.index'),
        ];
    }

    /**
     * Подсказка на пустом состоянии.
     */
    protected function getEmptyStateHeading(): ?string
    {
        return 'Записей пока нет';
    }

    protected function getEmptyStateDescription(): ?string
    {
        return 'Записи создаются клиентами через публичный API или мобильное приложение.';
    }
}
