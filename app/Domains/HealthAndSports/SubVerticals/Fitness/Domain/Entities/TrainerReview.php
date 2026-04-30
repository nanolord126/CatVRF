<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class TrainerReview
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $trainerId,
        public ?int $clientId,
        public ?int $sessionId,
        public string $uuid,
        public ?string $correlationId,
        public ?int $rating,
        public ?string $comment,
        public ?bool $wouldRecommend,
        public ?string $sentiment,
        public bool $isVerified,
        public bool $isVisible,
        public ?array $tags,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $trainerId,
        ?int $clientId = null,
        ?int $sessionId = null,
        ?int $businessGroupId = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            trainerId: $trainerId,
            clientId: $clientId,
            sessionId: $sessionId,
            uuid: '',
            correlationId: null,
            rating: null,
            comment: null,
            wouldRecommend: null,
            sentiment: null,
            isVerified: false,
            isVisible: true,
            tags: null,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isPromoter(): bool
    {
        return $this->wouldRecommend === true && ($this->rating ?? 0) >= 9;
    }

    public function isDetractor(): bool
    {
        return $this->wouldRecommend === false || ($this->rating ?? 10) <= 6;
    }

    public function isPositive(): bool
    {
        return $this->sentiment === 'positive' || ($this->rating ?? 0) >= 7;
    }
}
