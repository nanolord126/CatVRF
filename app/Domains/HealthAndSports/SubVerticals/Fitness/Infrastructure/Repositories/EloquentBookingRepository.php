<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\Booking;
use Modules\Fitness\Domain\Repositories\BookingRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\BookingModel;

final class EloquentBookingRepository implements BookingRepositoryInterface
{
    public function findById(int $id): ?Booking
    {
        $model = BookingModel::find($id);
        return $model?->toDomain();
    }

    public function findByClientId(int $clientId): array
    {
        return BookingModel::where('client_id', $clientId)
            ->orderByDesc('booked_at')
            ->get()
            ->map(fn (BookingModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByScheduleSlotId(int $scheduleSlotId): array
    {
        return BookingModel::where('schedule_slot_id', $scheduleSlotId)
            ->get()
            ->map(fn (BookingModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByTenantId(int $tenantId): array
    {
        return BookingModel::where('tenant_id', $tenantId)
            ->orderByDesc('booked_at')
            ->get()
            ->map(fn (BookingModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByDateRange(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return BookingModel::where('tenant_id', $tenantId)
            ->whereBetween('booked_at', [$startDate, $endDate])
            ->orderBy('booked_at')
            ->get()
            ->map(fn (BookingModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findUpcomingByClientId(int $clientId, int $limit = 10): array
    {
        return BookingModel::where('client_id', $clientId)
            ->whereHas('scheduleSlot', fn ($q) => $q->where('start_time', '>=', CarbonImmutable::now()))
            ->orderBy('booked_at')
            ->limit($limit)
            ->get()
            ->map(fn (BookingModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findWaitlistBySlot(int $scheduleSlotId): array
    {
        return BookingModel::where('schedule_slot_id', $scheduleSlotId)
            ->where('status', 'waitlist')
            ->orderBy('booked_at')
            ->get()
            ->map(fn (BookingModel $model) => $model->toDomain())
            ->toArray();
    }

    public function save(Booking $booking): Booking
    {
        $model = BookingModel::fromDomain($booking);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        BookingModel::destroy($id);
    }
}
