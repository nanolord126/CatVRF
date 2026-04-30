<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Entities;

use Carbon\CarbonImmutable;

/**
 * Seller Metrics Domain Entity
 *
 * Represents aggregated metrics for a specific seller.
 */
final readonly class SellerMetrics
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $sellerId,
        public readonly CarbonImmutable $date,
        public readonly int $ordersCount,
        public readonly float $ordersRevenue,
        public readonly float $ordersAov,
        public readonly int $productsViewed,
        public readonly int $productsSold,
        public readonly int $uniqueCustomers,
        public readonly float $conversionRate,
        public readonly int $refundsCount,
        public readonly float $refundsAmount,
        public readonly float $sellerRating,
        public readonly CarbonImmutable $calculatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $sellerId,
        CarbonImmutable $date,
        int $ordersCount = 0,
        float $ordersRevenue = 0.0,
        int $productsViewed = 0,
        int $productsSold = 0,
        int $uniqueCustomers = 0,
        int $refundsCount = 0,
        float $refundsAmount = 0.0,
        float $sellerRating = 0.0,
    ): self {
        // Calculate AOV
        $ordersAov = $ordersCount > 0 ? $ordersRevenue / $ordersCount : 0.0;

        // Calculate conversion rate (orders / product views)
        $conversionRate = $productsViewed > 0 ? ($ordersCount / $productsViewed) * 100 : 0.0;

        return new self(
            id: null,
            tenantId: $tenantId,
            sellerId: $sellerId,
            date: $date->startOfDay(),
            ordersCount: $ordersCount,
            ordersRevenue: $ordersRevenue,
            ordersAov: $ordersAov,
            productsViewed: $productsViewed,
            productsSold: $productsSold,
            uniqueCustomers: $uniqueCustomers,
            conversionRate: $conversionRate,
            refundsCount: $refundsCount,
            refundsAmount: $refundsAmount,
            sellerRating: $sellerRating,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    public function withId(int $id): self
    {
        return new self(
            id: $id,
            tenantId: $this->tenantId,
            sellerId: $this->sellerId,
            date: $this->date,
            ordersCount: $this->ordersCount,
            ordersRevenue: $this->ordersRevenue,
            ordersAov: $this->ordersAov,
            productsViewed: $this->productsViewed,
            productsSold: $this->productsSold,
            uniqueCustomers: $this->uniqueCustomers,
            conversionRate: $this->conversionRate,
            refundsCount: $this->refundsCount,
            refundsAmount: $this->refundsAmount,
            sellerRating: $this->sellerRating,
            calculatedAt: $this->calculatedAt,
        );
    }

    public function merge(self $other): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            sellerId: $this->sellerId,
            date: $this->date,
            ordersCount: $this->ordersCount + $other->ordersCount,
            ordersRevenue: $this->ordersRevenue + $other->ordersRevenue,
            ordersAov: ($this->ordersCount + $other->ordersCount) > 0
                ? ($this->ordersRevenue + $other->ordersRevenue) / ($this->ordersCount + $other->ordersCount)
                : 0.0,
            productsViewed: $this->productsViewed + $other->productsViewed,
            productsSold: $this->productsSold + $other->productsSold,
            uniqueCustomers: $this->uniqueCustomers + $other->uniqueCustomers,
            conversionRate: ($this->productsViewed + $other->productsViewed) > 0
                ? (($this->ordersCount + $other->ordersCount) / ($this->productsViewed + $other->productsViewed)) * 100
                : 0.0,
            refundsCount: $this->refundsCount + $other->refundsCount,
            refundsAmount: $this->refundsAmount + $other->refundsAmount,
            sellerRating: ($this->sellerRating + $other->sellerRating) / 2,
            calculatedAt: CarbonImmutable::now(),
        );
    }
}
