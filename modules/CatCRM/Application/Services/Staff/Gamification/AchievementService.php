<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Gamification;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\CatCRM\Domain\Staff\Repositories\AchievementRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Repositories\BadgeRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * AchievementService — Сервис управления достижениями
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class AchievementService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
        public AchievementRepositoryInterface $achievementRepository,
        public BadgeRepositoryInterface $badgeRepository,
    ) {}

    /**
     * Наградить сотрудника бейджем
     */
    public function awardBadge(
        int $tenantId,
        int $employeeId,
        int $badgeId,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $employeeId, $badgeId, $correlationId, $userId) {
            // TODO: Create achievement via repository
            $achievementId = 1; // Placeholder

            // Invalidate cache
            Cache::tags(['staff', 'achievements', "tenant:{$tenantId}"])->flush();

            $this->logAction(
                action: 'badge_awarded',
                entityType: 'achievement',
                entityId: $achievementId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'employee_id' => $employeeId,
                    'badge_id' => $badgeId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'achievement_id' => $achievementId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить достижения сотрудника
     */
    public function getEmployeeAchievements(int $tenantId, int $employeeId): array
    {
        $cacheKey = "staff:achievements:{$tenantId}:{$employeeId}";

        return Cache::tags(['staff', 'achievements', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId, $employeeId) {
                return $this->achievementRepository->findByEmployee($tenantId, $employeeId);
            }
        );
    }

    /**
     * Проверить, имеет ли сотрудник бейдж
     */
    public function hasBadge(int $tenantId, int $employeeId, int $badgeId): bool
    {
        $achievements = $this->getEmployeeAchievements($tenantId, $employeeId);
        
        foreach ($achievements as $achievement) {
            if ($achievement->badgeId === $badgeId) {
                return true;
            }
        }

        return false;
    }
}
