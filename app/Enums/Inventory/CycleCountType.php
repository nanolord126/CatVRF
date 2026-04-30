<?php

declare(strict_types=1);

namespace App\Enums\Inventory;

/**
 * Cycle Count Type Enum
 *
 * Defines the different types of cycle counting strategies for inventory management.
 * Cycle counting is a continuous inventory auditing method where a small subset
 * of inventory is counted on a regular basis instead of a full annual count.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
enum CycleCountType: string
{
    /**
     * Count high-value A-class items (70% of value, 10% of items)
     * These items require frequent counting due to their financial impact
     */
    case A_ITEMS = 'A_items';

    /**
     * Count medium-value B-class items (20% of value, 20% of items)
     * These items require moderate frequency counting
     */
    case B_ITEMS = 'B_items';

    /**
     * Count low-value C-class items (10% of value, 70% of items)
     * These items require less frequent counting
     */
    case C_ITEMS = 'C_items';

    /**
     * Count items with highest value regardless of ABC classification
     * Focuses on items with the highest monetary value in stock
     */
    case HIGH_VALUE = 'high_value';

    /**
     * Random selection of items for counting
     * Provides unbiased sampling across all inventory
     */
    case RANDOM = 'random';

    /**
     * Count items approaching expiration date
     * Critical for medical products and perishable goods
     */
    case EXPIRING_SOON = 'expiring_soon';

    /**
     * Count items with high turnover rate
     * Focuses on fast-moving inventory
     */
    case HIGH_TURNOVER = 'high_turnover';

    /**
     * Count items with low turnover rate
     * Focuses on slow-moving or dead stock
     */
    case LOW_TURNOVER = 'low_turnover';

    /**
     * Count items with recent discrepancies
     * Focuses on problematic items that had count variances
     */
    case PROBLEMATIC = 'problematic';

    /**
     * Count items in specific location or zone
     * Useful for warehouse zone-based counting
     */
    case ZONE_BASED = 'zone_based';

    /**
     * Get the recommended frequency in days for this count type
     *
     * @return int Frequency in days
     */
    public function getFrequencyDays(): int
    {
        return match ($this) {
            self::A_ITEMS => 30, // Monthly
            self::B_ITEMS => 90, // Quarterly
            self::C_ITEMS => 180, // Semi-annually
            self::HIGH_VALUE => 30,
            self::RANDOM => 60,
            self::EXPIRING_SOON => 7, // Weekly
            self::HIGH_TURNOVER => 30,
            self::LOW_TURNOVER => 90,
            self::PROBLEMATIC => 14, // Bi-weekly
            self::ZONE_BASED => 45,
        };
    }

    /**
     * Get the priority level for this count type
     *
     * @return int Priority (1-10, higher = more important)
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::A_ITEMS => 9,
            self::B_ITEMS => 7,
            self::C_ITEMS => 4,
            self::HIGH_VALUE => 10,
            self::RANDOM => 5,
            self::EXPIRING_SOON => 10,
            self::HIGH_TURNOVER => 8,
            self::LOW_TURNOVER => 3,
            self::PROBLEMATIC => 9,
            self::ZONE_BASED => 6,
        };
    }

    /**
     * Get the description for this count type
     *
     * @return string Description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::A_ITEMS => 'High-value A-class items requiring frequent counting',
            self::B_ITEMS => 'Medium-value B-class items requiring moderate counting',
            self::C_ITEMS => 'Low-value C-class items requiring less frequent counting',
            self::HIGH_VALUE => 'Items with highest monetary value in stock',
            self::RANDOM => 'Random selection of items for unbiased sampling',
            self::EXPIRING_SOON => 'Items approaching expiration date',
            self::HIGH_TURNOVER => 'Fast-moving inventory with high turnover rate',
            self::LOW_TURNOVER => 'Slow-moving or dead stock',
            self::PROBLEMATIC => 'Items with recent count discrepancies',
            self::ZONE_BASED => 'Items in specific warehouse zone or location',
        };
    }

    /**
     * Check if this count type requires approval
     *
     * @return bool True if approval required
     */
    public function requiresApproval(): bool
    {
        return match ($this) {
            self::A_ITEMS, self::HIGH_VALUE, self::EXPIRING_SOON, self::PROBLEMATIC => true,
            default => false,
        };
    }

    /**
     * Get the variance threshold percentage for this count type
     *
     * @return float Variance threshold percentage
     */
    public function getVarianceThreshold(): float
    {
        return match ($this) {
            self::A_ITEMS, self::HIGH_VALUE => 1.0, // 1% threshold
            self::B_ITEMS => 2.0, // 2% threshold
            self::C_ITEMS, self::RANDOM => 5.0, // 5% threshold
            self::EXPIRING_SOON => 0.5, // 0.5% threshold for expiring items
            self::HIGH_TURNOVER => 2.0,
            self::LOW_TURNOVER => 10.0, // 10% threshold for low turnover
            self::PROBLEMATIC => 0.5, // Strict threshold for problematic items
            self::ZONE_BASED => 3.0,
        };
    }
}
