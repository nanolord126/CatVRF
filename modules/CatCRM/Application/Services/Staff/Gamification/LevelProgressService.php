<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Gamification;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * LevelProgressService — Система уровней и прогресса
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class LevelProgressService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Начислить очки опыта сотруднику
     */
    public function awardExperience(
        int $tenantId,
        int $employeeId,
        int $points,
        string $reason,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $employeeId, $points, $reason, $correlationId, $userId) {
            // TODO: Update employee experience_points in database
            $newTotal = 1000; // Placeholder
            $newLevel = 2; // Placeholder
            $leveledUp = true; // Placeholder

            // Invalidate cache
            Cache::tags(['staff', 'progress', "tenant:{$tenantId}"])->flush();

            $this->logAction(
                action: 'experience_awarded',
                entityType: 'employee',
                entityId: $employeeId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'points' => $points,
                    'reason' => $reason,
                    'new_total' => $newTotal,
                    'leveled_up' => $leveledUp,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            if ($leveledUp) {
                $this->logAction(
                    action: 'level_up',
                    entityType: 'employee',
                    entityId: $employeeId,
                    context: [
                        'correlation_id' => $correlationId,
                        'tenant_id' => $tenantId,
                        'new_level' => $newLevel,
                    ],
                    userId: $userId,
                    tenantId: $tenantId
                );
            }

            return [
                'new_total' => $newTotal,
                'new_level' => $newLevel,
                'leveled_up' => $leveledUp,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить прогресс сотрудника
     */
    public function getEmployeeProgress(int $tenantId, int $employeeId): array
    {
        $cacheKey = "staff:progress:{$tenantId}:{$employeeId}";

        return Cache::tags(['staff', 'progress', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($tenantId, $employeeId) {
                // TODO: Fetch from database
                return [
                    'employee_id' => $employeeId,
                    'level' => 5,
                    'experience_points' => 5000,
                    'next_level_xp' => 6000,
                    'current_level_xp' => 5000,
                    'progress_percentage' => 83,
                ];
            }
        );
    }

    /**
     * Рассчитать XP для следующего уровня
     */
    public function calculateXpForLevel(int $level): int
    {
        return $level * 1000;
    }
}
