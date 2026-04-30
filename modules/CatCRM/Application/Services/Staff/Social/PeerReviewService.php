<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Social;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\CatCRM\Application\DTOs\Staff\CreatePeerReviewDTO;
use Modules\CatCRM\Domain\Staff\Repositories\PeerReviewRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PeerReviewService — Сервис peer review (отзывы между сотрудниками)
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class PeerReviewService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
        public PeerReviewRepositoryInterface $peerReviewRepository,
    ) {}

    /**
     * Создать peer review
     */
    public function createPeerReview(CreatePeerReviewDTO $dto, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($dto, $correlationId, $userId) {
            // TODO: Create peer review via repository
            $reviewId = 1; // Placeholder

            // Invalidate cache
            Cache::tags(['staff', 'peer_reviews', "tenant:{$dto->tenantId}"])->flush();

            $this->logCreated(
                entityType: 'peer_review',
                entityId: $reviewId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $dto->tenantId,
                    'reviewer_id' => $dto->reviewerId,
                    'reviewee_id' => $dto->revieweeId,
                    'rating' => $dto->rating,
                ],
                userId: $userId,
                tenantId: $dto->tenantId
            );

            return [
                'review_id' => $reviewId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить отзывы о сотруднике
     */
    public function getEmployeeReviews(int $tenantId, int $employeeId): array
    {
        $cacheKey = "staff:peer_reviews:{$tenantId}:{$employeeId}";

        return Cache::tags(['staff', 'peer_reviews', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId, $employeeId) {
                return $this->peerReviewRepository->findByReviewee($tenantId, $employeeId);
            }
        );
    }

    /**
     * Получить средний рейтинг сотрудника
     */
    public function getAverageRating(int $tenantId, int $employeeId): float
    {
        $reviews = $this->getEmployeeReviews($tenantId, $employeeId);
        
        if (empty($reviews)) {
            return 0.0;
        }

        $total = array_sum(array_map(fn($r) => $r->rating, $reviews));
        return round($total / count($reviews), 2);
    }
}
