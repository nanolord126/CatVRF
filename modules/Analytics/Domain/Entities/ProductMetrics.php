<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Entities;

use Carbon\CarbonImmutable;

/**
 * Product Metrics Domain Entity
 *
 * Represents aggregated metrics for a specific product.
 */
final readonly class ProductMetrics
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly ?int $sellerId,
        public readonly CarbonImmutable $date,
        public readonly int $views,
        public readonly int $addToCart,
        public readonly int $purchases,
        public readonly float $revenue,
        public readonly int $uniqueViewers,
        public readonly float $conversionRate,
        public readonly float $cartConversionRate,
        public readonly int $refunds,
        public readonly float $refundRate,
        public readonly float $avgRating,
        public readonly CarbonImmutable $calculatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $productId,
        CarbonImmutable $date,
        ?int $sellerId = null,
        int $views = 0,
        int $addToCart = 0,
        int $purchases = 0,
        float $revenue = 0.0,
        int $uniqueViewers = 0,
        int $refunds = 0,
        float $avgRating = 0.0,
    ): self {
        // Calculate conversion rate (purchases / views)
        $conversionRate = $views > 0 ? ($purchases / $views) * 100 : 0.0;

        // Calculate cart conversion rate (purchases / add_to_cart)
        $cartConversionRate = $addToCart > 0 ? ($purchases / $addToCart) * 100 : 0.0;

        // Calculate refund rate (refunds / purchases)
        $refundRate = $purchases > 0 ? ($refunds / $purchases) * 100 : 0.0;

        return new self(
            id: null,
            tenantId: $tenantId,
            productId: $productId,
            sellerId: $sellerId,
            date: $date->startOfDay(),
            views: $views,
            addToCart: $addToCart,
            purchases: $purchases,
            revenue: $revenue,
            uniqueViewers: $uniqueViewers,
            conversionRate: $conversionRate,
            cartConversionRate: $cartConversionRate,
            refunds: $refunds,
            refundRate: $refundRate,
            avgRating: $avgRating,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    public function withId(int $id): self
    {
        return new self(
            id: $id,
            tenantId: $this->tenantId,
            productId: $this->productId,
            sellerId: $this->sellerId,
            date: $this->date,
            views: $this->views,
            addToCart: $this->addToCart,
            purchases: $this->purchases,
            revenue: $this->revenue,
            uniqueViewers: $this->uniqueViewers,
            conversionRate: $this->conversionRate,
            cartConversionRate: $this->cartConversionRate,
            refunds: $this->refunds,
            refundRate: $this->refundRate,
            avgRating: $this->avgRating,
            calculatedAt: $this->calculatedAt,
        );
    }

    public function merge(self $other): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            productId: $this->productId,
            sellerId: $this->sellerId,
            date: $this->date,
            views: $this->views + $other->views,
            addToCart: $this->addToCart + $other->addToCart,
            purchases: $this->purchases + $other->purchases,
            revenue: $this->revenue + $other->revenue,
            uniqueViewers: $this->uniqueViewers + $other->uniqueViewers,
            conversionRate: ($this->views + $other->views) > 0
                ? (($this->purchases + $other->purchases) / ($this->views + $other->views)) * 100
                : 0.0,
            cartConversionRate: ($this->addToCart + $other->addToCart) > 0
                ? (($this->purchases + $other->purchases) / ($this->addToCart + $other->addToCart)) * 100
                : 0.0,
            refunds: $this->refunds + $other->refunds,
            refundRate: ($this->purchases + $other->purchases) > 0
                ? (($this->refunds + $other->refunds) / ($this->purchases + $other->purchases)) * 100
                : 0.0,
            avgRating: ($this->avgRating + $other->avgRating) / 2,
            calculatedAt: CarbonImmutable::now(),
        );
    }
}
