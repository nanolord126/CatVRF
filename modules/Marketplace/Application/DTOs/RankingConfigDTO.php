<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\DTOs;

/**
 * DTO конфигурации алгоритмов ранжирования
 */
final readonly class RankingConfigDTO
{
    private function __construct(
        public float $popularityWeight,
        public float $conversionWeight,
        public float $recencyWeight,
        public float $ratingWeight,
        public float $priceWeight,
        public float $availabilityWeight,
        public float $promotedWeight,
        public float $mlWeight,
        public float $personalizationWeight,
        public string $algorithmVersion,
        public int $recalculationIntervalMinutes,
        public bool $enableML,
        public bool $enablePersonalization,
        public array $customFactors,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            popularityWeight: (float) ($data['popularity_weight'] ?? 0.25),
            conversionWeight: (float) ($data['conversion_weight'] ?? 0.20),
            recencyWeight: (float) ($data['recency_weight'] ?? 0.15),
            ratingWeight: (float) ($data['rating_weight'] ?? 0.15),
            priceWeight: (float) ($data['price_weight'] ?? 0.10),
            availabilityWeight: (float) ($data['availability_weight'] ?? 0.10),
            promotedWeight: (float) ($data['promoted_weight'] ?? 0.03),
            mlWeight: (float) ($data['ml_weight'] ?? 0.01),
            personalizationWeight: (float) ($data['personalization_weight'] ?? 0.01),
            algorithmVersion: $data['algorithm_version'] ?? '1.0.0',
            recalculationIntervalMinutes: (int) ($data['recalculation_interval_minutes'] ?? 60),
            enableML: (bool) ($data['enable_ml'] ?? false),
            enablePersonalization: (bool) ($data['enable_personalization'] ?? false),
            customFactors: $data['custom_factors'] ?? [],
        );
    }

    public static function create(
        ?float $popularityWeight = null,
        ?float $conversionWeight = null,
        ?float $recencyWeight = null,
        ?float $ratingWeight = null,
        ?float $priceWeight = null,
        ?float $availabilityWeight = null,
        ?float $promotedWeight = null,
        ?float $mlWeight = null,
        ?float $personalizationWeight = null,
        ?string $algorithmVersion = null,
        ?int $recalculationIntervalMinutes = null,
        ?bool $enableML = null,
        ?bool $enablePersonalization = null,
        array $customFactors = [],
    ): self {
        return new self(
            popularityWeight: $popularityWeight ?? 0.25,
            conversionWeight: $conversionWeight ?? 0.20,
            recencyWeight: $recencyWeight ?? 0.15,
            ratingWeight: $ratingWeight ?? 0.15,
            priceWeight: $priceWeight ?? 0.10,
            availabilityWeight: $availabilityWeight ?? 0.10,
            promotedWeight: $promotedWeight ?? 0.03,
            mlWeight: $mlWeight ?? 0.01,
            personalizationWeight: $personalizationWeight ?? 0.01,
            algorithmVersion: $algorithmVersion ?? '1.0.0',
            recalculationIntervalMinutes: $recalculationIntervalMinutes ?? 60,
            enableML: $enableML ?? false,
            enablePersonalization: $enablePersonalization ?? false,
            customFactors: $customFactors,
        );
    }

    public function toArray(): array
    {
        return [
            'popularity_weight' => $this->popularityWeight,
            'conversion_weight' => $this->conversionWeight,
            'recency_weight' => $this->recencyWeight,
            'rating_weight' => $this->ratingWeight,
            'price_weight' => $this->priceWeight,
            'availability_weight' => $this->availabilityWeight,
            'promoted_weight' => $this->promotedWeight,
            'ml_weight' => $this->mlWeight,
            'personalization_weight' => $this->personalizationWeight,
            'algorithm_version' => $this->algorithmVersion,
            'recalculation_interval_minutes' => $this->recalculationIntervalMinutes,
            'enable_ml' => $this->enableML,
            'enable_personalization' => $this->enablePersonalization,
            'custom_factors' => $this->customFactors,
        ];
    }

    public function getTotalWeight(): float
    {
        return $this->popularityWeight
            + $this->conversionWeight
            + $this->recencyWeight
            + $this->ratingWeight
            + $this->priceWeight
            + $this->availabilityWeight
            + $this->promotedWeight
            + $this->mlWeight
            + $this->personalizationWeight
            + array_sum($this->customFactors);
    }

    public function isNormalized(): bool
    {
        return abs($this->getTotalWeight() - 1.0) < 0.0001;
    }

    public function normalize(): self
    {
        $total = $this->getTotalWeight();
        if ($total === 0.0) {
            return self::create(); // Return default config
        }

        return new self(
            popularityWeight: $this->popularityWeight / $total,
            conversionWeight: $this->conversionWeight / $total,
            recencyWeight: $this->recencyWeight / $total,
            ratingWeight: $this->ratingWeight / $total,
            priceWeight: $this->priceWeight / $total,
            availabilityWeight: $this->availabilityWeight / $total,
            promotedWeight: $this->promotedWeight / $total,
            mlWeight: $this->mlWeight / $total,
            personalizationWeight: $this->personalizationWeight / $total,
            algorithmVersion: $this->algorithmVersion,
            recalculationIntervalMinutes: $this->recalculationIntervalMinutes,
            enableML: $this->enableML,
            enablePersonalization: $this->enablePersonalization,
            customFactors: array_map(fn($w) => $w / $total, $this->customFactors),
        );
    }

    public function withWeight(string $factor, float $weight): self
    {
        $weights = $this->toArray();
        $weights[$factor] = $weight;
        return self::fromArray($weights);
    }

    public function validate(): void
    {
        if ($this->popularityWeight < 0 || $this->popularityWeight > 1) {
            throw new \InvalidArgumentException('Popularity weight must be between 0 and 1');
        }

        if ($this->conversionWeight < 0 || $this->conversionWeight > 1) {
            throw new \InvalidArgumentException('Conversion weight must be between 0 and 1');
        }

        if ($this->recencyWeight < 0 || $this->recencyWeight > 1) {
            throw new \InvalidArgumentException('Recency weight must be between 0 and 1');
        }

        if ($this->ratingWeight < 0 || $this->ratingWeight > 1) {
            throw new \InvalidArgumentException('Rating weight must be between 0 and 1');
        }

        if ($this->priceWeight < 0 || $this->priceWeight > 1) {
            throw new \InvalidArgumentException('Price weight must be between 0 and 1');
        }

        if ($this->availabilityWeight < 0 || $this->availabilityWeight > 1) {
            throw new \InvalidArgumentException('Availability weight must be between 0 and 1');
        }

        if ($this->promotedWeight < 0 || $this->promotedWeight > 1) {
            throw new \InvalidArgumentException('Promoted weight must be between 0 and 1');
        }

        if ($this->mlWeight < 0 || $this->mlWeight > 1) {
            throw new \InvalidArgumentException('ML weight must be between 0 and 1');
        }

        if ($this->personalizationWeight < 0 || $this->personalizationWeight > 1) {
            throw new \InvalidArgumentException('Personalization weight must be between 0 and 1');
        }

        if ($this->recalculationIntervalMinutes < 1) {
            throw new \InvalidArgumentException('Recalculation interval must be at least 1 minute');
        }
    }
}
