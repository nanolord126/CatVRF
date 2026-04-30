<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Gamification;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;

/**
 * LeaderboardService — Сервис лидербордов
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 */
final class LeaderboardService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Получить лидерборд по очкам опыта
     */
    public function getExperienceLeaderboard(int $tenantId, int $limit = 10, ?int $userId = null): array
    {
        $cacheKey = "staff:leaderboard:experience:{$tenantId}";

        $leaderboard = Cache::tags(['staff', 'leaderboard', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(1),
            function () use ($tenantId, $limit) {
                // TODO: Fetch from database ordered by experience_points
                return [
                    ['employee_id' => 1, 'experience_points' => 5000, 'level' => 5],
                    ['employee_id' => 2, 'experience_points' => 4500, 'level' => 4],
                ];
            }
        );

        $this->logAction(
            action: 'leaderboard_viewed',
            entityType: 'tenant',
            entityId: $tenantId,
            context: [
                'tenant_id' => $tenantId,
                'leaderboard_type' => 'experience',
                'limit' => $limit,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return array_slice($leaderboard, 0, $limit);
    }

    /**
     * Получить лидерборд по производительности
     */
    public function getPerformanceLeaderboard(int $tenantId, int $limit = 10, ?int $userId = null): array
    {
        $cacheKey = "staff:leaderboard:performance:{$tenantId}";

        $leaderboard = Cache::tags(['staff', 'leaderboard', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(1),
            function () use ($tenantId, $limit) {
                // TODO: Fetch from database ordered by performance_score
                return [
                    ['employee_id' => 1, 'performance_score' => 95],
                    ['employee_id' => 2, 'performance_score' => 92],
                ];
            }
        );

        $this->logAction(
            action: 'leaderboard_viewed',
            entityType: 'tenant',
            entityId: $tenantId,
            context: [
                'tenant_id' => $tenantId,
                'leaderboard_type' => 'performance',
                'limit' => $limit,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return array_slice($leaderboard, 0, $limit);
    }

    /**
     * Получить позицию сотрудника в лидерборде
     */
    public function getEmployeeRank(int $tenantId, int $employeeId, string $type = 'experience'): ?int
    {
        $leaderboard = $type === 'experience' 
            ? $this->getExperienceLeaderboard($tenantId, 1000)
            : $this->getPerformanceLeaderboard($tenantId, 1000);

        foreach ($leaderboard as $index => $entry) {
            if ($entry['employee_id'] === $employeeId) {
                return $index + 1;
            }
        }

        return null;
    }
}
