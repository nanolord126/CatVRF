<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Events;

use Ramsey\Uuid\UuidInterface;

/**
 * Событие обновления рейтинга позиции
 */
final readonly class RankingUpdated
{
    public function __construct(
        public UuidInterface $listingUuid,
        public float $previousScore,
        public float $newScore,
        public string $algorithmVersion,
        public \DateTimeImmutable $occurredAt,
    ) {}

    public static function create(
        UuidInterface $listingUuid,
        float $previousScore,
        float $newScore,
        string $algorithmVersion = '1.0.0',
    ): self {
        return new self(
            listingUuid: $listingUuid,
            previousScore: $previousScore,
            newScore: $newScore,
            algorithmVersion: $algorithmVersion,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    public function toArray(): array
    {
        return [
            'listing_uuid' => $this->listingUuid->toString(),
            'previous_score' => $this->previousScore,
            'new_score' => $this->newScore,
            'algorithm_version' => $this->algorithmVersion,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function scoreChanged(): bool
    {
        return abs($this->newScore - $this->previousScore) > 0.0001;
    }

    public function scoreImproved(): bool
    {
        return $this->newScore > $this->previousScore;
    }

    public function getScoreDelta(): float
    {
        return $this->newScore - $this->previousScore;
    }
}
