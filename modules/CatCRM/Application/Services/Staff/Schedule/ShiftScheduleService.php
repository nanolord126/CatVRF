<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Schedule;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\CatCRM\Application\DTOs\Staff\CreateShiftDTO;
use Modules\CatCRM\Domain\Staff\Repositories\ShiftRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ShiftScheduleService — Сервис графика смен
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class ShiftScheduleService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
        public ShiftRepositoryInterface $shiftRepository,
    ) {}

    /**
     * Создать смену
     */
    public function createShift(CreateShiftDTO $dto, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($dto, $correlationId, $userId) {
            // TODO: Create shift via repository
            $shiftId = 1; // Placeholder

            Cache::tags(['staff', 'shifts', "tenant:{$dto->tenantId}"])->flush();

            $this->logCreated(
                entityType: 'shift',
                entityId: $shiftId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $dto->tenantId,
                    'employee_id' => $dto->employeeId,
                ],
                userId: $userId,
                tenantId: $dto->tenantId
            );

            return [
                'shift_id' => $shiftId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить смены сотрудника
     */
    public function getEmployeeShifts(int $tenantId, int $employeeId): array
    {
        $cacheKey = "staff:shifts:{$tenantId}:{$employeeId}";

        return Cache::tags(['staff', 'shifts', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId, $employeeId) {
                return $this->shiftRepository->findByEmployee($tenantId, $employeeId);
            }
        );
    }

    /**
     * Чек-ин на смену
     */
    public function checkIn(int $tenantId, int $shiftId, float $latitude, float $longitude, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $shiftId, $latitude, $longitude, $correlationId, $userId) {
            // TODO: Create check-in record in database

            $this->logAction(
                action: 'shift_checked_in',
                entityType: 'shift',
                entityId: $shiftId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'check_in_id' => 1, // Placeholder
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Чек-аут со смены
     */
    public function checkOut(int $tenantId, int $shiftId, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $shiftId, $correlationId, $userId) {
            // TODO: Update check-in record

            $this->logAction(
                action: 'shift_checked_out',
                entityType: 'shift',
                entityId: $shiftId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'correlation_id' => $correlationId,
            ];
        });
    }
}
