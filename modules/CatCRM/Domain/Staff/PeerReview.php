<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\PeerReviewId;
use Modules\CatCRM\Domain\Staff\ValueObjects\PeerReviewStatus;

/**
 * PeerReview — Отзыв между сотрудниками
 * 
 * Readonly DDD entity для представления peer review
 */
final readonly class PeerReview
{
    public function __construct(
        public PeerReviewId $id,
        public int $tenantId,
        public int $reviewerId,
        public int $revieweeId,
        public int $rating, // 1-5
        public string $feedback,
        public ?string $strengths,
        public ?string $areasForImprovement,
        public PeerReviewStatus $status,
        public ?CarbonImmutable $reviewedAt,
        public array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }
}
