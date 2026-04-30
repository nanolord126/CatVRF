<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * CLV Segment Enumeration
 * 
 * Defines customer segments based on predicted CLV.
 * Used for targeting and personalization strategies.
 * 
 * Production-ready: backed enum, strict typing.
 */
enum CLVSegmentEnum: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case VIP = 'vip';

    /**
     * Get segment label for display.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Low Value',
            self::MEDIUM => 'Medium Value',
            self::HIGH => 'High Value',
            self::VIP => 'VIP',
        };
    }

    /**
     * Get segment color for UI.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::LOW => 'gray',
            self::MEDIUM => 'blue',
            self::HIGH => 'green',
            self::VIP => 'purple',
        };
    }

    /**
     * Get recommended action for seller.
     */
    public function getRecommendedAction(): string
    {
        return match ($this) {
            self::LOW => 'Send welcome discount or nurture campaign',
            self::MEDIUM => 'Cross-sell complementary products',
            self::HIGH => 'Provide priority support and exclusive offers',
            self::VIP => 'Dedicated account manager and premium perks',
        };
    }

    /**
     * Map CLV value to segment based on thresholds.
     * 
     * Thresholds (in RUB):
     * - Low: < 5,000
     * - Medium: 5,000 - 20,000
     * - High: 20,000 - 50,000
     * - VIP: > 50,000
     */
    public static function fromClv(float $clv180d): self
    {
        if ($clv180d < 5000) {
            return self::LOW;
        }
        
        if ($clv180d < 20000) {
            return self::MEDIUM;
        }
        
        if ($clv180d < 50000) {
            return self::HIGH;
        }
        
        return self::VIP;
    }

    /**
     * Get minimum CLV threshold for segment.
     */
    public function getMinThreshold(): float
    {
        return match ($this) {
            self::LOW => 0,
            self::MEDIUM => 5000,
            self::HIGH => 20000,
            self::VIP => 50000,
        };
    }

    /**
     * Get maximum CLV threshold for segment.
     */
    public function getMaxThreshold(): float
    {
        return match ($this) {
            self::LOW => 5000,
            self::MEDIUM => 20000,
            self::HIGH => 50000,
            self::VIP => PHP_FLOAT_MAX,
        };
    }
}
