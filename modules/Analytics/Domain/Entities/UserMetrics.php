<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Entities;

use Carbon\CarbonImmutable;

/**
 * User Metrics Domain Entity
 *
 * Represents aggregated metrics for a specific user.
 */
final readonly class UserMetrics
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly CarbonImmutable $date,
        public readonly int $sessions,
        public readonly int $pageViews,
        public readonly int $productsViewed,
        public readonly int $ordersPlaced,
        public readonly float $totalSpent,
        public readonly float $avgOrderValue,
        public readonly int $cartItems,
        public readonly float $cartValue,
        public readonly string $rfmSegment,
        public readonly int $daysSinceLastOrder,
        public readonly CarbonImmutable $calculatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $userId,
        CarbonImmutable $date,
        int $sessions = 0,
        int $pageViews = 0,
        int $productsViewed = 0,
        int $ordersPlaced = 0,
        float $totalSpent = 0.0,
        int $cartItems = 0,
        float $cartValue = 0.0,
        string $rfmSegment = 'unknown',
        int $daysSinceLastOrder = 999,
    ): self {
        // Calculate AOV
        $avgOrderValue = $ordersPlaced > 0 ? $totalSpent / $ordersPlaced : 0.0;

        return new self(
            id: null,
            tenantId: $tenantId,
            userId: $userId,
            date: $date->startOfDay(),
            sessions: $sessions,
            pageViews: $pageViews,
            productsViewed: $productsViewed,
            ordersPlaced: $ordersPlaced,
            totalSpent: $totalSpent,
            avgOrderValue: $avgOrderValue,
            cartItems: $cartItems,
            cartValue: $cartValue,
            rfmSegment: $rfmSegment,
            daysSinceLastOrder: $daysSinceLastOrder,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    public function withId(int $id): self
    {
        return new self(
            id: $id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            date: $this->date,
            sessions: $this->sessions,
            pageViews: $this->pageViews,
            productsViewed: $this->productsViewed,
            ordersPlaced: $this->ordersPlaced,
            totalSpent: $this->totalSpent,
            avgOrderValue: $this->avgOrderValue,
            cartItems: $this->cartItems,
            cartValue: $this->cartValue,
            rfmSegment: $this->rfmSegment,
            daysSinceLastOrder: $this->daysSinceLastOrder,
            calculatedAt: $this->calculatedAt,
        );
    }

    public function merge(self $other): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            date: $this->date,
            sessions: $this->sessions + $other->sessions,
            pageViews: $this->pageViews + $other->pageViews,
            productsViewed: $this->productsViewed + $other->productsViewed,
            ordersPlaced: $this->ordersPlaced + $other->ordersPlaced,
            totalSpent: $this->totalSpent + $other->totalSpent,
            avgOrderValue: ($this->ordersPlaced + $other->ordersPlaced) > 0
                ? ($this->totalSpent + $other->totalSpent) / ($this->ordersPlaced + $other->ordersPlaced)
                : 0.0,
            cartItems: $this->cartItems + $other->cartItems,
            cartValue: $this->cartValue + $other->cartValue,
            rfmSegment: $other->rfmSegment !== 'unknown' ? $other->rfmSegment : $this->rfmSegment,
            daysSinceLastOrder: min($this->daysSinceLastOrder, $other->daysSinceLastOrder),
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Check if user is active (has session in the period).
     */
    public function isActive(): bool
    {
        return $this->sessions > 0;
    }

    /**
     * Check if user is a returning customer (has previous orders).
     */
    public function isReturning(): bool
    {
        return $this->ordersPlaced > 0 && $this->daysSinceLastOrder > 0;
    }
}
