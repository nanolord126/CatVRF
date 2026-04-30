<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Social;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\CatCRM\Application\DTOs\Staff\CreateMentorshipDTO;
use Modules\CatCRM\Domain\Staff\Repositories\MentorshipRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * MentoringService — Сервис наставничества
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class MentoringService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
        public MentorshipRepositoryInterface $mentorshipRepository,
    ) {}

    /**
     * Создать наставничество
     */
    public function createMentorship(CreateMentorshipDTO $dto, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($dto, $correlationId, $userId) {
            // TODO: Create mentorship via repository
            $mentorshipId = 1; // Placeholder

            // Invalidate cache
            Cache::tags(['staff', 'mentorships', "tenant:{$dto->tenantId}"])->flush();

            $this->logCreated(
                entityType: 'mentorship',
                entityId: $mentorshipId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $dto->tenantId,
                    'mentor_id' => $dto->mentorId,
                    'mentee_id' => $dto->menteeId,
                ],
                userId: $userId,
                tenantId: $dto->tenantId
            );

            return [
                'mentorship_id' => $mentorshipId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить активные наставничества ментора
     */
    public function getMentorActiveMentorships(int $tenantId, int $mentorId): array
    {
        $cacheKey = "staff:mentorships:mentor:{$tenantId}:{$mentorId}";

        return Cache::tags(['staff', 'mentorships', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId, $mentorId) {
                return $this->mentorshipRepository->findActiveByMentor($tenantId, $mentorId);
            }
        );
    }

    /**
     * Получить активное наставничество ученика
     */
    public function getMenteeActiveMentorship(int $tenantId, int $menteeId): ?array
    {
        $cacheKey = "staff:mentorships:mentee:{$tenantId}:{$menteeId}";

        $mentorship = Cache::tags(['staff', 'mentorships', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId, $menteeId) {
                return $this->mentorshipRepository->findActiveByMentee($tenantId, $menteeId);
            }
        );

        return $mentorship ? [$mentorship] : null;
    }

    /**
     * Завершить наставничество
     */
    public function completeMentorship(int $tenantId, int $mentorshipId, ?string $feedback, ?int $userId = null): bool
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $mentorshipId, $feedback, $correlationId, $userId) {
            // TODO: Update mentorship status to completed via repository

            Cache::tags(['staff', 'mentorships', "tenant:{$tenantId}"])->flush();

            $this->logAction(
                action: 'mentorship_completed',
                entityType: 'mentorship',
                entityId: $mentorshipId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }
}
