<?php

declare(strict_types=1);

namespace App\Shared\Application\Services;

use App\Shared\Domain\Entities\SizeChart;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SizeRecommendationService — ML-powered size recommendations for Fashion & Footwear.
 *
 * Integrates with ML personalization system for accurate size recommendations
 * based on user's purchase history, body measurements, and preferences.
 *
 * @version 2026.1
 */
final class SizeRecommendationService
{
    private const CACHE_TTL_HOURS = 24;

    /**
     * Get size recommendation for fashion product.
     *
     * @param array $userMeasurements ['chest' => float, 'waist' => float, 'hips' => float]
     * @param string $gender male, female, unisex
     * @param string|null $brand Brand name for brand-specific charts
     * @return array|null
     */
    public function recommendFashionSize(
        array $userMeasurements,
        string $gender = 'unisex',
        ?string $brand = null
    ): ?array {
        $cacheKey = "size_recommendation:fashion:" . md5(json_encode($userMeasurements)) . ":{$gender}:{$brand}";

        $recommendation = Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($userMeasurements, $gender, $brand) {
            return SizeChart::getRecommendedSize(
                $userMeasurements,
                $gender,
                $brand,
                SizeChart::CATEGORY_FASHION
            );
        });

        if (!$recommendation) {
            return null;
        }

        // Enhance with ML-based adjustments if available
        return $this->enhanceWithML($recommendation, 'fashion');
    }

    /**
     * Get size recommendation for footwear product.
     *
     * @param array $userMeasurements ['foot_length' => float, 'foot_width' => float]
     * @param string $gender male, female, unisex
     * @param string|null $brand Brand name for brand-specific charts
     * @return array|null
     */
    public function recommendFootwearSize(
        array $userMeasurements,
        string $gender = 'unisex',
        ?string $brand = null
    ): ?array {
        $cacheKey = "size_recommendation:footwear:" . md5(json_encode($userMeasurements)) . ":{$gender}:{$brand}";

        $recommendation = Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($userMeasurements, $gender, $brand) {
            return SizeChart::getRecommendedSize(
                $userMeasurements,
                $gender,
                $brand,
                SizeChart::CATEGORY_FOOTWEAR
            );
        });

        if (!$recommendation) {
            return null;
        }

        // Enhance with ML-based adjustments if available
        return $this->enhanceWithML($recommendation, 'footwear');
    }

    /**
     * Convert size between systems.
     *
     * @param string $fromSystem eu, us, uk, jp, international
     * @param string $toSystem eu, us, uk, jp, international
     * @param mixed $sizeValue Size value to convert
     * @param string $category fashion or footwear
     * @param string $gender male, female, unisex
     * @param string|null $brand Brand name
     * @return string|null
     */
    public function convertSize(
        string $fromSystem,
        string $toSystem,
        mixed $sizeValue,
        string $category,
        string $gender = 'unisex',
        ?string $brand = null
    ): ?string {
        $cacheKey = "size_convert:{$fromSystem}:{$toSystem}:{$sizeValue}:{$category}:{$gender}:{$brand}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($fromSystem, $toSystem, $sizeValue, $category, $gender, $brand) {
            $chart = $brand
                ? SizeChart::getChart($brand, $gender, $category)
                : SizeChart::getStandardChart($gender, $category);

            if (!$chart) {
                return null;
            }

            return $chart->convertSize($fromSystem, $toSystem, $sizeValue);
        });
    }

    /**
     * Get size chart for brand and category.
     */
    public function getSizeChart(string $brand, string $gender, string $category): ?SizeChart
    {
        return SizeChart::getChart($brand, $gender, $category);
    }

    /**
     * Get standard size chart.
     */
    public function getStandardSizeChart(string $gender, string $category): ?SizeChart
    {
        return SizeChart::getStandardChart($gender, $category);
    }

    /**
     * Get all available size systems for category.
     */
    public function getAvailableSizeSystems(string $category): array
    {
        return [
            SizeChart::SIZE_SYSTEM_EU,
            SizeChart::SIZE_SYSTEM_US,
            SizeChart::SIZE_SYSTEM_UK,
            SizeChart::SIZE_SYSTEM_JP,
            SizeChart::SIZE_SYSTEM_INTERNATIONAL,
        ];
    }

    /**
     * Enhance recommendation with ML-based adjustments.
     *
     * This would integrate with the ML Personalization service
     * to adjust recommendations based on user's purchase history.
     */
    private function enhanceWithML(array $recommendation, string $category): array
    {
        // TODO: Integrate with ML Personalization service
        // For now, return the base recommendation
        Log::info('Size recommendation generated', [
            'category' => $category,
            'confidence' => $recommendation['confidence'] ?? null,
            'brand' => $recommendation['brand'] ?? null,
        ]);

        return $recommendation;
    }

    /**
     * Log size feedback for ML training.
     *
     * @param int $userId User ID
     * @param string $productId Product ID
     * @param array $recommendedSize Recommended size
     * @param array $actualSize Actual purchased size
     * @param string $feedback 'correct', 'too_small', 'too_large'
     */
    public function logSizeFeedback(
        int $userId,
        string $productId,
        array $recommendedSize,
        array $actualSize,
        string $feedback
    ): void {
        Log::info('Size feedback recorded for ML training', [
            'user_id' => $userId,
            'product_id' => $productId,
            'recommended_size' => $recommendedSize,
            'actual_size' => $actualSize,
            'feedback' => $feedback,
            'timestamp' => now()->toIso8601String(),
        ]);

        // TODO: Send to ML service for training
    }

    /**
     * Get user's preferred size based on purchase history.
     *
     * @param int $userId User ID
     * @param string $brand Brand name
     * @param string $category fashion or footwear
     * @return array|null
     */
    public function getUserPreferredSize(int $userId, string $brand, string $category): ?array
    {
        $cacheKey = "user_preferred_size:{$userId}:{$brand}:{$category}";

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($userId, $brand, $category) {
            // TODO: Query user's purchase history to determine preferred size
            // This would integrate with the ML Personalization service
            return null;
        });
    }

    /**
     * Invalidate cache for user's size recommendations.
     */
    public function invalidateUserCache(int $userId): void
    {
        // Clear all user-related size recommendation caches
        Cache::tags(["user_size_recommendations:{$userId}"])->flush();
    }
}
