<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\Appointment;
use Illuminate\Support\Collection;

interface AppointmentRepositoryInterface
{
    public function findById(int $id): ?Appointment;

    public function findByVenueId(int $venueId, array $filters = []): Collection;

    public function findByMasterId(int $masterId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): Collection;

    public function findByClientId(int $clientId, int $limit = 20): Collection;

    public function findOverlapping(
        int $masterId,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        ?int $excludeId = null
    ): Collection;

    public function findAvailableSlots(
        int $masterId,
        int $serviceId,
        \DateTimeImmutable $date
    ): Collection;

    public function save(Appointment $appointment): Appointment;

    public function delete(int $id): bool;

    public function updateStatus(int $id, string $status): bool;

    public function updatePaymentStatus(int $id, string $paymentStatus, ?int $paymentId = null): bool;
}
