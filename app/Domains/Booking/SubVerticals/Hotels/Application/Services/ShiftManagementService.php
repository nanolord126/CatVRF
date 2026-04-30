<?php

declare(strict_types=1);

namespace Modules\Hotels\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\Hotels\Domain\Entities\Shift;
use Modules\Hotels\Domain\Repositories\ShiftRepositoryInterface;
use Modules\Hotels\Domain\ValueObjects\VenueId;
use Modules\Hotels\Domain\ValueObjects\TenantId;
use Modules\Hotels\Domain\ValueObjects\UserId;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

final readonly class ShiftManagementService
{
    use WithAuditLogging;

    public function __construct(
        private ShiftRepositoryInterface $shiftRepository,
        private readonly AuditService $auditService,
    ) {}

    public function createShift(
        TenantId $tenantId,
        VenueId $venueId,
        UserId $userId,
        string $role,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime,
        ?string $location = null,
        ?UserId $supervisorId = null,
    ): Shift {
        $shift = Shift::create(
            tenantId: $tenantId,
            venueId: $venueId,
            userId: $userId,
            role: $role,
            startTime: $startTime,
            endTime: $endTime,
            location: $location,
            supervisorId: $supervisorId,
        );

        $this->shiftRepository->save($shift);

        $this->logCreated('Shift', $shift->id, $userId->value, $tenantId->value, [
            'venue_id' => $venueId->value,
            'role' => $role,
        ]);

        return $shift;
    }

    public function startShift(int $shiftId): Shift
    {
        $shift = $this->shiftRepository->findById(\Modules\Hotels\Domain\ValueObjects\ShiftId::fromInt($shiftId));
        if ($shift === null) {
            throw new \InvalidArgumentException('Shift not found');
        }

        $updatedShift = $shift->start();
        $this->shiftRepository->save($updatedShift);

        $this->logAction('shift_started', 'Shift', $shiftId, [
            'user_id' => $shift->userId->value,
        ], $shift->userId->value, null);

        return $updatedShift;
    }

    public function endShift(int $shiftId): Shift
    {
        $shift = $this->shiftRepository->findById(\Modules\Hotels\Domain\ValueObjects\ShiftId::fromInt($shiftId));
        if ($shift === null) {
            throw new \InvalidArgumentException('Shift not found');
        }

        $updatedShift = $shift->end();
        $this->shiftRepository->save($updatedShift);

        $this->logAction('shift_ended', 'Shift', $shiftId, [
            'user_id' => $shift->userId->value,
            'duration_hours' => $updatedShift->getDurationHours(),
        ], $shift->userId->value, null);

        return $updatedShift;
    }

    public function cancelShift(int $shiftId, string $reason): Shift
    {
        $shift = $this->shiftRepository->findById(\Modules\Hotels\Domain\ValueObjects\ShiftId::fromInt($shiftId));
        if ($shift === null) {
            throw new \InvalidArgumentException('Shift not found');
        }

        $updatedShift = $shift->cancel($reason);
        $this->shiftRepository->save($updatedShift);

        $this->logAction('shift_cancelled', 'Shift', $shiftId, [
            'reason' => $reason,
        ], $shift->userId->value, null);

        return $updatedShift;
    }

    public function getActiveShifts(VenueId $venueId): array
    {
        return $this->shiftRepository->findActiveShifts($venueId);
    }

    public function getUserShifts(UserId $userId): array
    {
        return $this->shiftRepository->findByUser($userId);
    }

    public function getShiftsForDateRange(VenueId $venueId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return $this->shiftRepository->findByVenueAndDateRange($venueId, $start, $end);
    }
}
