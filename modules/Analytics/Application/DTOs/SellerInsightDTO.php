<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

use Carbon\CarbonImmutable;

/**
 * Seller Insight DTO
 *
 * Represents an AI-powered insight for a seller.
 * Includes the insight message, severity, and actionable recommendations.
 */
final readonly class SellerInsightDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $type, // revenue_drop, price_optimization, inventory_alert, rating_issue, opportunity
        public readonly string $title,
        public readonly string $message,
        public readonly string $severity, // critical, warning, info, opportunity
        public readonly array $metrics,
        public readonly array $recommendations,
        public readonly CarbonImmutable $generatedAt,
        public readonly ?string $mlModel = null, // For future ML integration
    ) {}

    /**
     * Create insight.
     */
    public static function create(
        string $type,
        string $title,
        string $message,
        string $severity,
        array $metrics = [],
        array $recommendations = [],
        ?string $mlModel = null,
    ): self {
        return new self(
            id: uniqid('insight_', true),
            type: $type,
            title: $title,
            message: $message,
            severity: $severity,
            metrics: $metrics,
            recommendations: $recommendations,
            generatedAt: CarbonImmutable::now(),
            mlModel: $mlModel,
        );
    }

    /**
     * Create revenue drop insight.
     */
    public static function revenueDrop(
        float $dropPercentage,
        string $reason,
        array $recommendations,
    ): self {
        return self::create(
            type: 'revenue_drop',
            title: 'Revenue Drop Detected',
            message: "Sales have dropped by {$dropPercentage}% over the last 3 days. Reason: {$reason}",
            severity: $dropPercentage > 30 ? 'critical' : 'warning',
            metrics: [
                'drop_percentage' => $dropPercentage,
                'reason' => $reason,
            ],
            recommendations: $recommendations,
        );
    }

    /**
     * Create price optimization insight.
     */
    public static function priceOptimization(
        int $productId,
        float $currentPrice,
        float $avgCategoryPrice,
        float $potentialIncrease,
    ): self {
        return self::create(
            type: 'price_optimization',
            title: 'Price Optimization Opportunity',
            message: "Product #{$productId} is priced {$potentialIncrease}% below category average. Consider increasing price.",
            severity: 'opportunity',
            metrics: [
                'product_id' => $productId,
                'current_price' => $currentPrice,
                'avg_category_price' => $avgCategoryPrice,
                'potential_increase' => $potentialIncrease,
            ],
            recommendations: [
                "Increase price by {$potentialIncrease}% to match category average",
                "Monitor conversion rate after price change",
                "Consider bundle offer for higher margin",
            ],
        );
    }

    /**
     * Create inventory alert insight.
     */
    public static function inventoryAlert(
        int $productId,
        int $currentStock,
        int $dailySalesRate,
        int $daysUntilStockout,
    ): self {
        return self::create(
            type: 'inventory_alert',
            title: 'Low Stock Alert',
            message: "Product #{$productId} will run out of stock in {$daysUntilStockout} days at current sales rate.",
            severity: $daysUntilStockout < 3 ? 'critical' : 'warning',
            metrics: [
                'product_id' => $productId,
                'current_stock' => $currentStock,
                'daily_sales_rate' => $dailySalesRate,
                'days_until_stockout' => $daysUntilStockout,
            ],
            recommendations: [
                'Reorder immediately',
                'Consider temporary price increase to slow demand',
                'Notify customers of limited availability',
            ],
        );
    }

    /**
     * Create rating issue insight.
     */
    public static function ratingIssue(
        int $productCount,
        float $avgRating,
        array $lowRatedProducts,
    ): self {
        return self::create(
            type: 'rating_issue',
            title: 'Low Product Ratings',
            message: "You have {$productCount} products with rating below 4.2. Average rating: {$avgRating}",
            severity: $avgRating < 3.5 ? 'critical' : 'warning',
            metrics: [
                'product_count' => $productCount,
                'avg_rating' => $avgRating,
                'low_rated_products' => $lowRatedProducts,
            ],
            recommendations: [
                'Review customer feedback for common issues',
                'Improve product quality or description accuracy',
                'Offer discounts to encourage positive reviews',
            ],
        );
    }

    /**
     * Create opportunity insight.
     */
    public static function opportunity(
        string $title,
        string $message,
        array $metrics,
        array $recommendations,
    ): self {
        return self::create(
            type: 'opportunity',
            title: $title,
            message: $message,
            severity: 'opportunity',
            metrics: $metrics,
            recommendations: $recommendations,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'metrics' => $this->metrics,
            'recommendations' => $this->recommendations,
            'generated_at' => $this->generatedAt->toIso8601String(),
            'ml_model' => $this->mlModel,
        ];
    }
}
