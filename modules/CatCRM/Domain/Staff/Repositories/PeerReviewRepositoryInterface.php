<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Repositories;

use Modules\CatCRM\Domain\Staff\PeerReview;
use Modules\CatCRM\Domain\Staff\ValueObjects\PeerReviewId;

/**
 * PeerReviewRepositoryInterface — Interface for PeerReview repository
 */
interface PeerReviewRepositoryInterface
{
    public function findById(PeerReviewId $id): ?PeerReview;

    public function findByReviewer(int $tenantId, int $reviewerId): array;

    public function findByReviewee(int $tenantId, int $revieweeId): array;

    public function findPendingByReviewer(int $tenantId, int $reviewerId): array;

    public function save(PeerReview $peerReview): bool;

    public function delete(PeerReviewId $id): bool;
}
