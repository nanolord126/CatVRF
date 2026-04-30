<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Communication;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ProjectDiscussionService — Сервис обсуждения проектов
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class ProjectDiscussionService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Создать обсуждение проекта
     */
    public function createProjectDiscussion(
        int $tenantId,
        int $projectId,
        int $authorId,
        string $title,
        ?string $description = null,
        ?array $participants = null,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use (
            $tenantId,
            $projectId,
            $authorId,
            $title,
            $description,
            $participants,
            $correlationId,
            $userId
        ) {
            // TODO: Create project discussion via repository
            $discussionId = 1; // Placeholder

            Cache::tags(['staff', 'projects', "tenant:{$tenantId}"])->flush();

            $this->logCreated(
                entityType: 'project_discussion',
                entityId: $discussionId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'project_id' => $projectId,
                    'title' => $title,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'discussion_id' => $discussionId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить обсуждения проекта
     */
    public function getProjectDiscussions(int $tenantId, int $projectId, ?int $userId = null): array
    {
        $cacheKey = "staff:project_discussions:{$tenantId}:{$projectId}";

        $discussions = Cache::tags(['staff', 'projects', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($tenantId, $projectId) {
                // TODO: Fetch from database
                return [];
            }
        );

        $this->logAction(
            action: 'project_discussions_viewed',
            entityType: 'project',
            entityId: $projectId,
            context: [
                'tenant_id' => $tenantId,
                'project_id' => $projectId,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $discussions;
    }
}
