<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

use Carbon\CarbonImmutable;

/**
 * Product Analytics DTO
 *
 * Represents analytics data for a single product row in the product table.
 * Includes views, clicks, conversions, revenue, margin, returns, rating, etc.
 */
final readonly class ProductAnalyticsDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $sellerId,
        public readonly int $views,
        public readonly int $clicks,
        public readonly int $addToCart,
        public readonly int $orders,
        public readonly float $conversionRate,
        public readonly float $revenue,
        public readonly float $margin,
        public readonly float $returnRate,
        public readonly float $avgRating,
        public readonly int $reviewCount,
        public readonly float $avgSearchPosition,
        public readonly CarbonImmutable $lastOrderAt,
    ) {}

    /**
     * Create from database row.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            productName: $data['product_name'] ?? "Product #{$data['product_id']}",
            sellerId: $data['seller_id'],
            views: $data['views'] ?? 0,
            clicks: $data['clicks'] ?? 0,
            addToCart: $data['add_to_cart'] ?? 0,
            orders: $data['purchases'] ?? 0,
            conversionRate: (float) ($data['conversion_rate'] ?? 0),
            revenue: (float) ($data['revenue'] ?? 0),
            margin: (float) ($data['margin'] ?? 0),
            returnRate: (float) ($data['refund_rate'] ?? 0),
            avgRating: (float) ($data['avg_rating'] ?? 0),
            reviewCount: $data['review_count'] ?? 0,
            avgSearchPosition: (float) ($data['avg_search_position'] ?? 0),
            lastOrderAt: isset($data['last_order_at']) 
                ? CarbonImmutable::parse($data['last_order_at']) 
                : CarbonImmutable::now(),
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'seller_id' => $this->sellerId,
            'views' => $this->views,
            'clicks' => $this->clicks,
            'add_to_cart' => $this->addToCart,
            'orders' => $this->orders,
            'conversion_rate' => $this->conversionRate,
            'revenue' => $this->revenue,
            'margin' => $this->margin,
            'return_rate' => $this->returnRate,
            'avg_rating' => $this->avgRating,
            'review_count' => $this->reviewCount,
            'avg_search_position' => $this->avgSearchPosition,
            'last_order_at' => $this->lastOrderAt->toIso8601String(),
        ];
    }
}
