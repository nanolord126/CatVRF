<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Gamification;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\CatCRM\Application\DTOs\Staff\CreateBadgeDTO;
use Modules\CatCRM\Domain\Staff\Repositories\BadgeRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * BadgeService — Сервис управления бейджами
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class BadgeService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
        public BadgeRepositoryInterface $badgeRepository,
    ) {}

    /**
     * Создать бейдж
     */
    public function createBadge(CreateBadgeDTO $dto, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($dto, $correlationId, $userId) {
            // TODO: Convert DTO to Domain entity and save via repository
            $badgeId = 1; // Placeholder

            // Invalidate cache
            Cache::tags(['staff', 'badges', "tenant:{$dto->tenantId}"])->flush();

            $this->logCreated(
                entityType: 'badge',
                entityId: $badgeId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $dto->tenantId,
                    'badge_name' => $dto->name,
                ],
                userId: $userId,
                tenantId: $dto->tenantId
            );

            return [
                'badge_id' => $badgeId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить все бейджи тенанта
     */
    public function getBadgesByTenant(int $tenantId): array
    {
        $cacheKey = "staff:badges:{$tenantId}";

        return Cache::tags(['staff', 'badges', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($tenantId) {
                return $this->badgeRepository->findByTenant($tenantId);
            }
        );
    }

    /**
     * Получить бейджи по категории
     */
    public function getBadgesByCategory(int $tenantId, string $category): array
    {
        $cacheKey = "staff:badges:{$tenantId}:{$category}";

        return Cache::tags(['staff', 'badges', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($tenantId, $category) {
                return $this->badgeRepository->findByCategory($tenantId, $category);
            }
        );
    }
}
