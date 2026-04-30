<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Entities;

use Carbon\CarbonImmutable;

/**
 * Hourly Metrics Domain Entity
 *
 * Represents aggregated hourly metrics for real-time analytics.
 * Stored in ClickHouse or Redis for fast queries.
 */
final readonly class HourlyMetrics
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly CarbonImmutable $hour,
        public readonly int $ordersCount,
        public readonly float $ordersRevenue,
        public readonly int $usersActive,
        public readonly int $productsViewed,
        public readonly int $productsAddedToCart,
        public readonly int $sessions,
        public readonly int $pageViews,
        public readonly float $gmv,
        public readonly CarbonImmutable $calculatedAt,
    ) {}

    public static function create(
        int $tenantId,
        CarbonImmutable $hour,
        int $ordersCount = 0,
        float $ordersRevenue = 0.0,
        int $usersActive = 0,
        int $productsViewed = 0,
        int $productsAddedToCart = 0,
        int $sessions = 0,
        int $pageViews = 0,
    ): self {
        return new self(
            id: null,
            tenantId: $tenantId,
            hour: $hour->startOfHour(),
            ordersCount: $ordersCount,
            ordersRevenue: $ordersRevenue,
            usersActive: $usersActive,
            productsViewed: $productsViewed,
            productsAddedToCart: $productsAddedToCart,
            sessions: $sessions,
            pageViews: $pageViews,
            gmv: $ordersRevenue,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    public function withId(int $id): self
    {
        return new self(
            id: $id,
            tenantId: $this->tenantId,
            hour: $this->hour,
            ordersCount: $this->ordersCount,
            ordersRevenue: $this->ordersRevenue,
            usersActive: $this->usersActive,
            productsViewed: $this->productsViewed,
            productsAddedToCart: $this->productsAddedToCart,
            sessions: $this->sessions,
            pageViews: $this->pageViews,
            gmv: $this->gmv,
            calculatedAt: $this->calculatedAt,
        );
    }

    public function merge(self $other): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            hour: $this->hour,
            ordersCount: $this->ordersCount + $other->ordersCount,
            ordersRevenue: $this->ordersRevenue + $other->ordersRevenue,
            usersActive: max($this->usersActive, $other->usersActive),
            productsViewed: $this->productsViewed + $other->productsViewed,
            productsAddedToCart: $this->productsAddedToCart + $other->productsAddedToCart,
            sessions: $this->sessions + $other->sessions,
            pageViews: $this->pageViews + $other->pageViews,
            gmv: $this->gmv + $other->gmv,
            calculatedAt: CarbonImmutable::now(),
        );
    }
}
