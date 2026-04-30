<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Entities;

use Carbon\CarbonImmutable;

/**
 * Daily Metrics Domain Entity
 *
 * Represents aggregated daily metrics for the marketplace.
 * This is a domain entity - should not have infrastructure dependencies.
 */
final readonly class DailyMetrics
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly CarbonImmutable $date,
        public readonly int $ordersCount,
        public readonly float $ordersRevenue,
        public readonly float $ordersAov,
        public readonly int $usersActive,
        public readonly int $usersNew,
        public readonly int $productsViewed,
        public readonly int $productsAddedToCart,
        public readonly int $sellersActive,
        public readonly int $sessions,
        public readonly int $pageViews,
        public readonly float $gmv,
        public readonly int $refundsCount,
        public readonly float $refundsAmount,
        public readonly float $conversionRate,
        public readonly float $cartAbandonmentRate,
        public readonly CarbonImmutable $calculatedAt,
    ) {}

    public static function create(
        int $tenantId,
        CarbonImmutable $date,
        int $ordersCount = 0,
        float $ordersRevenue = 0.0,
        int $usersActive = 0,
        int $usersNew = 0,
        int $productsViewed = 0,
        int $productsAddedToCart = 0,
        int $sellersActive = 0,
        int $sessions = 0,
        int $pageViews = 0,
        int $refundsCount = 0,
        float $refundsAmount = 0.0,
    ): self {
        // Calculate AOV
        $ordersAov = $ordersCount > 0 ? $ordersRevenue / $ordersCount : 0.0;

        // Calculate GMV (Gross Merchandise Value = revenue - refunds)
        $gmv = $ordersRevenue - $refundsAmount;

        // Calculate conversion rate (orders / sessions)
        $conversionRate = $sessions > 0 ? ($ordersCount / $sessions) * 100 : 0.0;

        // Calculate cart abandonment rate (added_to_cart - orders) / added_to_cart
        $cartAbandonmentRate = $productsAddedToCart > 0
            ? (($productsAddedToCart - $ordersCount) / $productsAddedToCart) * 100
            : 0.0;

        return new self(
            id: null,
            tenantId: $tenantId,
            date: $date->startOfDay(),
            ordersCount: $ordersCount,
            ordersRevenue: $ordersRevenue,
            ordersAov: $ordersAov,
            usersActive: $usersActive,
            usersNew: $usersNew,
            productsViewed: $productsViewed,
            productsAddedToCart: $productsAddedToCart,
            sellersActive: $sellersActive,
            sessions: $sessions,
            pageViews: $pageViews,
            gmv: $gmv,
            refundsCount: $refundsCount,
            refundsAmount: $refundsAmount,
            conversionRate: $conversionRate,
            cartAbandonmentRate: $cartAbandonmentRate,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    public function withId(int $id): self
    {
        return new self(
            id: $id,
            tenantId: $this->tenantId,
            date: $this->date,
            ordersCount: $this->ordersCount,
            ordersRevenue: $this->ordersRevenue,
            ordersAov: $this->ordersAov,
            usersActive: $this->usersActive,
            usersNew: $this->usersNew,
            productsViewed: $this->productsViewed,
            productsAddedToCart: $this->productsAddedToCart,
            sellersActive: $this->sellersActive,
            sessions: $this->sessions,
            pageViews: $this->pageViews,
            gmv: $this->gmv,
            refundsCount: $this->refundsCount,
            refundsAmount: $this->refundsAmount,
            conversionRate: $this->conversionRate,
            cartAbandonmentRate: $this->cartAbandonmentRate,
            calculatedAt: $this->calculatedAt,
        );
    }

    /**
     * Merge with another DailyMetrics entity (for incremental updates).
     */
    public function merge(self $other): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            date: $this->date,
            ordersCount: $this->ordersCount + $other->ordersCount,
            ordersRevenue: $this->ordersRevenue + $other->ordersRevenue,
            ordersAov: ($this->ordersCount + $other->ordersCount) > 0
                ? ($this->ordersRevenue + $other->ordersRevenue) / ($this->ordersCount + $other->ordersCount)
                : 0.0,
            usersActive: max($this->usersActive, $other->usersActive),
            usersNew: $this->usersNew + $other->usersNew,
            productsViewed: $this->productsViewed + $other->productsViewed,
            productsAddedToCart: $this->productsAddedToCart + $other->productsAddedToCart,
            sellersActive: max($this->sellersActive, $other->sellersActive),
            sessions: $this->sessions + $other->sessions,
            pageViews: $this->pageViews + $other->pageViews,
            gmv: $this->gmv + $other->gmv,
            refundsCount: $this->refundsCount + $other->refundsCount,
            refundsAmount: $this->refundsAmount + $other->refundsAmount,
            conversionRate: ($this->sessions + $other->sessions) > 0
                ? (($this->ordersCount + $other->ordersCount) / ($this->sessions + $other->sessions)) * 100
                : 0.0,
            cartAbandonmentRate: ($this->productsAddedToCart + $other->productsAddedToCart) > 0
                ? ((($this->productsAddedToCart + $other->productsAddedToCart) - ($this->ordersCount + $other->ordersCount))
                    / ($this->productsAddedToCart + $other->productsAddedToCart)) * 100
                : 0.0,
            calculatedAt: CarbonImmutable::now(),
        );
    }
}
