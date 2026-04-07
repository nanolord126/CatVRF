<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Services;

use App\Domains\Beauty\Domain\Entities\Master;
use App\Domains\Beauty\Domain\Entities\Service;
use App\Domains\Beauty\Domain\Repositories\AppointmentRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class SchedulingService
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository,
    ) {
    }

    /**
     * @param Master $master
     * @param Service $service
     * @param CarbonImmutable $date
     * @return Collection<string>
     */
    public function getAvailableSlots(Master $master, Service $service, CarbonImmutable $date): Collection
    {
        $startOfDay = $date->startOfDay();
        $endOfDay = $date->endOfDay();

        $bookedAppointments = $this->appointmentRepository->findByMasterForPeriod($master->getId(), $startOfDay, $endOfDay);

        $bookedSlots = $bookedAppointments->map(fn($appointment) => [
            'start' => $appointment->getStartAt(),
            'end' => $appointment->getEndAt(),
        ])->all();

        $availableSlots = $master->getSchedule()->getAvailableSlotsForDate($date, $service->getDuration(), $bookedSlots);

        return new Collection($availableSlots);
    }

    /**
     * @param Master $master
     * @param Service $service
     * @param CarbonImmutable $dateTime
     * @return bool
     */
    public function isSlotAvailable(Master $master, Service $service, CarbonImmutable $dateTime): bool
    {
        if (!$master->getSchedule()->isOpenAt($dateTime)) {
            return false;
        }

        $availableSlots = $this->getAvailableSlots($master, $service, $dateTime);

        return $availableSlots->contains($dateTime->format('H:i'));
    }

    /**
     * Подсчёт количества свободных слотов за дату.
     *
     * @param Master          $master  Мастер
     * @param Service         $service Услуга
     * @param CarbonImmutable $date    Дата
     * @return int Количество свободных слотов
     */
    public function countAvailableSlots(Master $master, Service $service, CarbonImmutable $date): int
    {
        return $this->getAvailableSlots($master, $service, $date)->count();
    }
}
