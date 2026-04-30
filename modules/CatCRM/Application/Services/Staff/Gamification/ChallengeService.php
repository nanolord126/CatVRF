<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Gamification;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ChallengeService — Сервис челленджей и конкурсов
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class ChallengeService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Создать челлендж
     */
    public function createChallenge(
        int $tenantId,
        string $title,
        string $description,
        int $pointsReward,
        string $startDate,
        string $endDate,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $title, $description, $pointsReward, $startDate, $endDate, $correlationId, $userId) {
            // TODO: Create challenge in database
            $challengeId = 1; // Placeholder

            // Invalidate cache
            Cache::tags(['staff', 'challenges', "tenant:{$tenantId}"])->flush();

            $this->logCreated(
                entityType: 'challenge',
                entityId: $challengeId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'challenge_title' => $title,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'challenge_id' => $challengeId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Участвовать в челлендже
     */
    public function joinChallenge(
        int $tenantId,
        int $challengeId,
        int $employeeId,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $challengeId, $employeeId, $correlationId, $userId) {
            // TODO: Create challenge participation
            $participationId = 1; // Placeholder

            $this->logAction(
                action: 'challenge_joined',
                entityType: 'challenge_participation',
                entityId: $participationId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'challenge_id' => $challengeId,
                    'employee_id' => $employeeId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'participation_id' => $participationId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить активные челленджи
     */
    public function getActiveChallenges(int $tenantId): array
    {
        $cacheKey = "staff:challenges:active:{$tenantId}";

        return Cache::tags(['staff', 'challenges', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addMinutes(30),
            function () use ($tenantId) {
                // TODO: Fetch active challenges from database
                return [
                    ['challenge_id' => 1, 'title' => 'Weekly Sales', 'points_reward' => 500],
                ];
            }
        );
    }
}
