<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * Customer Segment DTO
 *
 * Represents a customer segment for RFM analysis.
 * Used for customer analytics and segmentation.
 */
final readonly class CustomerSegmentDTO
{
    public function __construct(
        public readonly string $segment, // new, repeat, loyal, at_risk, vip
        public readonly int $customerCount,
        public readonly float $percentage,
        public readonly float $avgOrderValue,
        public readonly float $totalRevenue,
        public readonly float $avgDaysSinceLastOrder,
    ) {}

    /**
     * Create segment.
     */
    public static function create(
        string $segment,
        int $customerCount,
        int $totalCustomers,
        float $avgOrderValue,
        float $totalRevenue,
        float $avgDaysSinceLastOrder,
    ): self {
        $percentage = $totalCustomers > 0 
            ? ($customerCount / $totalCustomers) * 100 
            : 0;

        return new self(
            segment: $segment,
            customerCount: $customerCount,
            percentage: $percentage,
            avgOrderValue: $avgOrderValue,
            totalRevenue: $totalRevenue,
            avgDaysSinceLastOrder: $avgDaysSinceLastOrder,
        );
    }

    public function toArray(): array
    {
        return [
            'segment' => $this->segment,
            'customer_count' => $this->customerCount,
            'percentage' => $this->percentage,
            'avg_order_value' => $this->avgOrderValue,
            'total_revenue' => $this->totalRevenue,
            'avg_days_since_last_order' => $this->avgDaysSinceLastOrder,
        ];
    }
}
