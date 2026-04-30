<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Communication;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * AnnouncementService — Сервис объявлений и новостей
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class AnnouncementService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Создать объявление
     */
    public function createAnnouncement(
        int $tenantId,
        int $authorId,
        string $title,
        string $content,
        ?array $targetAudience = null, // ['department' => 'sales', 'role' => 'manager']
        ?string $priority = 'normal',
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use (
            $tenantId,
            $authorId,
            $title,
            $content,
            $targetAudience,
            $priority,
            $correlationId,
            $userId
        ) {
            // TODO: Create announcement via repository
            $announcementId = 1; // Placeholder

            Cache::tags(['staff', 'announcements', "tenant:{$tenantId}"])->flush();

            $this->logCreated(
                entityType: 'announcement',
                entityId: $announcementId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'author_id' => $authorId,
                    'title' => $title,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'announcement_id' => $announcementId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить объявления
     */
    public function getAnnouncements(int $tenantId, ?int $userId = null): array
    {
        $cacheKey = "staff:announcements:{$tenantId}";

        $announcements = Cache::tags(['staff', 'announcements', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($tenantId) {
                // TODO: Fetch from database
                return [];
            }
        );

        $this->logAction(
            action: 'announcements_viewed',
            entityType: 'tenant',
            entityId: $tenantId,
            context: ['tenant_id' => $tenantId],
            userId: $userId,
            tenantId: $tenantId
        );

        return $announcements;
    }
}
