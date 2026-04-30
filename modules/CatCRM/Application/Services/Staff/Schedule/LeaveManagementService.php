<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Schedule;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\CatCRM\Application\DTOs\Staff\CreateLeaveDTO;
use Modules\CatCRM\Domain\Staff\Repositories\LeaveRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * LeaveManagementService — Сервис управления отпусками и больничными
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class LeaveManagementService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
        public LeaveRepositoryInterface $leaveRepository,
    ) {}

    /**
     * Создать запрос на отпуск/больничный
     */
    public function createLeave(CreateLeaveDTO $dto, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($dto, $correlationId, $userId) {
            // TODO: Create leave via repository
            $leaveId = 1; // Placeholder

            Cache::tags(['staff', 'leaves', "tenant:{$dto->tenantId}"])->flush();

            $this->logCreated(
                entityType: 'leave',
                entityId: $leaveId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $dto->tenantId,
                    'employee_id' => $dto->employeeId,
                    'type' => $dto->type,
                ],
                userId: $userId,
                tenantId: $dto->tenantId
            );

            return [
                'leave_id' => $leaveId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Одобрить отпуск
     */
    public function approveLeave(int $tenantId, int $leaveId, int $approvedBy, ?int $userId = null): bool
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $leaveId, $approvedBy, $correlationId, $userId) {
            // TODO: Update leave status to approved via repository

            Cache::tags(['staff', 'leaves', "tenant:{$tenantId}"])->flush();

            $this->logAction(
                action: 'leave_approved',
                entityType: 'leave',
                entityId: $leaveId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'approved_by' => $approvedBy,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Отклонить отпуск
     */
    public function rejectLeave(int $tenantId, int $leaveId, string $reason, ?int $userId = null): bool
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $leaveId, $reason, $correlationId, $userId) {
            // TODO: Update leave status to rejected via repository

            Cache::tags(['staff', 'leaves', "tenant:{$tenantId}"])->flush();

            $this->logAction(
                action: 'leave_rejected',
                entityType: 'leave',
                entityId: $leaveId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'reject_reason' => $reason,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Получить отпуска сотрудника
     */
    public function getEmployeeLeaves(int $tenantId, int $employeeId): array
    {
        $cacheKey = "staff:leaves:{$tenantId}:{$employeeId}";

        return Cache::tags(['staff', 'leaves', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId, $employeeId) {
                return $this->leaveRepository->findByEmployee($tenantId, $employeeId);
            }
        );
    }
}
