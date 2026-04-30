<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Entities;

use Ramsey\Uuid\UuidInterface;

/**
 * Рейтинг позиции для алгоритмов ранжирования
 * Комбинирует множество факторов в единую метрику
 */
final readonly class RankingScore
{
    private function __construct(
        public UuidInterface $listingUuid,
        public float $overallScore,
        public float $popularityScore,
        public float $conversionScore,
        public float $recencyScore,
        public float $ratingScore,
        public float $priceScore,
        public float $availabilityScore,
        public float $promotedScore,
        public float $promotionBoostScore,
        public float $mlScore,
        public float $personalizationScore,
        public array $factors,
        public string $algorithmVersion,
        public \DateTimeImmutable $calculatedAt,
        public \DateTimeImmutable $validUntil,
    ) {
        $this->validate();
    }

    public static function create(
        UuidInterface $listingUuid,
        array $factors = [],
        string $algorithmVersion = '1.0.0',
    ): self {
        $now = new \DateTimeImmutable();
        $validUntil = $now->modify('+1 hour');

        return new self(
            listingUuid: $listingUuid,
            overallScore: 0.0,
            popularityScore: 0.0,
            conversionScore: 0.0,
            recencyScore: 0.0,
            ratingScore: 0.0,
            priceScore: 0.0,
            availabilityScore: 0.0,
            promotedScore: 0.0,
            promotionBoostScore: 0.0,
            mlScore: 0.0,
            personalizationScore: 0.0,
            factors: $factors,
            algorithmVersion: $algorithmVersion,
            calculatedAt: $now,
            validUntil: $validUntil,
        );
    }

    public static function fromMetrics(
        UuidInterface $listingUuid,
        float $popularityScore,
        float $conversionScore,
        float $recencyScore,
        float $ratingScore,
        float $priceScore,
        float $availabilityScore,
        float $promotedScore = 0.0,
        float $mlScore = 0.0,
        float $personalizationScore = 0.0,
        array $factors = [],
        string $algorithmVersion = '1.0.0',
    ): self {
        $now = new \DateTimeImmutable();
        $validUntil = $now->modify('+1 hour');

        // Weighted average for overall score
        $weights = [
            'popularity' => 0.25,
            'conversion' => 0.20,
            'recency' => 0.15,
            'rating' => 0.15,
            'price' => 0.10,
            'availability' => 0.10,
            'promoted' => 0.03,
            'ml' => 0.01,
            'personalization' => 0.01,
        ];

        $overallScore =
            $popularityScore * $weights['popularity'] +
            $conversionScore * $weights['conversion'] +
            $recencyScore * $weights['recency'] +
            $ratingScore * $weights['rating'] +
            $priceScore * $weights['price'] +
            $availabilityScore * $weights['availability'] +
            $promotedScore * $weights['promoted'] +
            $mlScore * $weights['ml'] +
            $personalizationScore * $weights['personalization'];

        return new self(
            listingUuid: $listingUuid,
            overallScore: round($overallScore, 4),
            popularityScore: $popularityScore,
            conversionScore: $conversionScore,
            recencyScore: $recencyScore,
            ratingScore: $ratingScore,
            priceScore: $priceScore,
            availabilityScore: $availabilityScore,
            promotedScore: $promotedScore,
            mlScore: $mlScore,
            personalizationScore: $personalizationScore,
            factors: $factors,
            algorithmVersion: $algorithmVersion,
            calculatedAt: $now,
            validUntil: $validUntil,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            listingUuid: \Ramsey\Uuid\Uuid::fromString($data['listing_uuid']),
            overallScore: (float) $data['overall_score'],
            popularityScore: (float) $data['popularity_score'],
            conversionScore: (float) $data['conversion_score'],
            recencyScore: (float) $data['recency_score'],
            ratingScore: (float) $data['rating_score'],
            priceScore: (float) $data['price_score'],
            availabilityScore: (float) $data['availability_score'],
            promotedScore: (float) $data['promoted_score'],
            mlScore: (float) $data['ml_score'],
            personalizationScore: (float) $data['personalization_score'],
            factors: (array) $data['factors'],
            algorithmVersion: $data['algorithm_version'],
            calculatedAt: new \DateTimeImmutable($data['calculated_at']),
            validUntil: new \DateTimeImmutable($data['valid_until']),
        );
    }

    public function toArray(): array
    {
        return [
            'listing_uuid' => $this->listingUuid->toString(),
            'overall_score' => $this->overallScore,
            'popularity_score' => $this->popularityScore,
            'conversion_score' => $this->conversionScore,
            'recency_score' => $this->recencyScore,
            'rating_score' => $this->ratingScore,
            'price_score' => $this->priceScore,
            'availability_score' => $this->availabilityScore,
            'promoted_score' => $this->promotedScore,
            'ml_score' => $this->mlScore,
            'personalization_score' => $this->personalizationScore,
            'factors' => $this->factors,
            'algorithm_version' => $this->algorithmVersion,
            'calculated_at' => $this->calculatedAt->format('Y-m-d H:i:s'),
            'valid_until' => $this->validUntil->format('Y-m-d H:i:s'),
        ];
    }

    public function withOverallScore(float $score): self
    {
        return new self(
            ...$this->toArray(),
            overallScore: $score,
            calculatedAt: new \DateTimeImmutable(),
            validUntil: (new \DateTimeImmutable())->modify('+1 hour'),
        );
    }

    public function withMlScore(float $score): self
    {
        return new self(
            ...$this->toArray(),
            mlScore: $score,
            calculatedAt: new \DateTimeImmutable(),
            validUntil: (new \DateTimeImmutable())->modify('+1 hour'),
        );
    }

    public function withPersonalizationScore(float $score): self
    {
        return new self(
            ...$this->toArray(),
            personalizationScore: $score,
            calculatedAt: new \DateTimeImmutable(),
            validUntil: (new \DateTimeImmutable())->modify('+1 hour'),
        );
    }

    public function isValid(): bool
    {
        return $this->validUntil > new \DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        return !$this->isValid();
    }

    public function getAgeInSeconds(): int
    {
        return (new \DateTimeImmutable())->getTimestamp() - $this->calculatedAt->getTimestamp();
    }

    public function shouldRecalculate(int $maxAgeSeconds = 3600): bool
    {
        return $this->getAgeInSeconds() > $maxAgeSeconds || $this->isExpired();
    }

    public function getTopFactors(int $limit = 5): array
    {
        arsort($this->factors);
        return array_slice($this->factors, 0, $limit, true);
    }

    public function getFactor(string $name): float
    {
        return $this->factors[$name] ?? 0.0;
    }

    private function validate(): void
    {
        if ($this->overallScore < 0 || $this->overallScore > 1) {
            throw new \InvalidArgumentException('Overall score must be between 0 and 1');
        }

        if ($this->popularityScore < 0 || $this->popularityScore > 1) {
            throw new \InvalidArgumentException('Popularity score must be between 0 and 1');
        }

        if ($this->conversionScore < 0 || $this->conversionScore > 1) {
            throw new \InvalidArgumentException('Conversion score must be between 0 and 1');
        }

        if ($this->recencyScore < 0 || $this->recencyScore > 1) {
            throw new \InvalidArgumentException('Recency score must be between 0 and 1');
        }

        if ($this->ratingScore < 0 || $this->ratingScore > 1) {
            throw new \InvalidArgumentException('Rating score must be between 0 and 1');
        }

        if ($this->priceScore < 0 || $this->priceScore > 1) {
            throw new \InvalidArgumentException('Price score must be between 0 and 1');
        }

        if ($this->availabilityScore < 0 || $this->availabilityScore > 1) {
            throw new \InvalidArgumentException('Availability score must be between 0 and 1');
        }

        if ($this->promotedScore < 0 || $this->promotedScore > 1) {
            throw new \InvalidArgumentException('Promoted score must be between 0 and 1');
        }

        if ($this->mlScore < 0 || $this->mlScore > 1) {
            throw new \InvalidArgumentException('ML score must be between 0 and 1');
        }

        if ($this->personalizationScore < 0 || $this->personalizationScore > 1) {
            throw new \InvalidArgumentException('Personalization score must be between 0 and 1');
        }

        if ($this->validUntil <= $this->calculatedAt) {
            throw new \InvalidArgumentException('Valid until must be after calculated at');
        }
    }
}
