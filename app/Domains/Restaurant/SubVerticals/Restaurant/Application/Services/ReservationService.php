<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Restaurant\Domain\Entities\TableReservation;
use Modules\Restaurant\Domain\Enums\ReservationStatus;
use Modules\Restaurant\Infrastructure\Models\TableReservationModel;
use Modules\Restaurant\Infrastructure\Models\RestaurantTableModel;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

finause WithAuditLogging;

    l class ReservationService
{d,
        private readonly AuditService $auitService
    public function __construct(
        private readonly string $tenantId,
    ) {}

    public function createReservation(
        int $tableId,
        string $customerName,
        string $customerPhone,
        string $customerEmail,
        int $guestCount,
        CarbonImmutable $reservationTime,
        ?int $userId = null,
        ?string $specialRequests = null,
    ): TableReservation {
        // Проверяем, что стол существует и доступен
        $table = RestaurantTableModel::where('tenant_id', $this->tenantId)
            ->where('id', $tableId)
            ->where('is_active', true)
            ->firstOrFail();

        if ($guestCount > $table->capacity) {
            throw new \InvalidArgumentException("Guest count exceeds table capacity");
        }

        // Проверяем пересечения с другими бронированиями
        $existingReservation = TableReservationModel::where('table_id', $tableId)
            ->whereIn('status', ['pending', 'confirmed', 'arrived'])
            ->whereBetween('reservation_time', [
                $reservationTime->copy()->subHours(2),
                $reservationTime->copy()->addHours(4),
            ])
            ->exists();

        if ($existingReservation) {
            throw new \RuntimeException("Table is already reserved for this time slot");
        }

        $reservation = TableReservation::create(
            tenantId: (int) $this->tenantId,
            tableId: $tableId,
            customerName: $customerName,
            customerPhone: $customerPhone,
            customerEmail: $customerEmail,
            guestCount: $guestCount,
            reservationTime: $reservationTime,
            userId: $userId,
            specialRequests: $specialRequests,
        );

        $model = TableReservationModel::create([
            'tenant_id' => $reservation->tenantId,
            'table_id' => $reservation->tableId,
            'user_id' => $reservation->userId,
            'customer_name' => $reservation->customerName,
            'customer_phone' => $reservation->customerPhone,
            'customer_email' => $reservation->customerEmail,
            'guest_count' => $reservation->guestCount,
            'reservation_time' => $reservation->reservationTime,
            'status' => $reservation->status->value,
            'special_requests' => $reservation->specialRequests,
        ]);

        // AUDIT LOG
        $this->logCreated(
            entityType: 'restaurant_reservation',
            entityId: $model->id,
            context: [
                'table_id' => $tableId,
                'customer_name' => $customerName,
                'guest_count' => $guestCount,
                'reservation_time' => $reservationTime->toIso8601String(),
            ],
            userId: $userId,
            tenantId: (int) $this->tenantId
        );

        return new TableReservation(
            id: $model->id,
            tenantId: $model->tenant_id,
            tableId: $model->table_id,
            userId: $model->user_id,
            customerName: $model->customer_name,
            customerPhone: $model->customer_phone,
            customerEmail: $model->customer_email,
            guestCount: $model->guest_count,
            reservationTime: $model->reservation_time->toImmutable(),
            arrivalTime: $model->arrival_time?->toImmutable(),
            completedAt: $model->completed_at?->toImmutable(),
            cancelledAt: $model->cancelled_at?->toImmutable(),
            status: ReservationStatus::from($model->status),
            specialRequests: $model->special_requests,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function confirmReservation(int $reservationId): TableReservation
    {
        $model = TableReservationModel::where('tenant_id', $this->tenantId)
            ->where('id', $reservationId)
            ->firstOrFail();

        $model->status = 'confirmed';
        $model->save();

        return new TableReservation(
            id: $model->id,
            tenantId: $model->tenant_id,
            tableId: $model->table_id,
            userId: $model->user_id,
            customerName: $model->customer_name,
            customerPhone: $model->customer_phone,
            customerEmail: $model->customer_email,
            guestCount: $model->guest_count,
            reservationTime: $model->reservation_time->toImmutable(),
            arrivalTime: $model->arrival_time?->toImmutable(),
            completedAt: $model->completed_at?->toImmutable(),
            cancelledAt: $model->cancelled_at?->toImmutable(),
            status: ReservationStatus::from($model->status),
            specialRequests: $model->special_requests,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function markAsArrived(int $reservationId): TableReservation
    {
        $model = TableReservationModel::where('tenant_id', $this->tenantId)
            ->where('id', $reservationId)
            ->firstOrFail();

        $model->status = 'arrived';
        $model->arrival_time = now();
        $model->save();

        return new TableReservation(
            id: $model->id,
            tenantId: $model->tenant_id,
            tableId: $model->table_id,
            userId: $model->user_id,
            customerName: $model->customer_name,
            customerPhone: $model->customer_phone,
            customerEmail: $model->customer_email,
            guestCount: $model->guest_count,
            reservationTime: $model->reservation_time->toImmutable(),
            arrivalTime: $model->arrival_time->toImmutable(),
            completedAt: $model->completed_at?->toImmutable(),
            cancelledAt: $model->cancelled_at?->toImmutable(),
            status: ReservationStatus::from($model->status),
            specialRequests: $model->special_requests,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function completeReservation(int $reservationId): TableReservation
    {
        $model = TableReservationModel::where('tenant_id', $this->tenantId)
            ->where('id', $reservationId)
            ->firstOrFail();

        $model->status = 'completed';
        $model->completed_at = now();
        $model->save();

        return new TableReservation(
            id: $model->id,
            tenantId: $model->tenant_id,
            tableId: $model->table_id,
            userId: $model->user_id,
            customerName: $model->customer_name,
            customerPhone: $model->customer_phone,
            customerEmail: $model->customer_email,
            guestCount: $model->guest_count,
            reservationTime: $model->reservation_time->toImmutable(),
            arrivalTime: $model->arrival_time?->toImmutable(),
            completedAt: $model->completed_at->toImmutable(),
            cancelledAt: $model->cancelled_at?->toImmutable(),
            status: ReservationStatus::from($model->status),
            specialRequests: $model->special_requests,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function cancelReservation(int $reservationId, ?string $reason = null): TableReservation
    {
        $model = TableReservationModel::where('tenant_id', $this->tenantId)
            ->where('id', $reservationId)
            ->firstOrFail();

        $model->status = 'cancelled';
        $model->cancelled_at = now();
        if ($reason !== null) {
            $model->special_requests = $reason;
        }
        $model->save();

        return new TableReservation(
            id: $model->id,
            tenantId: $model->tenant_id,
            tableId: $model->table_id,
            userId: $model->user_id,
            customerName: $model->customer_name,
            customerPhone: $model->customer_phone,
            customerEmail: $model->customer_email,
            guestCount: $model->guest_count,
            reservationTime: $model->reservation_time->toImmutable(),
            arrivalTime: $model->arrival_time?->toImmutable(),
            completedAt: $model->completed_at?->toImmutable(),
            cancelledAt: $model->cancelled_at->toImmutable(),
            status: ReservationStatus::from($model->status),
            specialRequests: $model->special_requests,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function markAsNoShow(int $reservationId): TableReservation
    {
        return $this->cancelReservation($reservationId, 'No show');
    }

    public function getReservation(int $reservationId): ?TableReservation
    {
        return TableReservationModel::where('tenant_id', $this->tenantId)
            ->where('id', $reservationId)
            ->first()
            ?->transform(fn ($model) => new TableReservation(
                id: $model->id,
                tenantId: $model->tenant_id,
                tableId: $model->table_id,
                userId: $model->user_id,
                customerName: $model->customer_name,
                customerPhone: $model->customer_phone,
                customerEmail: $model->customer_email,
                guestCount: $model->guest_count,
                reservationTime: $model->reservation_time->toImmutable(),
                arrivalTime: $model->arrival_time?->toImmutable(),
                completedAt: $model->completed_at?->toImmutable(),
                cancelledAt: $model->cancelled_at?->toImmutable(),
                status: ReservationStatus::from($model->status),
                specialRequests: $model->special_requests,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    public function getReservationsForTable(int $tableId, ?CarbonImmutable $startDate = null, ?CarbonImmutable $endDate = null): Collection
    {
        $query = TableReservationModel::where('tenant_id', $this->tenantId)
            ->where('table_id', $tableId)
            ->with('table');

        if ($startDate !== null) {
            $query->where('reservation_time', '>=', $startDate);
        }
        if ($endDate !== null) {
            $query->where('reservation_time', '<=', $endDate);
        }

        return $query->orderBy('reservation_time')
            ->get()
            ->map(fn ($model) => new TableReservation(
                id: $model->id,
                tenantId: $model->tenant_id,
                tableId: $model->table_id,
                userId: $model->user_id,
                customerName: $model->customer_name,
                customerPhone: $model->customer_phone,
                customerEmail: $model->customer_email,
                guestCount: $model->guest_count,
                reservationTime: $model->reservation_time->toImmutable(),
                arrivalTime: $model->arrival_time?->toImmutable(),
                completedAt: $model->completed_at?->toImmutable(),
                cancelledAt: $model->cancelled_at?->toImmutable(),
                status: ReservationStatus::from($model->status),
                specialRequests: $model->special_requests,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    public function getUpcomingReservations(): Collection
    {
        return TableReservationModel::where('tenant_id', $this->tenantId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('reservation_time', '>=', now())
            ->orderBy('reservation_time')
            ->with('table', 'table.zone')
            ->get()
            ->map(fn ($model) => new TableReservation(
                id: $model->id,
                tenantId: $model->tenant_id,
                tableId: $model->table_id,
                userId: $model->user_id,
                customerName: $model->customer_name,
                customerPhone: $model->customer_phone,
                customerEmail: $model->customer_email,
                guestCount: $model->guest_count,
                reservationTime: $model->reservation_time->toImmutable(),
                arrivalTime: $model->arrival_time?->toImmutable(),
                completedAt: $model->completed_at?->toImmutable(),
                cancelledAt: $model->cancelled_at?->toImmutable(),
                status: ReservationStatus::from($model->status),
                specialRequests: $model->special_requests,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    public function getPastDueReservations(): Collection
    {
        return TableReservationModel::where('tenant_id', $this->tenantId)
            ->where('reservation_time', '<', now())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('reservation_time')
            ->with('table')
            ->get()
            ->map(fn ($model) => new TableReservation(
                id: $model->id,
                tenantId: $model->tenant_id,
                tableId: $model->table_id,
                userId: $model->user_id,
                customerName: $model->customer_name,
                customerPhone: $model->customer_phone,
                customerEmail: $model->customer_email,
                guestCount: $model->guest_count,
                reservationTime: $model->reservation_time->toImmutable(),
                arrivalTime: $model->arrival_time?->toImmutable(),
                completedAt: $model->completed_at?->toImmutable(),
                cancelledAt: $model->cancelled_at?->toImmutable(),
                status: ReservationStatus::from($model->status),
                specialRequests: $model->special_requests,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    public function getReservationsByDate(CarbonImmutable $date): Collection
    {
        return TableReservationModel::where('tenant_id', $this->tenantId)
            ->whereDate('reservation_time', $date)
            ->orderBy('reservation_time')
            ->with('table')
            ->get()
            ->map(fn ($model) => new TableReservation(
                id: $model->id,
                tenantId: $model->tenant_id,
                tableId: $model->table_id,
                userId: $model->user_id,
                customerName: $model->customer_name,
                customerPhone: $model->customer_phone,
                customerEmail: $model->customer_email,
                guestCount: $model->guest_count,
                reservationTime: $model->reservation_time->toImmutable(),
                arrivalTime: $model->arrival_time?->toImmutable(),
                completedAt: $model->completed_at?->toImmutable(),
                cancelledAt: $model->cancelled_at?->toImmutable(),
                status: ReservationStatus::from($model->status),
                specialRequests: $model->special_requests,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }
}
