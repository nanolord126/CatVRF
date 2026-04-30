<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\ValueObjects;

final readonly class FairnessConfig
{
    public function __construct(
        private float $minSellerExposure = 0.02,
        private float $maxDominanceShare = 0.30,
        private float $diversityMinGap = 0.05,
        private float $explorationBudget = 0.10,
        private int $minSellersInFeed = 5,
    ) {}

    public static function default(): self
    {
        return new self();
    }

    public static function aggressiveDiversity(): self
    {
        return new self(
            minSellerExposure: 0.05,
            maxDominanceShare: 0.20,
            diversityMinGap: 0.10,
            explorationBudget: 0.20,
            minSellersInFeed: 8,
        );
    }

    public static function revenueFirst(): self
    {
        return new self(
            minSellerExposure: 0.01,
            maxDominanceShare: 0.50,
            diversityMinGap: 0.02,
            explorationBudget: 0.05,
            minSellersInFeed: 3,
        );
    }

    public function getMinSellerExposure(): float
    {
        return $this->minSellerExposure;
    }

    public function getMaxDominanceShare(): float
    {
        return $this->maxDominanceShare;
    }

    public function getDiversityMinGap(): float
    {
        return $this->diversityMinGap;
    }

    public function getExplorationBudget(): float
    {
        return $this->explorationBudget;
    }

    public function getMinSellersInFeed(): int
    {
        return $this->minSellersInFeed;
    }

    public function toArray(): array
    {
        return [
            'min_seller_exposure' => $this->minSellerExposure,
            'max_dominance_share' => $this->maxDominanceShare,
            'diversity_min_gap' => $this->diversityMinGap,
            'exploration_budget' => $this->explorationBudget,
            'min_sellers_in_feed' => $this->minSellersInFeed,
        ];
    }
}
